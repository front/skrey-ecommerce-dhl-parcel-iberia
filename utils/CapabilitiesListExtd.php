<?php

include_once plugin_dir_path(__DIR__).'/dependencies/dhl_ws_library/model/CapabilitiesList.php';

class CapabilitiesListExtd extends CapabilitiesList{

    private $capabilitiesMap = array();
    const MAX_CACHE_LIFETIME_HOURS = 24;

    public function getCapability($requestHash){
        return $this->loadCapabilities($requestHash);
    }

    private function loadCapabilities($requestHash){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability";
        $query = "SELECT * FROM $table_name WHERE hash = %s";
        $capabilities = $wpdb->get_results( $wpdb->prepare( $query, array($requestHash) ) );

        $capabilitiesArray = [];

        foreach($capabilities as $capability_entry){
            $capabilitiesArray[]= $this->loadCapability((object)$capability_entry);
        }

        return $capabilitiesArray;
    }

    private function loadCapability($capability_entry){
        $capability = new Capability();
        $capability->rank = $capability_entry->rank;
        $capability->id = $capability_entry->id;
        $capability->fromCountryCode = $capability_entry->fromCountryCode;
        $capability->toCountryCode = $capability_entry->toCountryCode;
        $capability->returnUrl = $capability_entry->returnUrl;
        $capability->timeStamp = $capability_entry->creation_time;

        // Capability Product.
	    $capability->product = wp_cache_get( 'dhl_capability_product' );
	    if (empty($capability->product)) {
		    $capability->product = $this->loadCapabilityProduct( $capability_entry->id );
		    // Cache the value for 24h.
		    wp_cache_set( 'dhl_capability_product', $capability->product, '', 86400 );
	    }

        // Capability Parcel Type.
	    $capability->parcelType = wp_cache_get( 'dhl_capability_parcel_type' );
	    if (empty($capability->parcelType)) {
		    $capability->parcelType = $this->loadCapabilityParcelType( $capability_entry->id );
		    // Cache the value for 24h.
		    wp_cache_set( 'dhl_capability_parcel_type', $capability->parcelType, '', 86400 );
	    }

	    // Capability options.
	    $capability->options = wp_cache_get( 'dhl_capability_options' );
	    if ( empty( $capability->options ) ) {
		    $capability->options = $this->loadCapabilityOptions( $capability_entry->id );
		    // Cache the value for 24h.
		    wp_cache_set( 'dhl_capability_options', $capability->options, '', 86400 );
	    }

        return $capability;
    }

    private function loadCapabilityProduct($capability_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_product";
        $query = "SELECT * FROM $table_name WHERE capability_id = %s";
        $capability_product_entry = $wpdb->get_results( $wpdb->prepare( $query, array($capability_id) ) );

        $capability_product = new CapabilityProduct();

        return $this->buildObject('CapabilityProduct',$capability_product_entry[0]);
    }

    private function loadCapabilityParcelType($capability_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_parcel_type";
        $query = "SELECT * FROM $table_name WHERE capability_id = %s";
        $entry = $wpdb->get_results( $wpdb->prepare( $query, array($capability_id) ) );

        if(!($entry && count($entry))) return false;
        $obj = $this->buildObject('CapabilityParcelType',$entry[0]);

        $entry_first = json_decode(json_encode($entry[0]), true);
        $obj->dimensions =  $this->loadCapabilityParcelTypeDimensions($entry_first['id']);
        $obj->price =  $this->loadCapabilityParcelTypeDimensions($entry_first['price_id']);

        return $obj;
    }

    private function loadCapabilityParcelTypeDimensions($capability_parcel_type_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_parcel_type_dimensions";
        $query = "SELECT * FROM $table_name WHERE parcelType_id = %s";
        $entry = $wpdb->get_results( $wpdb->prepare( $query, array($capability_parcel_type_id) ) );

        if(!($entry && count($entry))) return false;
        $entry_first = json_decode(json_encode($entry[0]), true);
        $obj = $this->buildObject('CapabilityParcelTypeDimensions',$entry_first);
        return $obj;
    }

    private function loadCapabilityPrice($price_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_price";
        $query = "SELECT * FROM $table_name WHERE id = %s";
        $entry = $wpdb->get_results( $wpdb->prepare( $query, array($price_id) ) );

        if(!($entry && count($entry))) return false;
        $entry_first = json_decode(json_encode($entry[0]), true);
        $obj = $this->buildObject('CapabilityPrice',$entry_first);
        return $obj;
    }

    private function loadCapabilityOptions($capability_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_option";
        $query = "SELECT * FROM $table_name WHERE capability_id = %s";
        $entries = $wpdb->get_results( $wpdb->prepare( $query, array($capability_id) ) );

        $options = [];
        if(!($entries && count($entries))) return false;
        foreach($entries as $entry){
            $entry = json_decode(json_encode($entry), true);
            $obj = $this->buildObject('CapabilityOption',$entry);
            $obj->exclusions = $this->loadCapabilityOptionExclusion($entry['id']);
            $obj->price = $this->loadCapabilityPrice($entry['price_id']);
            $options[]= $obj;
        }
        return $options;
    }
    private function loadCapabilityOptionExclusion($capability_option_id){
        global $wpdb;

        $table_name = $wpdb->prefix . "dhl_capability_exclusion";
        $query = "SELECT * FROM $table_name WHERE option_id = %s";
        $entries = $wpdb->get_results( $wpdb->prepare( $query, array($capability_option_id) ) );

        $exclusions = [];
        if(!($entries && count($entries))) return false;
        foreach($entries as $entry){
            $entry = json_decode(json_encode($entry), true);
            $obj = $this->buildObject('CapabilityExclusion',$entry);;
            $exclusions[]= $obj;
        }
        return $exclusions;
    }

    public  function saveCapability(CapabilityLine $capabilityLine, $flushCache = false, CapabilityLine $cached_capabilityLine = null){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability";
        if($flushCache && $cached_capabilityLine) $this->deteleCapability($cached_capabilityLine);
        foreach($capabilityLine->getCapabilities() as $capability){
            $resp = $wpdb->insert(
                $table_name,
                array(
                    'hash'=> $capabilityLine->getHash(),
                        'creation_time' =>$capabilityLine->getCreationTimestamp(),
                        'rank'=>$capability->rank,
                        'fromCountryCode'=> $capability->fromCountryCode,
                        'toCountryCode' => $capability->toCountryCode,
                        'returnUrl' => $capability->returnUrl,
                        'creation_time' => $capabilityLine->getCreationTimestamp()
                )
            );
            if(!$resp) return;
            $capability_id = $wpdb->insert_id;
            $this->saveCapabilityProduct($capability->product, $capability_id );
            $this->saveCapabilityParcelType($capability->parcelType, $capability_id);
            $this->saveCapabilityOptions($capability->options, $capability_id);
        }
    }

    private function saveCapabilityProduct(CapabilityProduct $product, $capability_id){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_product";
        $resp = $wpdb->insert(
            $table_name,
            array(
                'capability_id'=> $capability_id,
                'key' => $product->key,
                'label'=> $product->label,
                'businessProduct'=> $product->businessProduct,
                'monoColloProduct' => $product->monoColloProduct,
                'softwareCharacteristic' => $product->softwareCharacteristic,
                'returnProduct' => $product->returnProduct,
                'code' => $product->code,
                'menuCode' => $product->menuCode
            )
        );
        if(!$resp) return false;
        return $wpdb->insert_id;
    }

    private function saveCapabilityParcelType (CapabilityParcelType $capabilityParcelType, $capability_id){
        $price_id = $this->saveCapabilityPrice($capabilityParcelType->price);
        if(!$price_id)return false;
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_parcel_type";
        $resp = $wpdb->insert(
            $table_name,
            array(
                'capability_id'=> $capability_id,
                'price_id' => $price_id,
                'key' => $capabilityParcelType->key,
                'minWeightKg'=> $capabilityParcelType->minWeightKg,
                'maxWeightKg'=> $capabilityParcelType->maxWeightKg
            )
        );
        if(!$resp) return false;
        $capability_parcel_type_id = $wpdb->insert_id;
        return $this->saveCapabilityParcelTypeDimensions($capabilityParcelType->dimensions, $capability_parcel_type_id);
    }

    private function saveCapabilityParcelTypeDimensions(CapabilityParcelTypeDimensions $capabilityParcelTypeDimension, $capability_parcel_type_id){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_parcel_type_dimensions";
        $resp = $wpdb->insert(
            $table_name,
            array(
                'parcelType_id'=> $capability_parcel_type_id,
                'maxLengthCm' => $capabilityParcelTypeDimension->maxLengthCm,
                'maxWidthCm' => $capabilityParcelTypeDimension->maxWidthCm,
                'maxHeightCm'=> $capabilityParcelTypeDimension->maxHeightCm,
            )
        );
        if(!$resp) return false;
        $capability_parcel_type_id = $wpdb->insert_id;
    }

    private function saveCapabilityPrice(CapabilityPrice $capabilityPrice){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_price";
        $resp = $wpdb->insert(
            $table_name,
            array(
                'withTax'=> ($capabilityPrice->withTax == null)? 0: $capabilityPrice->withTax,
                'withoutTax' => ($capabilityPrice->withTax == null)? 0: $capabilityPrice->withoutTax,
                'vatRate'=> ($capabilityPrice->withTax == null)? 0: $capabilityPrice->vatRate,
                'currency'=> ($capabilityPrice->withTax == null)? 0: $capabilityPrice->currency
            )
        );
        if(!$resp) return false;

        return $wpdb->insert_id;
    }

    private function saveCapabilityOptions($capabilityOptionsArray, $capability_id){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_option";
        foreach($capabilityOptionsArray as $opt){
            $price_id = $this->saveCapabilityPrice($opt->price);
            if(!$price_id) return false;
            $resp = $wpdb->insert(
                $table_name,
                array(
                    'capability_id'=> $capability_id,
                    'price_id' => $price_id,
                    'key'=> $opt->key,
                    'optionType'=> $opt->optionType,
                    'description'=> $opt->description,
                    'rank'=> $opt->rank,
                    'code'=> $opt->code,
                    'inputType'=> $opt->inputType
                )
            );
            if(!$resp)
                return false;
            if(!$this->saveExclusions($opt->exclusions,$wpdb->insert_id))
                return false;
        }
        return true;
    }

    private function saveExclusions($capabilityExclusionsArray, $option_id){
        global $wpdb;
        $table_name = $wpdb->prefix . "dhl_capability_exclusion";
        foreach($capabilityExclusionsArray as $excl){
            $resp = $wpdb->insert(
                $table_name,
                array(
                    'option_id'=> $option_id,
                    'key'=> $excl->key,
                    'optionType'=> $excl->optionType,
                    'rank'=> $excl->rank,
                    'code'=> $excl->code
                )
            );
            if(!$resp)
                return false;
        }
        return true;
    }

    private function deteleCapability(CapabilityLine $cached_capabilityLine){
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'dhl_capability_product', 'id IN('.implode(",",$this->getProductIds($cached_capabilityLine)).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability_price', 'id IN('.implode(",",array_merge($this->getOptionPricesIds($cached_capabilityLine),$this->getParcelTypePriceIds($cached_capabilityLine))).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability_parcel_type_dimensions', 'id IN('.implode(",",$this->getParcelTypeDimensionsIds($cached_capabilityLine)).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability_parcel_type', 'id IN('.implode(",",$this->getParcelTypeIds($cached_capabilityLine)).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability_exclusion', 'id IN('.implode(",",$this->getOptionExclusionsIds($cached_capabilityLine)).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability_option', 'id IN('.implode(",",$this->getOptionIds($cached_capabilityLine)).')');
        $wpdb->delete($wpdb->prefix . 'dhl_capability', 'id IN('.implode(",",$this->getCapabilityIds($cached_capabilityLine)).')');
    }

    private function getCapabilityIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return (int)$c->id;}, $capabilityLine->getCapabilities()));
    }
    private function getProductIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return (int)$c->product->id;}, $capabilityLine->getCapabilities()));
    }
    private function getParcelTypePriceIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return $c->parcelType->price?(int)$c->parcelType->price->id:0;}, $capabilityLine->getCapabilities()));
    }
    private function getParcelTypeIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return (int)$c->parcelType->id;}, $capabilityLine->getCapabilities()));
    }
    private function getParcelTypeDimensionsIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return $c->parcelType->dimensions?(int)$c->parcelType->dimensions->id:0;}, $capabilityLine->getCapabilities()));
    }
    private function getOptionIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return $c->options?implode(",",array_map(function($o){return (int)$o->id;},$c->options)):[];}, $capabilityLine->getCapabilities()));
    }
    private function getOptionExclusionsIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return $c->options?implode(",",array_map(function($o){return $o->exclusions?implode(",",array_map(function($e){return (int)$e->id;},$o->exclusions)):0;},$c->options)):[];}, $capabilityLine->getCapabilities()));
    }
    private function getOptionPricesIds(CapabilityLine $capabilityLine){
        return array_unique(array_map(function($c){return $c->options?implode(",",array_map(function($o){return $o->price?(int)$o->price->id:0;},$c->options)):[];}, $capabilityLine->getCapabilities()));
    }

    private function buildObject($class,$data){
        $object = new $class;
        foreach($data as $key=>$value){
            if(property_exists($class,$key)){
                $object->{$key}= $value;
            }
        }
        return $object;
    }
}
