<?php


        //$arq = fopen(__DIR__.'/arquivo_recebido.txt', 'w+');
        echo "Servidor UDP\n";
       
        $serverIP = "192.168.2.115"; // IP do servidor   
        $serverPort = 12384; // Porta do servidor
        
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_bind($socket, $serverIP, $serverPort);

        $bufferSize = 1024; // Tamanho do buffer para cada pacote
        $windowSize = 10; // Tamanho da janela deslizante
        $expectedSequenceNumber = 0; // Número de sequência esperado
        $receivedPackets = []; // Armazenar pacotes recebidos


        while (true) {
            // Receber pacotes
            socket_recvfrom($socket, $packet, $bufferSize + 4, 0, $clientIP, $clientPort);
            
            $sequenceNumber = unpack('N', substr($packet, 0, 4))[1];
            $packetData = substr($packet, 4);
            //print_r($packetData);
            // Simular perda de pacotes aleatoriamente
            // if (rand(0, 10) < 3) {
            //     continue; // Descartar pacote
            // }
            printf("Pacote %d recebido\n", $sequenceNumber);
            printf("Pacote %d esperado\n", $expectedSequenceNumber);
            // Verificar número de sequência e adicionar ao buffer
            if ($sequenceNumber === $expectedSequenceNumber) {
                $receivedPackets[] = $packetData;
                $expectedSequenceNumber++;
            }

            // Enviar ACK cumulativo
            $ack = pack('N', $expectedSequenceNumber - 1);
            socket_sendto($socket, $ack, 4, 0, $clientIP, $clientPort);

            // Verificar se todos os pacotes foram recebidos
            if (count($receivedPackets) === $windowSize) {
                break;
            }
        }
        print_r($receivedPackets);
        // Recriar o arquivo a partir dos pacotes recebidos
        $fileData = implode('', $receivedPackets);
        $fileData = mb_convert_encoding($fileData, 'UTF-8', 'auto');
        fopen(__DIR__.'/arquivo_recebido.txt', 'w+');
        file_put_contents(__DIR__.'/arquivo_recebido.txt', $fileData);

        socket_close($socket);


