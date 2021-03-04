<?php

class TrackAndTrace {
    private $_tracking_code;
    private $_postcode;
    
    public function __construct($tracking_code, $postcode = null) {
        $this->_tracking_code = $tracking_code;
        $this->_postcode = $postcode;
    }

    public function getTrackingCode() { return $this->_tracking_code; }
    public function getPostcode() { return $this->_postcode; }
}
