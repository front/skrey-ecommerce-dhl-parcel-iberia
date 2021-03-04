<?php

class AuthDTO{

    public $accessToken;

    public $userId;

    public $key;


}
class ParcelShopLocQueryDTO{

    public $zipCode;

    public $street;

    public $city;

    public $countryCode;

    //search query
    public $q;

}

class NameDTO{

    public $firstName = '';
    public $lastName = '';
    public $companyName = '';
    public $additionalName = '';

}

class AddressDTO{

    public $countryCode;
    public $postalCode;
    public $city;
    //Max 40 chars
    public $street;
    public $number = '';
    public $isBusiness = false;
    //Max 40 chars - Address second line field
    public $addition = '';
}

class Dhl_receiver_shipperDTO{

    /** @var NameDTO */
    public $name;
    /** @var AddressDTO */
    public $address;
    public $email;
    public $phoneNumber;

    //so se for o shipper
    public $vatNumber;

}

class LabelCreationDTO{

	const COD_CASH = 'COD_CASH';
    const COD_CHECK = 'COD_CHECK';

    public $key;

    public $input;

    public $labelId;

    public $labelFormat;

    public $orderReference;

    public $parcelTypeKey;

    /** @var Dhl_receiver_shipperDTO */
    public $receiver;

    /** @var Dhl_receiver_shipperDTO */
    public $shipper;
    
    public $accountId;

    public $options = [];
    
    public $returnLabel = false;

    public $pieceNumber = 1;

    public $quantity = 1;

    public $automaticPrintDialog = true;

    public $application;

    public $weight;

    public function setServicePoint($servicePointId){
        $this->options[]=array('key' => 'PS', 'input' => $servicePointId);
    }
    
    public function setCashOnDelivery($codType, $amountToBePaid) {
        $this->options[]=array('key' => $codType, 'input' => $amountToBePaid);
    }
    
    public function setReference($reference) {
        $this->options[]=array('key' => 'REFERENCE', 'input' => $reference);
    }

}
class FTPFileDTO{

    public $file_number;
    public $pickup_depot_code;
    public $client_number;

    public $pick_up_date;

    public $pick_up_company_name;
    public $pick_up_contact_person;
    public $pick_up_street;
    public $pick_up_house_number;
    public $pick_up_additional_address_line;
    public $pick_up_city;
    public $pick_up_postal_code;
    /**
     * PT / ES
     */
    public $pick_up_country_code;
    
    /**
     * Sem indicadores
     */
    public $pick_up_telephone_number;
    public $pick_up_email;
    
    public $pick_up_number_of_parcels = 1;
    public $pick_up_number_of_pallets;
    
    /**
     * Exemplos: Receção, Portaria, Armazem
     */
    public $parcel_location;
    public $pick_up_remarks;
    
    /**
     * 800 - B2B
     * 801 - B2C
     * 800 - Ambos os casos
     */
    public $product_key = 801;

    /**
     * update: 12.06.17: Leave the field in blank by now
     */
    public $license_plate;
    

    /**
     * yyyymmdd
     */
    public $generation_file_date;

    /**
     * hhmm
     */
    public $pick_up_morning_time_from = '0000';
    public $pick_up_morning_time_to = '0000';
    public $pick_up_evening_time_from = '0000';
    public $pick_up_evening_time_to = '0000';


    public $total_weight = 0;
}

class TrackAndTraceDTO {

    private $_track_and_trace_items = array();

    public function __construct() {}

    public function getTrackAndTrace() { return $this->_track_and_trace_items; }
    public function addTrackAndTrace(TrackAndTrace $track_and_trace) { $this->_track_and_trace_items[] = $track_and_trace; }
    public function resetTrackAndTrace() { $this->_track_and_trace_items = array(); }
    
    public function toArray() {
        $key_parts = array();
        foreach ($this->_track_and_trace_items as $track_and_trace) {
            $key_parts[] = $track_and_trace->getTrackingCode().(!is_null($track_and_trace->getPostcode())?'+'.$track_and_trace->getPostcode():'');
        }
        return array('key' => implode(',',$key_parts));
    }
}

class PickupRequestTimeSlotDTO{
    
    public $from;

    public $to;
}

class PickupRequestShipperEmailDTO{

    /** @var AddressDTO */
    public $address;

}

class PickupRequestShipperDTO{

    /** @var NameDTO */
    public $name;
    /** @var AddressDTO */
    public $address;
    /** @var PickupRequestShipperEmailDTO */
    public $email;
    public $phoneNumber;

}
class PickupRequestDTO{

    public $userId;

    public $accountId;

    public $pickupDate;

    public $description;

    public $pickupLocation;

    public $numberOfPackages;
    
    public $numberOfPallets;

    public $totalWeight;
    
    /** @var PickupRequestShipperDTO */
    public $shipper;
    
    /** @var PickupRequestShipperDTO */
    //public $receiver;
    
    /** @var PickupRequestTimeSlotDTO */
    public $timeSlot;
    
    public $type;
    
    public $provideLabels;    
}

class PickupRequestCheckDTO{

    public $countryCode;

    public $postalCode;

}

/** Capabilities */

abstract class CapablitiesSenderType
{
    const Business = 'business';
    const Consumer = 'consumer';
    const ParcelShop = 'parcelShop';
}
abstract class CapablitiesCarriers
{
    const ParcelShop = 'DHL-PARCEL';
    //const Express = 'DHL-EXPRESS';
}
class CapabilitiesRequestDTO{

    public $senderType = CapablitiesSenderType::Business;
    public $fromCountry = 'PT';
    public $toCountry;
    public $toBusiness='false';
    public $returnProduct='false';
    public $parcelType;
    public $option = [];
    public $fromPostalCode;
    public $toPostalCode;
    public $toCity;
    public $accountNubmer;
    /**The Id of the organization */
    public $oganizationId;
    /**The code of the business unit */
    public $businessUnit;
    public $carrier = CapablitiesCarriers::ParcelShop;
    /**Date for determining the capabilities */
    public $referenceTimeStamp;


    public function getSenderType(){
        return $this->senderType;
    }
    public function getCarrier(){
        return $this->carrier;
    }

    public function setSenderTypeBusiness(){
        $this->senderType = CapablitiesSenderType::Business;
    }
    /*public function setSenderTypeConsumer(){
        $this->senderType = CapablitiesSenderType::Consumer;
    }
    public function setSenderTypeParcelShop(){
        $this->referenceTimeStamp = null;
        $this->senderType = CapablitiesSenderType::ParcelShop;
    }
    public function setCarrierExpress(){
        $this->carrier = CapablitiesCarriers::Express;
    }*/
    public function setCarrierParcel(){
        $this->carrier = CapablitiesCarriers::ParcelShop;
    }

    public function hash(){
        return hash("md5", serialize($this));
    }

}

/** Capabilities END */

