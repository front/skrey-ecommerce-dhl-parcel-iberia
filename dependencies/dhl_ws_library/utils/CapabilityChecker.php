<?php

class CapabilityChecker{


    const OPT_PARCEL_SHIP_KEY = 'PS';
    const OPT_NORMAL_SHIP_KEY = 'DOOR';

    const OPT_COD_CASH = 'COD_CASH';
    const OPT_COD_CHECK = 'COD_CHECK';

    public static function canParcelShip(CapabilityLine $cLine){
        return self::hasCapabilityWithOption($cLine, self::OPT_PARCEL_SHIP_KEY);
    }
    public static function canNormalShip(CapabilityLine $cLine){
        return self::hasCapabilityWithOption($cLine, self::OPT_NORMAL_SHIP_KEY);
    }
    public static function canShipWeightAndDimension(CapabilityLine $cLine, array $dimensiosArray, $weightKg){
        return self::canShipDimension($cLine, $dimensiosArray) && self::canShipWeight($cLine, $weightKg);
    }
    public static function canShipWeight(CapabilityLine $cLine, $weightKg){
        foreach($cLine->capabilities as $capability){
            if($capability->parcelType->maxWeightKg >= $weightKg){
                return true;
            }
        }
    }
    public static function canShipDimension(CapabilityLine $cLine, array $dimensiosArray){
        foreach($cLine->capabilities as $capability){
            $capabilityDimArray = array(
                    $capability->dimensions->maxLengthCm,
                    $capability->dimensions->maxWidthCm,
                    $capability->dimensions->maxHeightCm);
            $keep = true;
            $hold_dhl_dims=$capabilityDimArray;
            $hold_min_dimensions = $dimensiosArray;
            while($keep){
                if(!sizeof($hold_dhl_dims)>0) return $true;
                $keep = max($hold_dhl_dims) >= max($min_dimensions)? true:false;
                if(!$keep) break;
                unset($hold_dhl_dims[array_search(max($hold_dhl_dims), $hold_dhl_dims)]);
                unset($hold_min_dimensions[array_search(max($hold_min_dimensions), $hold_min_dimensions)]);
            }
        }
    }
    public static function hasParcelCOD(CapabilityLine $cLine){
        return self::hasCapabilityWithOptionsNoExclusion($cLine, self::OPT_COD_CASH, self::OPT_PARCEL_SHIP_KEY);
    }
    public static function hasNormalCOD(CapabilityLine $cLine){
        return self::hasCapabilityWithOptionsNoExclusion($cLine, self::OPT_COD_CASH, self::OPT_NORMAL_SHIP_KEY);
    }
    public static function getCountryList(CapabilityLine $cLine){
        $country_list= [];
        foreach($cLine->getCapabilities() as $capability){
            $country_list[] = $capability->toCountryCode; 
        }
        return array_unique($country_list);
    }
    public static function getCountryListParcel(CapabilityLine $cLine){
        return array_unique(self::getCountryListWithOption($cLine, array(self::OPT_PARCEL_SHIP_KEY)));
    }
    public static function getCountryListNormal(CapabilityLine $cLine){
        return array_unique(self::getCountryListWithOption($cLine, array(self::OPT_NORMAL_SHIP_KEY)));
    }

    private static function getCountryListWithOption(CapabilityLine $cLine, array $opts){
        $country_list= [];
        foreach($cLine->getCapabilities() as $capability){
            if (self::capabilityHasOption($capability, $opts)) $country_list[] = $capability->toCountryCode; 
        }
        return array_unique($country_list);
    }
    private static function hasCapabilityWithOption(CapabilityLine $cLine, $key){
        return self::hasCapabilityWithOptions($cLine, array($key));
    }
    private static function hasCapabilityWithOptions(CapabilityLine $cLine, array $keys){
        return self::hasCapabilityWithOptionsNoExclusions($cLine, $keys, []);
    }
    private static function hasCapabilityWithOptionsNoExclusion(CapabilityLine $cLine, $key, $noExclusion){
        return self::hasCapabilityWithOptionsNoExclusions($cLine, array($key), array($noExclusion));
    }
    private static function hasCapabilityWithOptionsNoExclusions(CapabilityLine $cLine, array $optionKeys, array $notExclusionsKeys){
        foreach($cLine->getCapabilities() as $capability){
            if(self::capabilityHasOptionsNoExclusions($capability, $optionKeys, $notExclusionsKeys)){
                return true;
            }
        }
        return false;
    }
    private static function capabilityHasOption(Capability $capability, array $optionKeys){
        return self::capabilityHasOptionsNoExclusions($capability, $optionKeys, array());
    }
    private static function capabilityHasOptionsNoExclusions(Capability $capability, array $optionKeys, array $notExclusionsKeys){
        $i = 0;
        $arrayOptionsInitialSize = count($optionKeys);
        $iterations_left = count($capability->options);
        foreach($capability->options as $option){
            if($arrayOptionsInitialSize == 0 ||$iterations_left-- < $arrayOptionsInitialSize ) break;
            if(in_array($option->key, $optionKeys)){ 
                $i++; $arrayOptionsInitialSize--;
                if(count($notExclusionsKeys))foreach($option->exclusions as $exclusion){
                    if(in_array($exclusion->key, $notExclusionsKeys ) ){$i--; $arrayOptionsInitialSize++; break;}
                }
            }
        }
        return $i == count($optionKeys);
    }
}