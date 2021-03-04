<?php
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}

/**
 * Rules that normalize the postcode depending on the countryId
 * @param countryID ISO country id
 * @param postcode unormalized_postcode
 * @return normalized_postcode returns the normalized_postcode
 */
function postcode_normalizer($countryId, $postcode) {

    if(is_null($postcode)){
        return null;
    }
    if( !strcmp($countryId,"PT")){
        $pattern = "([0-9]{4})";
        preg_match($pattern, $postcode, $matches);
        return $matches[0];
    } else {
        return $postcode;
    }
}

function array_flatten($array) { 
    if (!is_array($array)) { 
      return false; 
    } 
    $result = array(); 
    foreach ($array as $key => $value) { 
      if (is_array($value)) { 
        $result = array_merge($result, array_flatten($value)); 
      } else { 
        $result[$key] = $value; 
      } 
    } 
    return $result; 
  }

function get_data_from_meta_data($metadata, $key){
    
    foreach( $metadata as $meta ){
        $data = $meta->get_data();
        if($data['key']==$key){
            $result = $data['value'];
        }
    }

    return $result;

}

// Cast all stdClasses in an array to arrays
function arrayCastRecursive($array)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = arrayCastRecursive($value);
            }
            if ($value instanceof stdClass) {
                $array[$key] = arrayCastRecursive((array)$value);
            }
        }
    }
    if ($array instanceof stdClass) {
        return arrayCastRecursive((array)$array);
    }
    return $array;
}

/*
 * Removes snake case/CAPS LOCK and outputs a proper readable string
*/
function formatStringToReadableString($stringToTransform){
   
    $newString = strtolower($stringToTransform);
    $newString = str_replace('_', ' ', ucwords($newString, ' '));

    return $newString;
}

?>