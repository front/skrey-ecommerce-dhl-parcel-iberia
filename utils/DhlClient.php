<?php

require_once plugin_dir_path(__DIR__).'dependencies/dhl_ws_library/dtos/DhlDTOs.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/ParcelShopLocationWSServices.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/ParcelShopOrderExport.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/ParcelShopTracker.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/PickUpRequestWSServices.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/model/TrackAndTrace.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/model/ParcelType.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/utils/PickupRequestHelper.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/utils/CapabilityChecker.php';
include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/CapabilitiesWS.php';

include_once __DIR__.'/CapabilitiesListExtd.php';
include_once __DIR__.'/utils.php';

class DhlClient{


    private function getAuthDto(){
        $auth = new AuthDTO();

        $options = get_option( 'dhl_parcel_options' );

        $auth->accessToken = "";
        $auth->userId = $options['wsc_user_id'];
        $auth->key = $options['wsc_key'];

        return $auth;
        
    }
    private function getTokenCallBack(){
        $callback = function($token){
            $_SESSION['dhl_token'] = $token;
        };
        return $callback;
    }


    private function createLabelDTO($id_order, $isParcel, $isReturn = false){
        $order = wc_get_order( $id_order );
        $labelDTO = new LabelCreationDTO();

        $shipper = new Dhl_receiver_shipperDTO();
        $shipper->name = new NameDTO();
        $shipper->address = new AddressDTO();

        $options = get_option( 'dhl_parcel_options' );

        $shipper->name->firstName = $options['ppai_pickup_point_contact_person_firstname'];
        $shipper->name->lastName = $options['ppai_pickup_point_contact_person_lastname'];
        $shipper->name->companyName = $options['pp_company_name'];

        $shipper->address->countryCode = $options['pp_pickup_point_country'];
        $shipper->address->postalCode = $options['pp_pickup_point_postal_code'];
        $shipper->address->city = $options['pp_pickup_point_town'];
        $shipper->address->street = $options['pp_pickup_point_street'];
        $shipper->address->number = $options['pp_pickup_point_house_number'];
        $shipper->address->addition = $options['pp_pickup_point_additional_information'];
        $shipper->address->isBusiness = true;

        $shipper->email = $options['ppai_pickup_point_parcel_email'];
        $shipper->phoneNumber = $options['ppai_pickup_point_telephone_number'];
        $shipper->vatNumber = $options['ppoc_company_vat_number'];

        $receiver = new Dhl_receiver_shipperDTO();
        $receiver->name = new NameDTO();
        $receiver->address = new AddressDTO();

        //Label
        if($isParcel && !$isReturn){
            try{
                $order = wc_get_order( $id_order );
                $dhl_client = new DhlClient();
                $pShopOrderInfo = $dhl_client->getServicePointId($order);
                $meta_data = $order->get_meta_data();

                $pShop = $this->getParcelShop($pShopOrderInfo['sp_id'], $pShopOrderInfo['sp_country']);

                $labelDTO->setServicePoint($pShop->id);
                
                $receiver->name->companyName = "";
                
                $receiver->address->countryCode = $pShop->address->countryCode;
                $receiver->address->postalCode = $pShop->address->zipCode; 
                $receiver->address->city = $pShop->address->city; 
                $receiver->address->street = $pShop->address->street;
                $receiver->address->number = $pShop->address->number;
                $receiver->address->isBusiness = false;
                $receiver->name->firstName = $order->get_shipping_first_name();
                $receiver->name->lastName = $order->get_shipping_last_name();
                
            }catch(Exception $e){
                throw($e);
            }
        }else{


            $receiver->name->firstName = $order->get_shipping_first_name();
            $receiver->name->lastName =  $order->get_shipping_last_name();
            
            $receiver->address->countryCode = $order->get_shipping_country();
            $receiver->address->postalCode = $order->get_shipping_postcode(); 
            $receiver->address->city = $order->get_shipping_city(); 
            $receiver->address->street = $order->get_shipping_address_1();
            $receiver->address->addition = $order->get_shipping_address_2();
            $receiver->address->isBusiness = false;

            $receiver->vatNumber = $options['ppoc_company_vat_number'];

        }

        $receiver->phoneNumber = $order->get_billing_phone();
        $receiver->email = $order->get_billing_email();

        $labelDTO->shipper = $shipper;
        $labelDTO->receiver = $receiver;

        $labelDTO->labelId = $this->getGUID();
        $labelDTO->labelFormat = 'pdf';
        $labelDTO->orderReference ='dhl_ref_'.$id_order;

        $labelDTO->accountId =  $options['wsc_account_id'];
        $labelDTO->returnLabel = false;
        
        $meta_data = $order->get_meta_data();
        $order_weigh = get_data_from_meta_data($meta_data,'_cart_weight');
        $labelDTO->weight = $order_weigh;

        //Cash_on_delivery
        if( !strcmp($order->get_payment_method(), "dhl_cod") && !$isParcel && !$isReturn ){
            $labelDTO->setCashOnDelivery("COD_CASH", $order->get_total());
        }

        //Reference
        $reference_field = get_data_from_meta_data($meta_data,'_reference_field');
        if($reference_field){
            $labelDTO->setReference(strval($reference_field));
        } else {
            $labelDTO->setReference("");
        }

        $labelDTO->pieceNumber = 1;
        $labelDTO->quantity = 1;
        $labelDTO->application = 'application';

        $parcelType = new ParcelType();
        $dimensions = $this->getProductDimensions($order);
        $labelDTO->parcelTypeKey = $parcelType->getParcelType($order_weigh, $dimensions);

        return $labelDTO;
    }

    private function createPickupReqDTO( $id_order = false, $pick_up_request_date){
        $dto = new PickupRequestDTO();

        $shipper = new PickupRequestShipperDTO();
        $timeSlot =  new PickupRequestTimeSlotDTO();

        $shipper->name = new NameDTO();
        $shipper->email = new PickupRequestShipperEmailDTO();
        $shipper->address = new AddressDTO();

        $options = get_option( 'dhl_parcel_options' );

        $shipper->name->firstName = $options['ppai_pickup_point_contact_person_firstname'];
        $shipper->name->lastName = $options['ppai_pickup_point_contact_person_lastname'];
        $shipper->name->companyName = $options['pp_company_name'];

        $shipper->address->countryCode = $options['pp_pickup_point_country'];
        $shipper->address->postalCode = $options['pp_pickup_point_postal_code'];
        $shipper->address->city = $options['pp_pickup_point_town'];
        $shipper->address->street = $options['pp_pickup_point_street'];
        $shipper->address->number = $options['pp_pickup_point_house_number'];
        $shipper->address->isBusiness = true;

        $shipper->email->address = $options['ppai_pickup_point_parcel_email'];
        $shipper->phoneNumber = $options['ppai_pickup_point_telephone_number'];
        $shipper->vatNumber = $options['ppoc_company_vat_number'];

        $timeSlot->from = $options['ppwh_pickup_point_opening_hours'];
        $timeSlot->to = $options['ppwh_pickup_point_closing_hours'];

        $dto->userId = $options['wsc_user_id'];
        $dto->accountId = $options['wsc_account_id'];

        $dto->pickupDate = $pick_up_request_date;
        $dto->description='';
        $dto->pickupLocation = $options['ppai_pickup_point_parcel_location'];

        $dto->numberOfPackages = 1;
        $dto->numberOfPallets = 0;
        $dto->totalWeight = 1;

        $dto->shipper = $shipper;

        $dto->timeSlot = $timeSlot;

        $dto->type='Once';
        $dto->provideLabels=false;

        return $dto;
    }

    private function createParcelShopLocQueryDTO($params){
        $dto = new ParcelShopLocQueryDTO();
        $capabilityList =  new CapabilitiesListExtd(); //??
        
        if(array_key_exists('zipcode',$params))$dto->zipCode=$params['zipcode'];
        if(array_key_exists('countryCode',$params))$dto->countryCode=$params['countryCode'];
        if(array_key_exists('city',$params))$dto->city=$params['city'];
        if(array_key_exists('q',$params))$dto->q=$params['q'];

        return $dto;
    }


    public function createDhlLabel($id_order){
        try{
            $order = wc_get_order($id_order);
            $isParcel = $this->getServicePointId($order) != null;
            $exporter = new ParcelShopOrderExport($this->getAuthDto(),$this->getTokenCallBack()); 

            $labelDTO =$this->createLabelDTO($id_order, $isParcel);
            $json =json_decode($exporter->createLabel($labelDTO));
        }catch(Exception $e){
            throw($e);
        }
        return $json;
    }

    public function createDhlReturnLabel($id_order){
        try{
            $order = wc_get_order($id_order);
            $isParcel = $this->getServicePointId($order) != null;
            $exporter = new ParcelShopOrderExport($this->getAuthDto(),$this->getTokenCallBack()); 
 
            $labelDTO =$this->createLabelDTO($id_order, $isParcel, true);

            $json =json_decode($exporter->createReturnLabel($labelDTO));
        }catch(Exception $e){
            throw($e);
        }
        return $json;
        
    }

    public function createPickUpRequest($id_order, $cron = false){
        $options = get_option( 'dhl_parcel_options' );
        if(!$options['ppoc_fixed_pickup']){
            try{

                $dto = $this->createPickupReqDTO($id_order, $pick_up_request_date);
                $pickup_ws =  new PickUpRequestWSServices($this->getAuthDto(),$this->getTokenCallBack());
                $dto = PickupRequestHelper::fillPickupRequestTimeSlots(
                    $pickup_ws,
                    $dto,
                    new DateTime($options['ppwh_pickup_point_opening_hours']),
                    new DateTime($options['ppwh_pickup_point_closing_hours']),
                    $options['ppoc_same_day_pick_up_hour'],
                    new DateTime(current_time('mysql'))
                );
                if(!$this->isFirstPickUpRequest($dto->pickupDate, $cron)){
                    return;
                }
                error_log("REQUEST SEND: ". json_encode($dto));
                $json = json_decode($pickup_ws->createPickupReq($dto)); 
                error_log("RESPONSE RECEIVED: ". json_encode($json));

                $dbh = new Database_Handler();
                if($cron){
                    $dbh->update_pickup_request($dto->pickupDate, (int)isset($json->id));
                }else{
                    $dbh->insert_pickup_request($id_order, $dto->pickupDate, (int)isset($json->id));
                }
                
            }
            catch(Exception $e){
                throw($e);
            }
        }

    }

    public function getLabelPDF($label_id){
        try{
            $labelWS = new ParcelShopOrderExport($this->getAuthDto(),$this->getTokenCallBack());
            $pdf = $labelWS->getLabel($label_id);

            return $pdf;
        }
        catch(Exception $e){
            throw($e);
        }
    }

    public function getParcelShopPostcode($parcel_shop_id, $country_code){
        try{
            $parcelWS =new ParcelShopLocationWSServices($this->getAuthDto(),$this->getTokenCallBack()); 

            $parcel = json_decode($parcelWS->getParcelShopData($parcel_shop_id,$country_code));
            
            return ($parcel && $parcel->address)?$parcel->address->zipCode:-1;

        }catch(Exception $e){
            throw($e);
        }

    }

    public function getParcelShop($parcel_shop_id, $country_code){
        try{
            $parcelWS =new ParcelShopLocationWSServices($this->getAuthDto(),$this->getTokenCallBack()); 

            $parcel = json_decode($parcelWS->getParcelShopData($parcel_shop_id,$country_code));

            return $parcel;
        
        }catch(Exception $e){
            throw($e);
        }

    }

    public function getServicePointsLocation($params){
        try {        
            $dto = $this->createParcelShopLocQueryDTO($params);

            $ws = new ParcelShopLocationWSServices($this->getAuthDto(),$this->getTokenCallBack());
            $servicepoints=$ws->getParcelLocations($dto);
            
            $servicepoints =  json_decode($servicepoints);
            $i=0;
            foreach($servicepoints as $servicepoint){
                foreach($servicepoint->openingTimes as $time){
                    $servicepoints[$i]->openingTimesByDay[$time->weekDay][]=$time;
                }
                $i++;
            }
            return $servicepoints;
        }catch(Exception $e){
                throw($e);
        }
    }

    public function isDHLParcelShipment($id_order){
        $order = wc_get_order($id_order);
        foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
            if ($shipping_item_obj->get_method_id() == "dhl_service_point_shipping_method" || $shipping_item_obj->get_method_id() == "dhl_normal_shipping_method") {
                return true;
            }
        }
        return false;
    }

    public function getServicePointId($order){
        foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
            if ($shipping_item_obj->get_method_id() == "dhl_service_point_shipping_method") {
                return get_data_from_meta_data($shipping_item_obj->get_meta_data(),'service_point_id');
            }
        }
        return null;
    }

    private function getCarrierRefIdByOrderId($ref){
        $id_carrier = Db::getInstance()->getValue('SELECT `id_reference` FROM `'._DB_PREFIX_.'carrier`
        WHERE id_carrier = '.(int)$ref.' ORDER BY id_reference DESC');

        return $id_carrier;
    }

    private function isFirstPickUpRequest($pickUpDate , $cron = false){
        $pick_up_date = new DateTime($pickUpDate);

        $dbh = new Database_Handler();
        $pickReq = $dbh->get_pickup_request($pick_up_date, $cron);       

        return (count($pickReq) && $cron) || (!count($pickReq) && !$cron);
    }

    private function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }
        else {
            $charid = strtoupper(hash('sha256',uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = ''
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .'';
            return $uuid;
        }
    }

    public function getTrackingInfo($trackingCode, $postcode){
        $ws = new ParcelShopTracker($this->getAuthDto(),$this->getTokenCallBack());

        $track_and_trace = new TrackAndTrace($trackingCode, $postcode);
        $dto = new TrackAndTraceDTO();
        $dto->addTrackAndTrace($track_and_trace);

        return $ws->getTrackAndTrace($dto);
    }

    private function getProductDimensions($order){
        $dimensions = array();
        $parcelType = new ParcelType();
        foreach( $order->get_items() as $item_id => $item_data ){
            for($i = 0; $i < $item_data->get_quantity() ;$i++){
                $product = $item_data->get_product();
                $unformated_product_dimensions = wc_format_dimensions($product->get_dimensions(false));
                if($unformated_product_dimensions == "N/A"){
                    return array();
                }
                $formated_product_dimensions = str_replace("cm","", str_replace(' ', '', $unformated_product_dimensions));
                $formated_product_dimensions = explode("x", $formated_product_dimensions);
                $parcel_dimensions = $parcelType->setDimensionsHelper($formated_product_dimensions[0],$formated_product_dimensions[1],$formated_product_dimensions[2]);
                array_push($dimensions, $parcel_dimensions) ;
            }
        }
        return $dimensions;
    }

    private function getProductDimensionsFromProduct($product){
        $parcelType = new ParcelType();
        $formated_product_dimensions = $product->get_dimensions(false);
        if(!$formated_product_dimensions){
            return array();
        }
        $parcel_dimensions = $parcelType->setDimensionsHelper($formated_product_dimensions['length'],$formated_product_dimensions['width'],$formated_product_dimensions['height']);
        return $parcel_dimensions;        
    }

    //Get the capability based on the cart
    public function getCapability(CapabilitiesListExtd $capabilityList, $cart){
        $dto = $this->createCapabilitiesRequestDTO($cart);
        $ws = new CapabilitiesWS($this->getAuthDto(),$this->getTokenCallBack());
        $options = get_option( 'dhl_parcel_options' );
        $capabilityList = $ws->getCapabilities($dto, $capabilityList, new DateTime($options["ppoc_capabilities_cache_reset_hour"]));

        return $capabilityList;
    }

    private function createCapabilitiesRequestDTO($cart){
        $dto = $this->createEmptyCapabilitiesRequestDTO();
        $customer = $cart->get_customer();

        $options = get_option( 'dhl_parcel_options' );
        $dto->fromCountry = $options['pp_pickup_point_country'];
        $dto->toCountry = $customer->get_shipping_country();

        $parcel_type_model =  new ParcelType();
        foreach($cart->get_cart_contents() as $item_id => $values){
            $_product =  wc_get_product( $values['data']->get_id());
            $dim_array[] = $this->getProductDimensionsFromProduct($_product);
        }

        $dto->parcelType = $parcel_type_model->getParcelType($cart->cart_contents_weight, $dim_array);
        $dto->fromPostalCode = $options['pp_pickup_point_postal_code'];
        $dto->toPostalCode = $customer->get_shipping_postcode();

        $dto->accountNubmer = $options['wsc_account_id'];
        
        return $dto;
    }

    private function createEmptyCapabilitiesRequestDTO(){
        $dto =  new CapabilitiesRequestDTO();
        $dto->referenceTimeStamp= (new DateTime())->format('Y-m-d H:i:s');

        return $dto;
    }

    public function isParcelShippingAvailable($cart, CapabilitiesListExtd $capabilityList = null){
        if(!$capabilityList){
            $capabilityList = new CapabilitiesListExtd();
        }
        $capabilityList = $this->getCapability($capabilityList, $cart);

        return CapabilityChecker::canParcelShip($capabilityList);

    }

    public function isNormalShippingAvailable($cart, CapabilitiesListExtd $capabilityList = null){
        if(!$capabilityList){
            $capabilityList = new CapabilitiesListExtd();
        }
        $capabilityList = $this->getCapability($capabilityList, $cart);
        return CapabilityChecker::canNormalShip($capabilityList);
    }

    public function isCodAvailable($cart, $isServicePointShipping, CapabilitiesListExtd $capabilityList = null){
        if(!$capabilityList){
            $capabilityList = new CapabilitiesListExtd();
        }
        $capabilityList = $this->getCapability($capabilityList, $cart);
        return $isServicePointShipping?CapabilityChecker::hasParcelCOD($capabilityList):CapabilityChecker::hasNormalCOD($capabilityList);
    }

    public function getAvaliableCountries($fromCountryCode, CapabilitiesListExtd $capabilityList = null, $parcelType = null){
        if(!$capabilityList){
            $capabilityList = new CapabilitiesListExtd();
        }
        $dto = $this->createEmptyCapabilitiesRequestDTO();
        $dto->fromCountry = $fromCountryCode;
        $ws = new CapabilitiesWS($this->getAuthDto(),$this->getTokenCallBack());
        $options = get_option( 'dhl_parcel_options' );
        $capabilityList = $ws->getCapabilities($dto, $capabilityList, new DateTime($options["ppoc_capabilities_cache_reset_hour"]));
        $country_list = CapabilityChecker::getCountryList($capabilityList);

        return $country_list;
    }
}

?>