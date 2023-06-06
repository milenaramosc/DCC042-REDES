<?php

namespace App\Controllers;

use App\Controllers\BaseController;

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


        #$serverAddress = gethostbyname($serverIP);

        # Configuração do socket
        $maxBytes = 1024;

        $windowSize = (filesize($pathDataGrama) / $maxBytes); // N no minimo 10
        $windowSliding = []; // janela deslizante

        $nextSegment = 0; // proximo segmento a ser enviado
        $segmentsConfirmed = 0; // número de segmentos confirmados
        $lastAcknowledgedSegment = -1; // último segmento confirmado



        $handle = @fopen($pathDataGrama, "r");

        for ($i = $nextSegment; $i < $nextSegment + $windowSize; $i++) {
            fseek($handle, $i);
            $windowSliding[$i] = fread($handle, $maxBytes);
            $this->sendSegment($i, $windowSliding);
        }

        while ($segmentsConfirmed < filesize($pathDataGrama)) {
            echo 'Esperando confirmação...';
            // Simulação de recebimento de confirmação
            $confirmationSegment = rand($nextSegment, $nextSegment + $windowSize - 1);
            $this->receiveConfirmation($lastAcknowledgedSegment, $segmentsConfirmed, $windowSliding, $nextSegment, $windowSize, $pathDataGrama, $handle, $maxBytes, $confirmationSegment);

            // Verificar se a confirmação é para o último segmento confirmado
            if ($confirmationSegment === $lastAcknowledgedSegment + 1) {
                // Deslizar a janela e enviar novos segmentos
                $this->slideWindow($windowSliding, $nextSegment, $windowSize, $pathDataGrama, $handle, $maxBytes);
            }
        }
        fclose($handle);
    }

    public function sendSegment($segment, &$windowSliding)
    {

        # Dados do servidor
        $serverPort = 12384;
        $serverIP = "192.168.2.115";

       
    
        if (array_key_exists($segment, $windowSliding)) {
            $packet = pack('N', $segment) . $windowSliding[$segment];

            # Cria o socket UDP
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        

            # Enviar pacote para o servidor UDP
            socket_sendto($socket, $packet, strlen($packet), 0, $serverIP, $serverPort);
        }
    }

    public function slideWindow(&$windowSliding, &$nextSegment, &$windowSize, $path, &$handle, $maxBytes)
    {
        $nextSegment++;

        if ($nextSegment + $windowSize - 1 < filesize($path)) {
            $newSegment = $nextSegment + $windowSize - 1;
            fseek($handle, $nextSegment + $newSegment);
            $windowSliding[$nextSegment] = fread($handle, $maxBytes);
            $this->sendSegment($newSegment, $windowSliding);
        }
    }

    public function receiveConfirmation(&$lastAcknowledgedSegment, &$segmentsConfirmed, &$windowSliding, &$nextSegment, &$windowSize, $path, &$handle, $maxBytes, $segment)
    {
        $lastAcknowledgedSegment = $segment;
        $segmentsConfirmed++;
        $this->slideWindow($windowSliding, $nextSegment, $windowSize, $path, $handle, $maxBytes, $segment);
    }
}
