<?php


class CapabilityLine {

    private $hash;
    /** Capability */
    private $capabilities = [];
    private $creationTimestamp;

    public function __construct($hash, $capabilities , $creationTimestamp){
        $this->hash  = $hash;
        $this->capabilities  = $capabilities;
        $this->creationTimestamp  = $creationTimestamp;
    }

    public function getHash(){
        return $this->hash;
    }
    public function getCapabilities(){
        return $this->capabilities;
    }
    public function getCreationTimestamp(){
        return $this->creationTimestamp;
    }

}