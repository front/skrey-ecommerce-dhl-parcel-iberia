<?php

require_once 'DHLWS.php';

class ParcelShopLocationWSServices extends DHLWS{

    const PATH = '/parcel-shop-locations/';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        parent::__construct($authDTO, $updateTokenCallback);
    }

    public function getParcelLocations(ParcelShopLocQueryDTO $queryParams){

        try{
            $curl = $this->buildCurl(self::PATH,CURLOPT_HTTPGET,$queryParams->countryCode. '?'.http_build_query((array)$queryParams));
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

    public function getParcelShopData( $id, $country_code){
        try{
            $curl = $this->buildCurl(self::PATH,CURLOPT_HTTPGET, $country_code.'/'.$id);
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