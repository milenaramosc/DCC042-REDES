<?php
namespace App\Libraries;

class ControlVariable
{
    protected $serverIP;
    protected $serverPort;
    protected $expectedSequenceNumber;
    protected $receivedPackets;

    protected $maxBytes;
    protected $windowSliding; // janela deslizante
    protected $windowSize; // N no minimo 10
    protected $nextSegment; // proximo segmento a ser enviado
    protected $segmentsConfirmed; // número de segmentos confirmados
    protected $ISN; // número de sequência inicial
    protected $acknowledgedment; // 
    protected $handle;
    protected $pathDataGrama;


    public function __construct()
    {
        $this->serverIP = "10.5.191.126";
        $this->serverPort = 12384;
       
        $this->maxBytes = 1024;
        $this->windowSliding = [];
        $this->nextSegment = 0;
        $this->segmentsConfirmed = 0;
        $this->windowSize = 10;

        $this->ISN = mt_rand(0, pow(2, 32)-1);
        $this->acknowledgedment = -1;
        
        $this->expectedSequenceNumber = 0;
    }

    public function getServerIP()
    {
        return $this->serverIP;
    }

    public function getServerPort()
    {
        return $this->serverPort;
    }

    public function getMaxBytes()
    {
        return $this->maxBytes;
    }

    public function getWindowSliding()
    {
        return $this->windowSliding;
    }

    public function getWindowSize()
    {
        return $this->windowSize;
    }

    public function getNextSegment()
    {
        return $this->nextSegment;
    }

    public function getSegmentsConfirmed()
    {
        return $this->segmentsConfirmed;
    }

    public function getAcknowledgedment()
    {
        return $this->acknowledgedment;
    }

    public function getExpectedSequenceNumber()
    {
        return $this->expectedSequenceNumber;
    }

    public function getReceivedPackets()
    {
        return $this->receivedPackets;
    }

    public function getHandle()
    {
        return $this->handle;
    }

    public function getPathDataGrama()
    {
        return $this->pathDataGrama;
    }

    public function setServerIP($serverIP)
    {
        $this->serverIP = $serverIP;
    }
    
    public function setServerPort($serverPort)
    {
        $this->serverPort = $serverPort;
    }

    public function setMaxBytes($maxBytes)
    {
        $this->maxBytes = $maxBytes;
    }

    public function setWindowSliding($value)
    {
        $this->windowSliding[] = $value;
    }

    public function unsetWindowSliding($index)
    {
        unset($this->windowSliding[$index]);
    }

    public function setWindowSize($windowSize)
    {
        $this->windowSize = $windowSize;
    }

    public function setNextSegment($nextSegment)
    {
        $this->nextSegment = $nextSegment;
    }

    public function setSegmentsConfirmed($segmentsConfirmed)
    {
        $this->segmentsConfirmed = $segmentsConfirmed;
    }

    public function setAcknowledgedment($acknowledgedment)
    {
        $this->acknowledgedment = $acknowledgedment;
    }

    public function setExpectedSequenceNumber($expectedSequenceNumber)
    {
        $this->expectedSequenceNumber = $expectedSequenceNumber;
    }

    public function setReceivedPackets($receivedPackets)
    {
        $this->receivedPackets = $receivedPackets;
    }

    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    public function setPathDataGrama($pathDataGrama)
    {
        $this->pathDataGrama = $pathDataGrama;
    }
}