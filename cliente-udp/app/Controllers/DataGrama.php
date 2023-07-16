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
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

            if ($socket === false) {
                echo "Erro ao criar o socket: " . socket_strerror(socket_last_error()) . "\n";
                exit;
            }

            $requestWindowSize = 'REQUEST_WINDOW_SIZE'; // Dados a serem enviados para o servidor

            socket_sendto($socket, $requestWindowSize, strlen($requestWindowSize), 0, $serverIP, $serverPort);

            $responseWindowSize = 0;
            $windowSize = 0;
            socket_recvfrom($socket, $responseWindowSize, $bufferSize, 0, $serverIP, $serverPort);
            //echo "Tamanho da janela: " . $responseWindowSize . "\n";
            // Ler e enviar o arquivo em pacotes
            $fileHandle = fopen($path, 'r');
            $window = []; // Janela deslizante do cliente
            if (!empty($responseWindowSize)) {
                if ((string)explode('|', $responseWindowSize)[0] === "WINDOW_SIZE")
                    $windowSize = (int)explode('|', $responseWindowSize)[1]; // Tamanho N da janela deslizante

                while (!feof($fileHandle)) {
                    $data = fread($fileHandle, 1024); // Ler 1024 bytes do arquivo
                    $packet = "SEQ|" . $seqNumber % ($windowSize * 2) . "|" . base64_encode($data); // Pacote: SEQ|<número_de_sequência>|<dados(codificados em base64)>

                    if ($seqNumber < $windowEnd) {
                        // Enviar pacote e adicionar à janela deslizante
                        socket_sendto($socket, $packet, strlen($packet), 0, $serverIP, $serverPort);
                        $window[$seqNumber % ($windowSize * 2)] = $packet;
                    }
                    // Verificar os ACKs recebidos
                    var_dump($acknowledged);
                    foreach ($window as $seq => $sentPacket) {
                        echo "seq: $seq\n              ";
                        if (isset($acknowledged[$seq])) {
                            echo "ACK $seq recebido\n";
                            // Pacote já foi confirmado, remover da janela
                           // unset($window[$seq]);
                        } else {
                            // Verificar se o pacote foi confirmado
                            $ack = '';
                            if (socket_recvfrom($socket, $ack, $bufferSize, 0, $serverIP, $serverPort) !== false) {
                                $ackMsg = (string)explode('|', $ack)[0];
                                $ackNumber = (int)explode('|', $ack)[1];
                                echo "ACK $ackNumber recebido\n";
                                if ($ackMsg === "ACK_PERDIDO") {
                                    // Reenviar pacote perdido
                                    echo "Reenviando pacote $ackNumber\n";
                                    print_r($window[$ackNumber]);
                                    echo "===================";
                                    socket_sendto($socket, $window[$ackNumber], strlen($window[$ackNumber]), 0, $serverIP, $serverPort);
                                }
                                else {
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
}
