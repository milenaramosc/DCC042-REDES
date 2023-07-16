<?php
function simulatePacketLoss($lossProbability)
{
    $randomNumber = mt_rand() / mt_getrandmax();

    if ($randomNumber < $lossProbability) {
        // Pacote perdido, retorna verdadeiro
        return true;
    } else {
        // Pacote não perdido, retorna falso
        return false;
    }
}

function congestionControl($currentWindowSize, $packetLoss)
{
    $maxWindowSize = 100; // Tamanho máximo da janela
    $ssthresh = 16; // Threshold inicial

    if ($packetLoss) {
        // Fast Recovery
        $currentWindowSize = $ssthresh / 2; // Reduz a janela pela metade
    } elseif ($currentWindowSize < $ssthresh) {
        // Slow Start
        $currentWindowSize += 1; // Incrementa a janela em 1 a cada pacote ACK recebido
    } else {
        // Congestion Avoidance
        $congestionWindowSizeIncrement = 1 / $currentWindowSize;
        $currentWindowSize += $congestionWindowSizeIncrement; // Incrementa a janela por 1/CWND a cada pacote ACK recebido
    }

    // Limita a janela ao tamanho máximo
    if ($currentWindowSize > $maxWindowSize) {
        $currentWindowSize = $maxWindowSize;
    }

    // Limita a janela a um tamanho mínimo
    if ($currentWindowSize < 1) {
        $currentWindowSize = 1;
    }

    return $currentWindowSize;
}

echo "Servidor UDP escontando\n";
$serverIP = '127.0.0.1'; // IP do servidor
$serverPort = 12384; // Porta do servidor

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_bind($socket, $serverIP, $serverPort);

$bufferSize = 1024; // Tamanho do buffer
$windowSize = 10; // Tamanho da janela deslizante
$windowBase = 0; // Base da janela deslizante do servidor
$windowEnd = $windowBase + $windowSize - 1; // Fim da janela deslizante do servidor

$fileHandle = fopen(__DIR__ . '/arquivo_recebido.txt', 'w+'); // Caminho para salvar o arquivo recebido
$window = []; // Janela deslizante do servidor

while (true) {
    $packet = '';
    socket_recvfrom($socket, $requestMsg, $bufferSize, 0, $clientIP, $clientPort);

    if ($requestMsg === 'REQUEST_WINDOW_SIZE') {
        $responseWindowSize = "WINDOW_SIZE|{$windowSize}";
        socket_sendto($socket, $responseWindowSize, strlen($responseWindowSize), 0, $clientIP, $clientPort);
    } else {
        $packet = $requestMsg;

        if (strpos($packet, 'CLOSE_CONNECTION') !== false) {
            echo 'FIM';
            break;
        }

        $seqNumber = (int)explode('|', $packet)[1];
        // Simulação de perda de pacotes
        $packetLoss = simulatePacketLoss(0.2); // Aqui estou adotando uma probabilidade de 20% de perda de pacotes

        if ($packetLoss) {
            $ack = "ACK_PERDIDO|" . ($seqNumber % ($windowSize * 2));
            echo "Pacote perdido: " . $ack . "\n";
            //print_r($window);
            socket_sendto($socket, $ack, strlen($ack), 0, $clientIP, $clientPort);
            continue;
        }

        $data = base64_decode(explode('|', $packet)[2]);

        if ($seqNumber >= $windowBase && $seqNumber < $windowBase + $windowSize) {
            // Pacote dentro da janela deslizante do servidor
            fwrite($fileHandle, $data);

            $window[$seqNumber % ($windowSize * 2)] = $packet;

            $ack = "ACK|" . ($seqNumber % ($windowSize * 2));
            echo "ACK: " . $ack . "\n";
            socket_sendto($socket, $ack, strlen($ack), 0, $clientIP, $clientPort);
            unset($window[$seqNumber % ($windowSize * 2)]);
            if ($seqNumber == $windowBase) {
                $windowBase++;
                $windowEnd = $windowBase + $windowSize - 1;

                for ($i = $windowBase; $i <= $windowEnd; $i++) {
                    if (isset($window[$i])) {
                        fwrite($fileHandle, base64_decode(explode('|', $window[$i])[2]));
                        unset($window[$i]);
                    } else {
                        break;
                    }
                }
            }
        }

        // Controle de congestionamento
        $windowSize = congestionControl($windowSize, $packetLoss);
    }
}

fclose($fileHandle);
socket_close($socket);
