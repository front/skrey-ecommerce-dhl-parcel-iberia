<?php

require_once dirname(dirname(__FILE__))."/utils/Packer.php";

class ParcelType {

    private $_weight_rules;
    private $_dimension_rules;

    public function __construct() {
        $this->setRules();
    }

    /**
     * Returns the parcel type, if no dimensions is given it uses only the weight
     * @param total_weight total weight of the products
     * @param dimensions array of arrays containg dimensions for each product (Note: you can use setDimensionsHelper for the rigth format)
     * @return response parcel type or null if no parcel type is aplicable
     */
    public function getParcelType($total_weight, $dimensions = array()){
        $dimensions = apply_filters( 'dhl_get_order_dimensions', $dimensions, $total_weight );

        $this->_weight_rules    = apply_filters( 'dhl_set_weight_rules', $this->_weight_rules );
        $this->_dimension_rules = apply_filters( 'dhl_set_dimension_rules', $this->_dimension_rules );

        //Get minimum dimensions needed
        if(!empty($dimensions)) {
            $packer = new Packer($dimensions);
            $packer->pack();
            $min_dimensions = $packer->get_container_dimensions();
        }
        //Check by weight first
        foreach( $this->_weight_rules as $rule => $limits){
            if($limits["max"] >= $total_weight ){
                //No dimensions to calculate
                if(empty($dimensions)) {
                    return $rule;
                } else {
                    $keep = true;
                    $hold_dhl_dims=$this->_dimension_rules[$rule]['max'];
                    $hold_min_dimensions = $min_dimensions;
                    while($keep){
                        if(!sizeof($hold_dhl_dims)>0) return $rule;
                        $keep = max($hold_dhl_dims) >= max($hold_min_dimensions)? true:false;
                        if(!$keep) break;
                        unset($hold_dhl_dims[array_search(max($hold_dhl_dims), $hold_dhl_dims)]);
                        unset($hold_min_dimensions[array_search(max($hold_min_dimensions), $hold_min_dimensions)]);
                    }


                }
            }
        }
        return null;
    }

    /**
     * Helper function when defining the dimensions array
     * @return dimensions array with dimensions in the correct format
     */
    public function setDimensionsHelper($length, $width, $height){
        return array(
            "length" => (double)$length,
            "width" => (double)$width,
            "height" => (double)$height
        );
    }

    private function setRules(){
        $this->_weight_rules = array(
            "SMALL" => array(
                "min" => 0,
                "max" => 5.99
            ),
            "MEDIUM" => array(
                "min" => 6,
                "max" => 15.99
            ),
            "LARGE" => array(
                "min" => 16.00,
                "max" => 31.49
            ),
            "BULKY" => array(
                "min" => 31.50,
                "max" => 9999999999
            )
        );
        $this->_dimension_rules = array(
            "SMALL" => array(
                "min" => array(
                    "length" => 0,
                    "width" => 0,
                    "height" => 0
                ),
                "max" => array(
                    "length" => 25,
                    "width" => 20,
                    "height" => 5
                )
            ),
            "MEDIUM" => array(
                "min" => array(
                    "length" => 25,
                    "width" => 20,
                    "height" => 5
                ),
                "max" => array(
                    "length" => 60,
                    "width" => 50,
                    "height" => 25
                )
            ),
            "LARGE" => array(
                "min" => array(
                    "length" => 60,
                    "width" => 50,
                    "height" => 25
                ),
                "max" => array(
                    "length" => 120,
                    "width" => 60,
                    "height" => 60
                )
            ),
            "BULKY" => array(
                "min" => array(
                    "length" => 120,
                    "width" => 60,
                    "height" => 60
                ),
                "max" => array(
                    "length" => 200,
                    "width" => 120,
                    "height" => 80
                )
            )
        );
    }

}
