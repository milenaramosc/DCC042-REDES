<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ControlVariable;

class DataGrama extends BaseController
{

    public function index()
    {
        $request = service('request');

        $file = $request->getFile('file');

        if ($file->isValid()) {

            // Move o file para um diretório desejado
            $path = ROOTPATH . 'dataGramaEnviado';
            $this->clienteUDP($file);


        }

    }
    public function clienteUDP()
    {
        $request = service('request');

        $file = $request->getFile('file');

        if ($file->isValid()) {

            // Move o file para um diretório desejado
            $path = $file->getTempName(); // Caminho do arquivo a ser enviado

            $ControlVariable = new ControlVariable();
            $ControlVariable->setPathDataGrama($path);

            $serverIP = $ControlVariable->getServerIP(); // IP do servidor
            $serverPort = $ControlVariable->getServerPort(); // Porta do servidor

            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            $windowSize = 10; // Tamanho da janela deslizante
            $bufferSize = $windowSize * 1024; // Tamanho do buffer
            
            $fileSize = filesize($path); // Tamanho total do arquivo
            $seqNumber = 0; // Número de sequência inicial
            $windowBase = 0; // Base da janela deslizante do cliente
            $windowEnd = $windowBase + $windowSize - 1; // Fim da janela deslizante do cliente
            $acknowledged = []; // Números de sequência já confirmados pelo servidor
            // Enviar tamanho da janela para o servidor
            $windowMsg = "WINDOW_SIZE|" . $windowSize;
            socket_sendto($socket, $windowMsg, strlen($windowMsg), 0, $serverIP, $serverPort);
    
            // Ler e enviar o arquivo em pacotes
            $fileHandle = fopen($path, 'r');
            $window = []; // Janela deslizante do cliente

            while (!feof($fileHandle)) {
                $data = fread($fileHandle, 1024); // Ler 1024 bytes do arquivo
                $packet = "SEQ|" . $seqNumber % ($windowSize * 2) . "|" . base64_encode($data); // Pacote: SEQ|<número_de_sequência>|<dados(codificados em base64)>

                if ($seqNumber < $windowEnd) {
                // Enviar pacote e adicionar à janela deslizante
                    socket_sendto($socket, $packet, strlen($packet), 0, $serverIP, $serverPort);
                    // echo "SeqNumber: $seqNumber\n";
                    // echo "windowSize: $windowSize\n";
                    // echo "Pos:". $seqNumber % ($windowSize * 2)."\n";
                    $window[$seqNumber % ($windowSize * 2)] = $packet;
                }
               // echo "Pacote $seqNumber enviado\n";
                // var_dump($window);
                // echo "FIM";
                // Verificar os ACKs recebidos
                var_dump($acknowledged);
                foreach ($window as $seq => $sentPacket) {
                    echo "seq: $seq\n              ";
                    if (isset($acknowledged[$seq])) {
                        echo "ACK $seq recebido\n";
                        // Pacote já foi confirmado, remover da janela
                         unset($window[$seq]);
                     } else {
                         // Verificar se o pacote foi confirmado
                         $ack = '';
                         MSG_DONTWAIT;
                         if (socket_recvfrom($socket, $ack, $bufferSize, 0, $serverIP, $serverPort) !== false) {
                             $ackNumber = (int)explode('|', $ack)[1];
                             echo "ACK $ackNumber recebido\n";
                             $acknowledged[$ackNumber] = true;
    
                             if ($ackNumber == $windowBase) {
                                 // Deslizar janela
                                 $windowBase++;
                                 $windowEnd = $windowBase + $windowSize - 1;
    
                                 // Enviar pacotes seguintes da janela deslizante
                                 for ($i = $windowBase; $i <= $windowEnd; $i++) {
                                     if (isset($window[$i])) {
                                         socket_sendto($socket, $window[$i], strlen($window[$i]), 0, $serverIP, $serverPort);
                                     }
                                 }
                             }
                         }
                     }
                 }
    
                 $seqNumber++;
             }
    
             fclose($fileHandle);
    
             // Enviar mensagem de encerramento da conexão
             $closeMsg = "CLOSE_CONNECTION";
             socket_sendto($socket, $closeMsg, strlen($closeMsg), 0, $serverIP, $serverPort);
    
             socket_close($socket);
        }
        


    }
}
