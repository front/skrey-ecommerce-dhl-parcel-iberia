<?php

require_once 'DHLWS.php';
require_once 'model/CapabilitiesList.php';
require_once 'model/CapabilityLine.php';
require_once 'utils/CapabilityLoader.php';


class CapabilitiesWS extends DHLWS{

    const PATH = '/capabilities/';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        parent::__construct($authDTO, $updateTokenCallback);
    }

    /**
     * @return CapabilityLine 
     */
    public function getCapabilities(CapabilitiesRequestDTO $queryParams, CapabilitiesList $capabilityList, DateTime $todaysResetCacheTime){
        $timestamp = $queryParams->referenceTimeStamp;
        $queryParams->referenceTimeStamp = null;
        $capabilities = $capabilityList->getCapability($queryParams->hash());
        $flushCache = false;
        if($capabilities){
            $capability_cached =  new CapabilityLine($queryParams->hash(), $capabilities, $capabilities[0]->timeStamp);
            if(new DateTime($timestamp) > $todaysResetCacheTime && new DateTime($capability_cached->getCreationTimestamp()) < $todaysResetCacheTime){
                $flushCache = true; 
            }else{
                return $capability_cached;
            }
        }
        try{
            $curl = $this->buildCurl(self::PATH,CURLOPT_HTTPGET, $queryParams->getSenderType().'?'.http_build_query((array)$queryParams));
            $result = json_decode(curl_exec($curl));

            if(curl_getinfo($curl, CURLINFO_HTTP_CODE)==401 || curl_getinfo($curl, CURLINFO_HTTP_CODE) ==403){
               $result =  json_decode($this->handleUnauthorized($curl));
            }
        }
        catch(Exception $e){
            throw $e;
        } finally{
            curl_close($curl);
        
        }
        $capabilities = [];
        foreach($result as $capability){
            $c = CapabilityLoader::loadCapability($capability);
            if($c) $capabilities[]= $c;
        }

        $capability_new = new CapabilityLine($queryParams->hash(), $capabilities, $timestamp);
        $capability_cached = isset($capability_cached)? $capability_cached : null;
        $capabilityList->saveCapability($capability_new, $flushCache, $capability_cached);

        return $capability_new;
    }
}