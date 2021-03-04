<?php

require_once dirname(__FILE__).'/Exception.php';
 abstract class DHLWS{

    protected $updateTokenCallback;

    protected $authDTO = null;

    const CONTENT_TYPE ='application/json';

    const BASE_URL = 'https://api-gw.dhlparcel.nl';

    const AUTH_PATH = '/authenticate/api-key';

    public function __construct (AuthDTO $authDTO, $updateTokenCallback = false ) {
        $this->updateTokenCallback = $updateTokenCallback;
        $this->authDTO =  $authDTO;
    }
    
    public function getToken(){
        return $this->authDTO ? $this->authDTO->accessToken : null;        
    }

    protected function handleUnauthorized($curl, $curl_headers = []){

        try{
            $auth_curl = $this->buildCurl(self::AUTH_PATH,CURLOPT_POST, '' ,  [],json_encode(
                array(
                    'userId'=>$this->authDTO->userId,
                    'key'=>$this->authDTO->key) 
            ));
            $result = curl_exec($auth_curl);

            $accessReply = (json_decode($result));

            if($accessReply && $accessReply->accessToken){
                $this->authDTO->accessToken = $accessReply->accessToken;

                if($this->updateTokenCallback){
                    call_user_func($this->updateTokenCallback,$accessReply->accessToken);    
                }
            }else{
                throw new AuthenticaitonFailedException();
            }            
            
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->buildHeaders($curl_headers));

            $result = curl_exec($curl); 

        }
        catch(Exception $e){
            throw $e;
        } finally{
            curl_close($auth_curl);
        }
        return $result;
    }

    protected function buildCurl($path, $curlopt_http, $query_string = '', $headers =[], $body = false, $mergeHeaders = true ){
        $curl = curl_init();
        $url = self::BASE_URL. $path .$query_string;

        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, $curlopt_http, 1);                     
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->buildHeaders($headers, $mergeHeaders));

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        if($body) curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        return $curl;
    }

    private function buildHeaders($headers, $doMerge = true){

        if(!$doMerge){
            return $headers;
        }

        $default_headers = array(
            'content-type'=>self::CONTENT_TYPE,
            'Authorization'=> 'Bearer '.$this->authDTO->accessToken);

        $merged_headers = array_merge(
                array(
                'content-type'=>self::CONTENT_TYPE,
                'Authorization'=> 'Bearer '.$this->authDTO->accessToken
                ),
                $headers
        );

        $final_headers = [];

        foreach ($merged_headers as $key => $value) $final_headers[]=$key.': '.$value;

        return $final_headers;               
    }
}
