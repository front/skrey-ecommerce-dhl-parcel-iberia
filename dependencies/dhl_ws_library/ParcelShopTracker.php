<?php

class ParcelShopTracker extends DHLWS{

    const PATH = '/track-trace';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        parent::__construct($authDTO, $updateTokenCallback);
    }

    public function getTrackAndTrace(TrackAndTraceDTO $dto){
        try {
            $curl = $this->buildCurl(self::PATH, CURLOPT_HTTPGET,'?'.http_build_query($dto->toArray()) );
            $result = curl_exec($curl);

            if(curl_getinfo($curl, CURLINFO_HTTP_CODE)==401 || curl_getinfo($curl, CURLINFO_HTTP_CODE) ==403){
               $result = $this->handleUnauthorized($curl);
            }

        }catch(Exception $e){
            throw $e;
        } finally{
            curl_close($curl);
        }
        return $result;
    }
}
