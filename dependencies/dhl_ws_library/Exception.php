<?php


class AuthenticaitonFailedException extends Exception{


    const MSG = 'Authentication on DHL servers failed';
    
    const CODE = 401;

    public function __construct(){
        parent::__construct(self::MSG, self::CODE);
    }

}

