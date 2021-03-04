<?php

require_once 'DHLWS.php';

class PickUpRequestWSServices extends DHLWS{

    const PATH = '/pickup-requests';
    const PATH_AVALABILITY = '/pickup-availability';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        parent::__construct($authDTO, $updateTokenCallback);
    }

    public function createPickupReq(PickupRequestDTO $queryParams){

        try{
            $curl = $this->buildCurl(self::PATH,CURLOPT_POST, '' ,  [],json_encode((array)$queryParams));
            $result = json_decode(curl_exec($curl));

            if(curl_getinfo($curl, CURLINFO_HTTP_CODE)==401 || curl_getinfo($curl, CURLINFO_HTTP_CODE) ==403){
               $result =  json_decode($this->handleUnauthorized($curl));
            }
            $i = 0;
            $create_new_pickup_request = function (PickupRequestDTO $queryParams){
                $pickup_date = new DateTime($queryParams->pickupDate);
                date_modify($pickup_date, '+1 day');
                $queryParams->pickupDate = $pickup_date->format('Y-m-d');
                $curl = $this->buildCurl(self::PATH,CURLOPT_POST, '' ,  [],json_encode((array)$queryParams));
                return json_decode(curl_exec($curl));
            };
            while( ++$i<8 && $result && isset($result->key) && $result->key=='create_pickup_request_invalid_date'){
                $result = $create_new_pickup_request($queryParams);
            }
        }
        catch(Exception $e){
            throw $e;
        } finally{
            curl_close($curl);
        }
             
        return json_encode($result);
    }
    public function checkPickupRequestAvalabilityForSameDay(PickupRequestCheckDTO $queryParams){
        try{
            $curl = $this->buildCurl(self::PATH_AVALABILITY,CURLOPT_HTTPGET, '?'.http_build_query((array)$queryParams), [], false, true);
            $result = curl_exec($curl);

            if(curl_getinfo($curl, CURLINFO_HTTP_CODE)==401 || curl_getinfo($curl, CURLINFO_HTTP_CODE) ==403){
               $result = $this->handleUnauthorized($curl);
            }
        }
        catch(Exception $e){
            throw $e;
        } finally{
            curl_close($curl);
        }
             
        return $result;
    }

}