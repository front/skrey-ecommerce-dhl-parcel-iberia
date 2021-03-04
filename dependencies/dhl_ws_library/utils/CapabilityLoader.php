<?php


require_once dirname(dirname(__FILE__))."/model/Capabilities/Capability.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityExclusion.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityOption.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityParcelType.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityParcelTypeDimensions.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityPrice.php";
require_once dirname(dirname(__FILE__))."/model/Capabilities/CapabilityProduct.php";
class CapabilityLoader {
    
    const ALLOWED_PRODUCT_KEYS = ['CON','IBERIA'];

    public static function loadCapability($capabilityStdJSon){
        if(!in_array($capabilityStdJSon->product->key, self::ALLOWED_PRODUCT_KEYS)){
            return false;
        }
        $capability =  new Capability();
        $capability->rank = $capabilityStdJSon->rank;
        $capability->fromCountryCode = $capabilityStdJSon->fromCountryCode;
        $capability->toCountryCode = $capabilityStdJSon->toCountryCode;
        if(isset($capabilityStdJSon->returnUrl)) $capability->returnUrl = $capabilityStdJSon->returnUrl;

        $capability->product = self::loadProduct($capability, $capabilityStdJSon);
        $capability->parcelType = self::loadParcelType($capabilityStdJSon->parcelType);
        $capability->options =  self::loadOptions($capabilityStdJSon->options);

        return $capability;
    }
    private static function loadProduct($capability, $capabilityStdJSon){
        $product = new CapabilityProduct();
        $product->key = $capabilityStdJSon->product->key;
        $product->label = $capabilityStdJSon->product->label;
        $product->code= $capabilityStdJSon->product->code;
        $product->menuCode= $capabilityStdJSon->product->menuCode;
        $product->businessProduct= $capabilityStdJSon->product->businessProduct;
        $product->monoColloProduct= $capabilityStdJSon->product->monoColloProduct;
        $product->softwareCharacteristic= $capabilityStdJSon->product->softwareCharacteristic;
        $product->returnProduct= $capabilityStdJSon->product->returnProduct;

        return $product;
    }
    private static function loadParcelType( $parcelTypeJson){
        $parcelType = new CapabilityParcelType();
        $parcelType->key = $parcelTypeJson->key;
        $parcelType->minWeightKg = $parcelTypeJson->minWeightKg;
        $parcelType->maxWeightKg = $parcelTypeJson->maxWeightKg;
        $parcelType->dimensions = self::loadParcelTypeDimensions($parcelTypeJson->dimensions);
        $parcelType->price = isset($parcelTypeJson->price ) ? self::loadPrice($parcelTypeJson->price) : new CapabilityPrice();
        
        return $parcelType;
    }
    private static function loadExclusions($exclusionsArray){
        $exclusions = [];

        foreach($exclusionsArray as $excl){
            $exclusion = new CapabilityExclusion();
            $exclusion->key = $excl->key ;
            $exclusion->rank = $excl->rank ;
            $exclusion->code = $excl->code ;
            $exclusion->optionType = $excl->optionType ;
            
            $exclusions[]= $exclusion;
        }

        return $exclusions;
    }
    private static function loadOptions($optionsArray){
        $options = [];
        
        foreach($optionsArray as $opt){
            $option =  new CapabilityOption();
            $option->key            = $opt->key;
            $option->description    = $opt->description;
            $option->rank           = $opt->rank;
            $option->code           = $opt->code;
            $option->optionType     = $opt->optionType;
            $option->price          = isset($opt->price )? self::loadPrice( $opt->price) : new CapabilityPrice();
            $option->inputType      = isset($opt->inputType) ? $opt->inputType : null;
            $option->exclusions     = isset($opt->exclusions) ? self::loadExclusions($opt->exclusions) : self::loadExclusions(array());
            
            $options[] = $option;
        }

        return $options;
    }
    private static function loadParcelTypeDimensions( $parcelTypeDimJson){
        $parcelTypeDimensions =  new CapabilityParcelTypeDimensions();

        $parcelTypeDimensions->maxLengthCm  = $parcelTypeDimJson->maxLengthCm;
        $parcelTypeDimensions->maxWidthCm   = $parcelTypeDimJson->maxWidthCm ;
        $parcelTypeDimensions->maxHeightCm  = $parcelTypeDimJson->maxHeightCm;
        

        return $parcelTypeDimensions;
    }
    private static function loadPrice($priceJson){
        $price = new CapabilityPrice();

        $price->withTax = ($priceJson->withTax == null)? 0: $priceJson->withTax;
        $price->withoutTax = ($priceJson->withoutTax == null)? 0: $priceJson->withoutTax;
        $price->vatRate = ($priceJson->vatRate == null)? 0: $priceJson->vatRate;
        $price->currency = ($priceJson->currency == null)? 0: $priceJson->currency;

        return $price;
    }
}