<?php


require_once('utils.php');

class Database_Handler {

    const SHIPPING_RULES_TABLE_NAME = "dhl_parcel_shipping_rules_data";
    const LABELS_TABLE_NAME = "dhl_parcel_labels_data";
    const LABELS_PICKUP_REQUEST_NAME = "dhl_parcel_pick_up_order_request";

    /**
     * Creates database table
     */
    public function create_shipping_rules_table() {
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(12) NOT NULL AUTO_INCREMENT,
            countryId varchar(10) DEFAULT '' NOT NULL,
            minPostCode varchar(25),
            maxPostCode varchar(25),
            min double NOT NULL,
            max double NOT NULL,
            ruleCriteria varchar(15) NOT NULL,
            cost double NOT NULL,
            shippingMethod varchar(55) NOT NULL,
            shippingZoneId bigint(12) NOT NULL,
            PRIMARY KEY  (id)
          ) $charset_collate;";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          dbDelta( $sql );
    }

    public function update_plugin($current_version){
        $final_version = $current_version;
        global $wpdb;
        if($current_version < '1.0.14'){
            $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;
            $sql = "ALTER TABLE $table_name
                    CHANGE COLUMN `min` `min` DOUBLE NOT NULL ,
                    CHANGE COLUMN `max` `max` DOUBLE NOT NULL ;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query($sql);
            $final_version = '1.0.14';
        }
        $options = get_option( 'dhl_parcel_options' );
        $options['dhl_parcel_db_version'] = $final_version;
        update_option('dhl_parcel_options', $options);
    }

    /**
     * Creates database table
     */
    public function create_labels_table() {
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_TABLE_NAME;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(12) NOT NULL AUTO_INCREMENT,
            label_id varchar(45) NOT NULL,
            is_return_label boolean NOT NULL,
            order_id varchar(45) NOT NULL,
            creation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            tracking_code varchar(45) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
          ) $charset_collate;";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          dbDelta( $sql );
    }

    /**
     * Creates database table
     */
    public function create_pickup_request_table() {
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_PICKUP_REQUEST_NAME;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(12) NOT NULL AUTO_INCREMENT,
            order_id varchar(45) NOT NULL,
            creation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            pick_up_date DATETIME NOT NULL,
            success boolean NOT NULL,
            PRIMARY KEY  (id)
          ) $charset_collate;";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          dbDelta( $sql );
    }

    /**
     * Inserts/Update a shipping label into the database
     */
    public function insert_labels( $label_id, $is_return_label, $order_id, $tracking_code) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_TABLE_NAME;

        $wpdb->replace(
            $table_name,
            array(
                'label_id' => $label_id,
                'is_return_label' => $is_return_label,
                'order_id' => $order_id,
                'tracking_code' => $tracking_code,
            )
        );
        return $wpdb->insert_id;
    }

    /**
     * Retrives labels based on order_id
     */
    public function get_labels_by_order_id($order_id, $is_return_label){

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_TABLE_NAME;
        $query = "SELECT * FROM $table_name WHERE order_id = %s AND is_return_label = %d";
        $results = $wpdb->get_results( $wpdb->prepare( $query, array($order_id, (int)$is_return_label) ) );


        return  $results[0];
    }

    public function get_labels_by_date($date){

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_TABLE_NAME;
        $query = "SELECT * FROM $table_name WHERE creation_date = %s";
        $results = $wpdb->get_results( $wpdb->prepare( $query, $date ) );

        if( count($results)==0 ){
            $first_of_the_day = true;
        } else {
            $first_of_the_day = $results[0];
        }

        return $first_of_the_day;
    }

    /**
     * Inserts/Update a shipping cost into the database
     */
    public function insert_pickup_request( $order_id, $pick_up_request_date, $success) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_PICKUP_REQUEST_NAME;

        $wpdb->replace(
            $table_name,
            array(
                'order_id'=>$order_id,
                'pick_up_date'=> $pick_up_request_date,
                'creation_date'=> (new DateTime())->format('Y-m-d H-i-s'),
                'success' => (int)$success
            )
        );
        return $wpdb->insert_id;
    }

    public function update_pickup_request( $pick_up_request_date, $success) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_PICKUP_REQUEST_NAME;

        $return = $wpdb->update(
            $table_name,
            array(
                'success' => (int)$success
            ),
            array( 'pick_up_date = '.$pick_up_request_date )
        );

        return $return;
    }


    public function get_pickup_request($pick_up_date, $cron){
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::LABELS_PICKUP_REQUEST_NAME;

        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE pick_up_date = %s AND success = %d", $pick_up_date->format('Y-m-d'), (int) !$cron );

        $results = $wpdb->get_results( $query );

        return $results;
    }

    /**
     * Insert into database the shipping rule, given the rule data and the intance_id of the shipping_method
     * @param data array with instance_id and shipping rule
     */
    public function saveShippingRule($shipping_method ,$row, $locations, $shippingZoneId){
        $row['min'] = str_replace(',', '.', $row['min']);
        $row['max'] = str_replace(',', '.', $row['max']);
        $row['cost'] = str_replace(',', '.', $row['cost']);
        $rule = array(
            'id' => $row['id'],
            'countryId' => 'PT',
            'minPostCode' => null,
            'maxPostCode' => null,
            'min' => doubleval($row['min']),
            'max' => doubleval($row['max']),
            'ruleCriteria' => $row['ruleCriteria'],
            'cost' => $row['cost'],
            'shippingMethod' => $shipping_method,
            'shippingZoneId' => $shippingZoneId,
        );
        $row_id= $this->insert_into_shipping_prices($rule);

        return $row_id;
    }

    /**
     * Inserts/Update a shipping cost into the database
     */
    private function insert_into_shipping_prices($shipping_price_rule) {

        global $wpdb;
        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        if($this->validate_object_for_database($shipping_price_rule)){
            if($shipping_price_rule['id']== 0){
                $wpdb->insert(
                    $table_name,
                    array(
                        'countryId' => $shipping_price_rule['countryId'],
                        'minPostCode' => dhl_parcel_postcode_normalizer($shipping_price_rule['countryId'],$shipping_price_rule['minPostCode'] ),
                        'maxPostCode' => dhl_parcel_postcode_normalizer($shipping_price_rule['countryId'],$shipping_price_rule['maxPostCode'] ),
                        'min' => doubleval($shipping_price_rule['min']),
                        'max' => doubleval($shipping_price_rule['max']),
                        'ruleCriteria' => $shipping_price_rule['ruleCriteria'],
                        'cost' => $shipping_price_rule['cost'],
                        'shippingMethod' => $shipping_price_rule['shippingMethod'],
                        'shippingZoneId' => $shipping_price_rule['shippingZoneId'],
                    )
                );
                return $wpdb->insert_id;
            } else {
                $wpdb->replace(
                    $table_name,
                    array(
                        'id' => $shipping_price_rule['id'],
                        'countryId' => $shipping_price_rule['countryId'],
                        'minPostCode' => dhl_parcel_postcode_normalizer($shipping_price_rule['countryId'],$shipping_price_rule['minPostCode'] ),
                        'maxPostCode' => dhl_parcel_postcode_normalizer($shipping_price_rule['countryId'],$shipping_price_rule['maxPostCode'] ),
                        'min' => doubleval($shipping_price_rule['min']),
                        'max' => doubleval($shipping_price_rule['max']),
                        'ruleCriteria' => $shipping_price_rule['ruleCriteria'],
                        'cost' => $shipping_price_rule['cost'],
                        'shippingMethod' => $shipping_price_rule['shippingMethod'],
                        'shippingZoneId' => $shipping_price_rule['shippingZoneId'],
                    )
                );
                return $shipping_price_rule['id'];
            }
        }
    }

    /**
     * Retrives the shipping rules for the given location
     * @param location array with the countryIds and the postcodes of the shipping method
     * @return rules rules of location
     */
    public function get_shipping_rules_by_location($locations, $shipping_method) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $countries = array();
        $postcodes = array();
        foreach ($locations as $value){
            $value = get_object_vars($value);
            if($value['type']==='country'){
                array_push($countries,$value['code']);
            } else if($value['type']==='postcode'){
                array_push($postcodes,$value['code']);
            }

        }

        $rules = array();
        foreach ($countries as $countryId){

            //There arent postcodes defined
            if(empty($postcodes)){

                $query = "SELECT * FROM $table_name WHERE countryId=%s AND minPostCode IS NULL AND maxPostCode IS NULL AND shippingMethod=%s";
                $results = $wpdb->get_results( $wpdb->prepare( $query, array($countryId, $shipping_method) ) );
                array_push($rules,$results);
            } else {
                foreach($postcodes as $postcodeValue){

                    //Uniform input
                    //Single postcode
                    if(strpos($postcodeValue, '...') == false){
                        $minPostCode = dhl_parcel_postcode_normalizer($countryId, $postcodeValue);
                        $maxPostCode = dhl_parcel_postcode_normalizer($countryId, $postcodeValue);

                    } else {
                        $postcode = explode("...", $postcodeValue);
                        $minPostCode = dhl_parcel_postcode_normalizer($countryId, $postcode[0]);
                        $maxPostCode = dhl_parcel_postcode_normalizer($countryId, $postcode[1]);
                    }

                    $query = "SELECT * FROM $table_name WHERE countryId=%s AND minPostCode=%s AND maxPostCode=%s AND shippingMethod=%s";
                    $results = $wpdb->get_results( $wpdb->prepare( $query, array($countryId, $minPostCode, $maxPostCode, $shipping_method) ) );
                    array_push($rules,$results);
                }
            }

        }

        /** Flatten the array and changes from stdClass to array*/
        $rules_array = array();
        foreach($rules as $val){
            foreach($val as $rule){
                array_push($rules_array,get_object_vars($rule));
            }
        }

        return $rules_array;
    }
    public function get_shipping_rules_by_zone_id($zone_id, $shipping_method) {
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $rules = array();
        $query = "SELECT * FROM $table_name WHERE shippingZoneId=%s AND shippingMethod=%s";
        $results = $wpdb->get_results( $wpdb->prepare( $query, array($zone_id,$shipping_method) ) );
        array_push($rules,$results);

        /** Flatten the array and changes from stdClass to array*/
        $rules_array = array();
        foreach($rules as $val){
            foreach($val as $rule){
                array_push($rules_array,get_object_vars($rule));
            }
        }
        return $rules_array;
    }
    public function get_shipping_rules_by_zone_id_and_criteria($zone_id, $weight, $price, $shipping_method) {
        global $wpdb;

        // The final weight is the greater of the real weight and
        // the volumetric weight.
        $weight = apply_filters( 'dhl_calcute_final_weight', $weight );

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $rules = array();
        $query = "SELECT * FROM $table_name
                  WHERE shippingZoneId=%s
                  AND shippingMethod=%s
                  AND
                      (( %s BETWEEN min AND max AND ruleCriteria = 'Price')
                      OR
                      (  %s BETWEEN min AND max AND ruleCriteria = 'Weight'))";
        $results = $wpdb->get_results($wpdb->prepare( $query, array($zone_id,$shipping_method,$price,$weight)));
        array_push($rules,$results);

        /** Flatten the array and changes from stdClass to array*/
        $rules_array = array();
        foreach($rules as $val){
            foreach($val as $rule){
                array_push($rules_array,get_object_vars($rule));
            }
        }
        return $rules_array;
    }

    /**
     * Retrives the shipping rules for the given country, postcode, weight, price, shipping method
     * @param
     * @return rules rules
     */
    public function get_shipping_rules_by_criteria($countryId, $postcode, $weight, $price, $shipping_method) {

        $rules = array();

        //Weight based rules
        $results = $this->get_shipping_rules($countryId, $postcode, "Weight", $weight, $shipping_method);
        array_push($rules,$results);

        //Price based rules
        $results = $this->get_shipping_rules($countryId, $postcode, "Price", $price, $shipping_method);
        array_push($rules,$results);

        $rules = dhl_parcel_array_flatten($rules);

        return $rules;
    }

    /**
     * Retrives shipping rules based on country, postcode, ruleCriteria, its value and shipping method
     */
    private function get_shipping_rules($countryId, $postcode, $ruleCriteria, $value, $shipping_method){

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;
        $normalized_postcode = dhl_parcel_postcode_normalizer($countryId, $postcode);

        $query =
            "SELECT cost FROM $table_name
            WHERE countryId = %s
            AND minPostCode <= %s
            AND maxPostCode >= %s
            AND min <= %s
            AND max >= %s
            AND shippingMethod = %s
            AND ruleCriteria = %s";

        $results = $wpdb->get_results( $wpdb->prepare( $query, array($countryId, $normalized_postcode, $normalized_postcode, $value, $value, $shipping_method, $ruleCriteria) ) );

        if(empty($results)){
            $query =
                "SELECT cost FROM $table_name
                WHERE countryId = %s
                AND minPostCode IS NULL
                AND maxPostCode IS NULL
                AND min <= %s
                AND max >= %s
                AND shippingMethod = %s
                AND ruleCriteria = %s";

            $results = $wpdb->get_results( $wpdb->prepare( $query, array($countryId, $value, $value, $shipping_method, $ruleCriteria) ) );
        }

        return $results;
    }


    /**
     * Validates if object can be inserted into the database
     */
    public function validate_object_for_database($shipping_price_rule){
        if( is_null($shipping_price_rule['countryID'])
        && $shipping_price_rule['min'] >=0
        && $shipping_price_rule['max'] >= $shipping_price_rule['min']
        && $shipping_price_rule['cost'] >=0
        && $this->isValidRuleCriteria($shipping_price_rule['ruleCriteria'])
        && $this->isValidShippingMethod($shipping_price_rule['shippingMethod'])
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if its a valid Rule Criteria
     */
    public function isValidRuleCriteria($value){
        if(strcmp($value,"Weight")
            || strcmp($value,"Price")
        ) {
            return true;
        }
        return false;
    }

    /**
     * Checks f its a valid Shipping Method
     */
    public function isValidShippingMethod($value){
        if(strcmp($value,"dhl_normal_shipping_method")
            || strcmp($value,"dhl_service_point_shipping_method")
        ) {
            return true;
        }
        return false;
    }

    /**
     * Deletes the table from the database
     */
    public function delete_shipping_rules_table() {
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS " . $table_name );
    }

    /**
     * Retrives all the shipping prices
     */
    public function get_all_shipping_prices() {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $results = $wpdb->get_results( "SELECT * FROM $table_name");

        return $results;

    }

    /**
     * Deletes the shipping prices
     */
    public function delete_shipping_rule($ID) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $wpdb->delete($table_name,array('id' => $ID));

    }



    /**
     * Deletes the shipping prices
     */
    public function delete_shipping_rule_by_location($locations, $shipping_method, $shippingZoneId) {

        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $countries = array();
        $postcodes = array();
        foreach ($locations as $value){
            $value = get_object_vars($value);
            if($value['type']==='country'){
                array_push($countries,$value['code']);
            } else if($value['type']==='postcode'){
                array_push($postcodes,$value['code']);
            }

        }
        foreach ($countries as $countryId){

            //There arent postcodes defined
            if(empty($postcodes)){

                $query = array( 'countryId' => $countryId, 'minPostCode' => null, 'maxPostCode' => null, 'shippingMethod' => $shipping_method, 'shippingZoneId' => $shippingZoneId );
                $wpdb->delete($table_name,$query);
            } else {
                foreach($postcodes as $postcodeValue){

                    //Uniform input
                    //Single postcode
                    if(strpos($postcodeValue, '...') == false){
                        $minPostCode = dhl_parcel_postcode_normalizer($countryId, $postcodeValue);
                        $maxPostCode = dhl_parcel_postcode_normalizer($countryId, $postcodeValue);

                    } else {
                        $postcode = explode("...", $postcodeValue);
                        $minPostCode = dhl_parcel_postcode_normalizer($countryId, $postcode[0]);
                        $maxPostCode = dhl_parcel_postcode_normalizer($countryId, $postcode[1]);
                    }

                    $query = array( 'countryId' => $countryId, 'minPostCode' => $minPostCode, 'maxPostCode' => $maxPostCode, 'shippingMethod' => $shipping_method,  'shippingZoneId' => $shippingZoneId  );
                    $wpdb->delete($table_name,$query);
                }
            }
        }
    }

    public function deleteShippingRulesByZoneId($zoneId){
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $wpdb->delete($table_name,array('shippingZoneID' => $zoneId));
    }

    public function get_next_id(){
        global $wpdb;

        //Name of the database
        $table_name = $wpdb->prefix . self::SHIPPING_RULES_TABLE_NAME;

        $results = $wpdb->get_results( "SELECT MAX(id) FROM $table_name");

        $t = $results['0'];
        $array = json_decode(json_encode($t), True);

        return $array['MAX(id)'];
    }

}
