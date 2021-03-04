<?php

class ParcelShopOrderExport extends DHLWS{
    
    const PATH = '/labels';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        parent::__construct($authDTO, $updateTokenCallback);
    }

    public function createLabel(LabelCreationDTO $dto){
        try{
            $curl = $this->buildCurl(self::PATH,CURLOPT_POST, '' ,  [],json_encode((array)$dto));
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
    public function createReturnLabel(LabelCreationDTO $dto){
        try{
            $holder = $dto->receiver;
            $dto->receiver = $dto->shipper;
            $dto->shipper = $holder;

            $dto->returnLabel = true;
            //como o receiver é sempre a loja é sempre considerado um business
            $dto->receiver->address->isBusiness = true;

            $result = $this->createLabel($dto);

        }catch(Exception $e){
            throw $e;
        }      
        return $result;
    }
    public function getLabel($labelId, $return_as_pdf = true){
        try {
            $headers = $return_as_pdf?array('Accept'=> 'application/pdf'):array();

            $curl = $this->buildCurl(self::PATH,CURLOPT_HTTPGET, '/'.$labelId ,  $headers);
            $result = curl_exec($curl);

            if(curl_getinfo($curl, CURLINFO_HTTP_CODE)==401 || curl_getinfo($curl, CURLINFO_HTTP_CODE) ==403){
               $result = $this->handleUnauthorized($curl, $headers);
            }

        }catch(Exception $e){
            throw $e;
        } finally{
            curl_close($curl);
        }
        return $result;
    }
}