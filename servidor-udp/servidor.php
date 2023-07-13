<?php
    echo "Servidor UDP escontando\n";
    $serverIP = '127.0.0.1'; // IP do servidor
    $serverPort = 12384; // Porta do servidor
   
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_bind($socket, $serverIP, $serverPort);

    $bufferSize = 1024; // Tamanho do buffer
    $windowSize = 10; // Tamanho da janela deslizante
    $windowBase = 0; // Base da janela deslizante do servidor
    $windowEnd = $windowBase + $windowSize - 1; // Fim da janela deslizante do servidor

    // Receber tamanho da janela do cliente
    $windowMsg = '';
    socket_recvfrom($socket, $windowMsg, $bufferSize, 0, $clientIP, $clientPort);
    echo "Cliente IP: ".$clientIP . "\n";
    echo "Cliente Port: ".$clientPort . "\n";
    //echo "Teste: ".$teste . "\n";return 0;
    echo "Janela de Msg";
    print_r($windowMsg);
    echo "\n";
    $windowSize = (int)explode('|', $windowMsg)[1];
    echo "Tamnho da janela: ".$windowSize . "\n";
    $fileHandle = fopen(__DIR__.'/arquivo_recebido.txt', 'w+'); // Caminho para salvar o arquivo recebido
    $window = []; // Janela deslizante do servidor

    while (true) {
        $packet = '';
        socket_recvfrom($socket, $packet, $bufferSize, 0, $clientIP, $clientPort);
        echo "Pacote recebido: ".$packet . "\n";
        if (strpos($packet, 'CLOSE_CONNECTION') !== false) {
            echo 'FIM';
            // Recebido pedido de encerramento da conexão
            break;
        }
        echo "Pacote recebido: ".$packet . "\n";
        $seqNumber = (int)explode('|', $packet)[1];
        $data = base64_decode(explode('|', $packet)[2]);
        echo "SeqNumber: ".$seqNumber . "\n";
        if ($seqNumber >= $windowBase && $seqNumber < $windowBase + $windowSize) {
            // Pacote dentro da janela deslizante do servidor
            print_r($data);
            echo "\n";
            fwrite($fileHandle, $data);

            // Adicionar pacote à janela deslizante
            $window[$seqNumber % ($windowSize * 2)] = $packet;

            // Enviar ACK acumulativo para o cliente
            $ack = "ACK|" . ($seqNumber % ($windowSize * 2));
            echo "ACK: ".$ack . "\n";
            socket_sendto($socket, $ack, strlen($ack), 0, $clientIP, $clientPort);

            if ($seqNumber == $windowBase) {
                // Deslizar janela deslizante
                $windowBase++;
                $windowEnd = $windowBase + $windowSize - 1;

                // Confirmar pacotes seguintes na janela deslizante
                for ($i = $windowBase; $i <= $windowEnd; $i++) {
                    if (isset($window[$i])) {
                        fwrite($fileHandle, base64_decode(explode('|', $window[$i])[2]));
                        unset($window[$i]);
                    } else {
                        break;
                    }
                }
            }
        } else {
            // Pacote fora da janela deslizante, descartar
        }
    }

    fclose($fileHandle);
    socket_close($socket);
