<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ControlVariable;

class DataGrama extends BaseController
{
    
    public function index()
    {
        $request = service('request');

        $fileData = $request->getFile('arquivo');

       
        if ($fileData->isValid()) {
            $this->clienteUDP($fileData);
        }
        $response = service('response');
        return $response->setJSON([
            'status' => 'success',
            'data' => $fileData
        ]);
    }
    public function clienteUDP($fileData)
    {
        $pathDataGrama = $fileData->getTempName();
        $controlVariable = new ControlVariable();
        $controlVariable->setPathDataGrama($pathDataGrama);

        #$serverAddress = gethostbyname($serverIP);

        # Configuração do socket
        $maxBytes = 1024;

        //$controlVariable->setWindowSize(filesize($pathDataGrama) / $maxBytes); // N no minimo 10
        $windowSize = $controlVariable->getWindowSize();
        
        $nextSegment = $controlVariable->getNextSegment(); // proximo segmento a ser enviado

        // echo 'inicio nextSegment: '.$nextSegment.'\n';
        // die();
        $handle = fopen($pathDataGrama, "r");
        if ($handle === false) {
            // Lidar com o erro ao abrir o arquivo
            echo "Erro ao abrir o arquivo.";die();
        } else {
            #Continuar o processamento do arquivo
            for ($i = $nextSegment; $i < $windowSize; $i++) {
                $controlVariable->setWindowSliding(fread($handle, $maxBytes)); // janela deslizante
            }
            $controlVariable->setHandle(ftell($handle));
            fclose($handle);
            $this->sendSegment($nextSegment, $controlVariable);
        }
    
    }

    public function sendSegment($segment, &$controlVariable)
    {

        # Dados do servidor
        $serverPort = 12384;
        $serverIP = "10.0.0.106";//"192.168.2.115";//"10.5.191.126";

        $windowSliding = $controlVariable->getWindowSliding();
        $arquivo = fopen('../../temp.txt', 'w');
        
        $maxBytes = $controlVariable->getMaxBytes();
        $teste = $segment%$maxBytes;
        fwrite($arquivo, 'Segmento Teste'. $teste.'\n');
        // echo 'Segmento Teste'. $teste.'\n';
        // print_r($controlVariable->getWindowSliding());
        if (array_key_exists($segment%$maxBytes, $windowSliding)) {
            $sequenceNumber = $controlVariable->getINC() + 1;
            fwrite($arquivo, 'Enviando segmento: '.$segment.'\n');
            $packet = pack('N', $sequenceNumber) . $windowSliding[$segment % $maxBytes];

            # Cria o socket UDP
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        

            # Enviar pacote para o servidor UDP
            socket_sendto($socket, $packet, strlen($packet), 0, $serverIP, $serverPort);

            $maxBytes = $controlVariable->getMaxBytes();
            $segmentsConfirmed = $controlVariable->getSegmentsConfirmed();
            $pathDataGrama = $controlVariable->getPathDataGrama();
            //while ($segmentsConfirmed < filesize($pathDataGrama)) {
                while(true) {
                    echo 'Esperando confirmação...';
                    #Simulação de recebimento de confirmação
                    socket_recvfrom($socket, $buffer, $maxBytes, 0, $serverIP, $serverPort);
                    // $confirmationSegment = unpack('N', substr($buffer, 0, 4))[1];
                    // fwrite($arquivo, 'Sequence Number: '.$confirmationSegment.'\n   ');
                    // $controlVariable->setAcknowledgedment($confirmationSegment);
                    // $acknowledgedment = $controlVariable->getAcknowledgedment();
                    //$controlVariable->setSegmentsConfirmed($confirmationSegment);
                    fwrite($arquivo, 'Buffer: '.$buffer.'\n  ');
                  //  $confirmationSegment = rand($nextSegment, $nextSegment + $windowSize - 1);
                    $this->receiveConfirmation($controlVariable, $buffer);
                    // Verificar se a confirmação é para o último segmento confirmado
                    //die();
                    // if ($confirmationSegment === $acknowledgedment + 1) {
                    //     #Deslizar a janela e enviar novos segmentos
                    //     $this->slideWindow($controlVariable);
                    // }
        
                }
                fclose($arquivo);
        }
        else die();
        $response = service('response');
        return $response->setJSON([
            'status' => 'success',
            'data' => $controlVariable
        ]);
    }

    public function slideWindow(&$controlVariable)
    {
        $nextSegment = $controlVariable->getAcknowledgedment();

        $windowSize = $controlVariable->getWindowSize();
        $path = $controlVariable->getPathDataGrama();
        //$handle = $controlVariable->getHandle();
        $maxBytes = $controlVariable->getMaxBytes();

        $handle = fopen($path, "r");
        if ($handle === false) {
            #Lidar com o erro ao abrir o arquivo
            echo "Erro ao abrir o arquivo.";die();
        } else {
    
            if ($nextSegment < $windowSize) {
                //print_r($controlVariable->getWindowSliding());
                //$controlVariable->unsetWindowSliding($acknowledgedment);
                // echo "index: {$acknowledgedment} ========================= \n"; 
                // var_dump($controlVariable->getWindowSliding());
                $this->sendSegment($nextSegment, $controlVariable);
            }
            // else {
            //     // echo 'nextSegment: '.$nextSegment.'\n';
            //     echo "tamanho array: ".count($controlVariable->getWindowSliding())."\n";
            //     die();
            //     var_dump($controlVariable->getWindowSliding());
            // }
    
            else if (!feof($handle)) {
                //var_dump($controlVariable->getWindowSliding());
               $positionHandle = $controlVariable->getHandle();
               fseek($handle, $positionHandle);
                for ($i = 0; $i < $windowSize; $i++) {
                    $controlVariable->unsetWindowSliding($i);
                    // $controlVariable->setWindowSliding(fread($handle, $maxBytes));
                }
                for ($i = 0; $i < $windowSize; $i++) {
                    $controlVariable->setWindowSliding(fread($handle, $maxBytes));
                }
                $this->sendSegment($nextSegment, $controlVariable);
                echo "tamanho array: ".count($controlVariable->getWindowSliding())."\n";
            }
            else {
                fclose($handle);
            }
        }
    }

    public function receiveConfirmation(&$controlVariable, $buffer)
    {
        $controlVariable->setAcknowledgedment(unpack('N', substr($buffer, 0, 4))[1]);
        $this->slideWindow($controlVariable);
    }
}
