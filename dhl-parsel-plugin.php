<?php
/*
Plugin Name: DHL Parcel Iberia Shipping Plugin
Plugin URI: www.skrey-software.com
Description: Adds functionalities
Version: 1.0.19
Author: Skrey Software, Frontkom
Author URI: www.skrey-software.com, frontkom.com
*/
define ( 'DHL_PARCEL_VERSION', '1.0.19' );

//Dependencies
require_once('classes/dhl-service-point-shipping-method.php');
require_once('classes/dhl-normal-shipping-method.php');
require_once('utils/database_handler.php');
require_once('classes/views/dhl-settings-page.php');
require_once('utils/DhlClient.php');
require_once('utils/CapabilitiesDB.php');
require_once('classes/dhl-service-point-shipping-method.php');
require_once('classes/views/reference-field-meta-box.php');

require_once('classes/dhl-cash-on-delivery-gateway.php');

//Start the session if no session is already started
function register_session(){
    if( !session_id() && session_status() != PHP_SESSION_ACTIVE && ! headers_sent() )
        session_start();
}
add_action('init','register_session');

//Load Translations
function dhl_parcel_load_plugin_textdomain() {
	$domain = 'dhl_parcel_iberia_woocommerce_plugin';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
	if ( $loaded = load_textdomain( $domain, __DIR__. '/languages' . '/' . $domain . '-' . $locale . '.mo' ) ) {
		return $loaded;
	} else {
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}
add_action( 'init', 'dhl_parcel_load_plugin_textdomain' );

//Desactivate plugin
function desactivate_plugin(){
    //cronjob
    wp_clear_scheduled_hook('dhl_pickup_request_hourly_action');
}
register_deactivation_hook(__FILE__, 'desactivate_plugin');

//Add payment gateway
function wc_shl_cod_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Gateway_DHL_COD';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_shl_cod_add_to_gateways' );

//Ensures that the database table will be created when the plugin is activated
register_activation_hook( __FILE__, array('Database_Handler','create_shipping_rules_table') );
register_activation_hook( __FILE__, array('Database_Handler','create_labels_table') );
register_activation_hook( __FILE__, array('Database_Handler','create_pickup_request_table') );
register_activation_hook( __FILE__, array('CapabilitiesDB','create_capabilities_DBs') );

function dhl_parcel_update_db_check() {
    $options = get_option( 'dhl_parcel_options' );
    if (!isset($options['dhl_parcel_db_version']) || $options['dhl_parcel_db_version'] != DHL_PARCEL_VERSION) {
        $dbh = new Database_Handler();
        $dbh->update_plugin(isset($options['dhl_parcel_db_version'])?$options['dhl_parcel_db_version']:'1.0');
    }
}
add_action( 'plugins_loaded', 'dhl_parcel_update_db_check' );
//Add css rules to admin
function admin_dhl_enqueue_scripts() {
    wp_register_style( 'dhl_shipping_method', plugin_dir_url( __FILE__ ) . 'assets/css/admin-dhl-shippings.css', false, '1.0.0' );
    wp_register_style( 'track-and-trace', plugin_dir_url( __FILE__ ) . 'assets/css/track-and-trace-modal.css', false, '1.0.0' );
    wp_enqueue_style( 'dhl_shipping_method');
    wp_enqueue_style( 'track-and-trace' );
}

add_action( 'admin_enqueue_scripts', 'admin_dhl_enqueue_scripts' );

// Add scripts to wp_head()
function load_google_maps() {
	?>

    <script sync
            src="https://maps.googleapis.com/maps/api/js?key=<?php $options = get_option( 'dhl_parcel_options' );echo esc_html($options['gmac_google_map_api_key']) ?>">
        </script>

    <?php
}
add_action( 'wp_head', 'load_google_maps' );


//Add scripts to frontend pages
function front_dhl_enqueue_scripts() {
    wp_register_script( 'dhl_parcel_checkout', plugin_dir_url( __FILE__ ).'assets/js/html-checkout-page-js.js', array( 'jquery' ),false,false);
    wp_localize_script( 'dhl_parcel_checkout', 'dhl_parcel_iberia_woocommerce_plugin', array( 'shipping_methods' =>  dhl_get_shipping_methods() ) );
    wp_localize_script( 'dhl_parcel_checkout', 'plugin_path', array( 'path' => plugin_dir_url( __FILE__ ) ));
    wp_localize_script( 'dhl_parcel_checkout', 'ajax_obj',array( 'my_ajax_url' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script('dhl_parcel_checkout');
    wp_register_style( 'track-and-trace', plugin_dir_url( __FILE__ ) . 'assets/css/track-and-trace-modal.css', false, '1.0.0' );
    wp_enqueue_style( 'track-and-trace' );
    wp_enqueue_style('checkout_style', plugin_dir_url( __FILE__ ).'assets/css/checkout.css');

}

add_action( 'wp_enqueue_scripts', 'front_dhl_enqueue_scripts' );


//Import checkout-page
require_once('classes/views/html-checkout-page.php');

//Add to checkout
add_action( 'woocommerce_review_order_before_payment', 'dhl_woocommerce_review_order_before_payment' ,10, 1);

//AjaxCalls
require_once( 'async/ajax_controller.php');


//Hook for updating the shipping rate based on the service point
add_action( 'woocommerce_checkout_update_order_review', 'woocommerce_checkout_update_order_review' );

function woocommerce_checkout_update_order_review( $post_data ){

    $shipping_address = WC()->session->get( 'service_point_address');

    $packages = WC()->cart->get_shipping_packages();
    foreach ( $packages as $package_key => $package ) {
        if($shipping_address) {
            $package['destination']['country'] = $shipping_address['country'];
            $package['destination']['postcode']  = $shipping_address['codePostal'];
            $package['destination']['city'] = $shipping_address['city'];
            $package['destination']['address'] = $shipping_address['address'];

            $shipping_obj = WC()->shipping;
            $shipping_methods = $shipping_obj->load_shipping_methods($package);
            foreach ( $shipping_methods as $shipping_method ) {
                if($shipping_method instanceof WC_DHL_Service_Point_Shipping_Method){
                    $rate = $shipping_method->get_rates_for_package( $package );
                    if($rate){
                        $cost = $rate['dhl_service_point_shipping_method']->get_cost();
                        WC()->session->set( 'shipping_sp_cost', $cost );
                    }
                }
            }
            // this is needed for us to remove the session set for the shipping cost. Without this, we can't set it on the checkout page.
            WC()->session->set( 'shipping_for_package_' . $package_key, false );
        }

    }

}

//Update the shipping rates
add_filter( 'woocommerce_package_rates', 'adjust_shipping_rate', 50 );
function adjust_shipping_rate( $rates ){

    foreach ($rates as $rate) {
        $cost = $rate->cost;
        if($rate->method_id == "dhl_service_point_shipping_method"){
            if(WC()->session->get( 'shipping_sp_cost' ) != null){
                //$rate->cost = WC()->session->get( 'shipping_sp_cost' );
            }
        }
    }
    return $rates;
}

//Verifies if the dhl shipping methods are avaliable for the current cart using capabilities
add_filter( 'woocommerce_package_rates', 'dhl_shipping_methods_verifier', 10, 2 );
function dhl_shipping_methods_verifier( $available_shipping_methods, $package ) {
    global $woocommerce;
    $dhl_client = new DhlClient();
    $cart = $woocommerce->cart;
    foreach( $available_shipping_methods as $shipping_method){
        if($shipping_method->method_id == "dhl_service_point_shipping_method"){
            if(!$dhl_client->isParcelShippingAvailable($cart)){
                unset( $available_shipping_methods["dhl_service_point_shipping_method"] );
            }
        }
        if($shipping_method->method_id == "dhl_normal_shipping_method"){
            if(!$dhl_client->isNormalShippingAvailable($cart)){
                unset( $available_shipping_methods["dhl_normal_shipping_method"] );
            }
        }
    }

    return $available_shipping_methods;
}

function get_shipping_cost_by_service_point($service_point_address){
    global $woocommerce;

    $postal_code = $service_point_address[ 'codePostal' ];
    $countryId = $service_point_address[ 'country' ];
    $postal_code = dhl_parcel_postcode_normalizer($countryId,$postal_code);

    $weight = $woocommerce->cart->cart_contents_weight;
    $price = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );

    $dbh = new Database_Handler();
    $rulesCost = $dbh->get_shipping_rules_by_criteria($countryId,$postal_code,$weight,$price,'dhl_service_point_shipping_method');

    $finalCost = 0;
    foreach($rulesCost as $rules){
        foreach($rules as $cost){
            if( $finalCost == 0){
                $finalCost = $cost;
            } else if($finalCost > $cost) {
                $finalCost = doubleval($cost);
            }
        }
    }

    return $finalCost;
}

//Remove price if value is 0 on Service Point Shipping for Service Point Shipping
add_filter( 'woocommerce_cart_shipping_method_full_label', 'add_service_point_label', 10, 2 );
function add_service_point_label( $label, $method ) {
    return $label;
}

//Add service_point data and cart weigth to order metadata
add_action('woocommerce_checkout_create_order', 'before_checkout_create_order', 20, 2);
function before_checkout_create_order( $order, $data ) {

    //Weight
    global $woocommerce;
    $woocommerce->cart->calculate_totals();
    $weight = $woocommerce->cart->cart_contents_weight;
    $order->update_meta_data( '_cart_weight', $weight );
}

add_action('woocommerce_checkout_create_order_shipping_item', 'before_checkout_create_order_item', 20, 4);
function before_checkout_create_order_item( $item, $package_key, $package, $order) {

    // save service point
    $service_point = WC()->session->get( 'service_point');
    if ($item->get_method_id() == "dhl_service_point_shipping_method") {
        $item->add_meta_data( 'service_point_id', $service_point );
    }
}

//Order Validations on place order
add_action('woocommerce_after_checkout_validation', 'after_checkout_order_validation');

function after_checkout_order_validation( $posted ) {

    $service_point = WC()->session->get( 'service_point');

    if($posted['shipping_method']['0'] == "dhl_normal_shipping_method" || $posted['shipping_method']['0'] == "dhl_service_point_shipping_method"){
        if ($posted['shipping_method']['0'] == "dhl_service_point_shipping_method"  && empty($service_point['sp_id'])) {
            wc_add_notice( __( "No service point selected for shipping!", 'dhl_parcel_iberia_woocommerce_plugin' ), 'error' );
       }
       if(strlen($posted['billing_address_1']) >= 40
            ||  strlen($posted['billing_address_2']) >= 40
            || strlen($posted['shipping_address_1']) >= 40
            ||  strlen($posted['shipping_address_2']) >= 40) {
                wc_add_notice( __( "Address can't have more that 40 characters!", 'dhl_parcel_iberia_woocommerce_plugin' ), 'error' );
        }
    }


}

//Check when to create the label
function action_woocommerce_order_status_changed( $this_get_id, $this_status_transition_from, $this_status_transition_to, $instance ) {
    $options = get_option( 'dhl_parcel_options' );

    $dbh = new Database_Handler();
    $status = str_replace("wc-", "", strtolower($options['ppoc_order_state_to_create_shipping_label ']) );
    $dhl_client = new DhlClient();
    if (!$dhl_client->isDHLParcelShipment($this_get_id)) { return; }
    $label = $dbh->get_labels_by_order_id($this_get_id,false);

    if( strcmp($status, $this_status_transition_to)==0 && !count($label)){
        //Reference incase its order_id and did not enter on the "Edit order" page.
        if ($options['ppoc_reference_field'] == "order_id"){
            if(!get_post_meta( $this_get_id, '_reference_field', true )){
                update_post_meta( $this_get_id, '_reference_field', $this_get_id );
            }
        }

        $json = $dhl_client->createDhlLabel($this_get_id);
        $json = get_object_vars($json);
        $tracking_code = $json['trackerCode'];
        $dbh->insert_labels($json['labelId'],false,$this_get_id,$tracking_code);

        if($options['ppoc_create_return_label']){
            $json = $dhl_client->createDhlReturnLabel($this_get_id);
            $json = get_object_vars($json);
            $tracking_code = $json['trackerCode'];
            $dbh->insert_labels($json['labelId'],true,$this_get_id,$tracking_code);
        }
        $dhl_client->createPickUpRequest($this_get_id);
    }

};

//Hook for when the order state changes
add_action( 'woocommerce_order_status_changed', 'action_woocommerce_order_status_changed', 10, 4 );


//Add a custom actions to order actions select box on edit order page
function order_action_print_label( $actions ) {
    global $theorder;

    // add custom action
    $dbh = new Database_Handler();
    $label = $dbh->get_labels_by_order_id($theorder->get_id(),false);
    if($label != null){
        $actions['print_label'] = __( 'Print shipping label', 'dhl_parcel_iberia_woocommerce_plugin' );
        $actions['print_return_label'] = __( 'Create and/or print return shipping label', 'dhl_parcel_iberia_woocommerce_plugin' );
    }
    return $actions;
}
add_action( 'woocommerce_order_actions', 'order_action_print_label' );

//Add an print label when custom action is clicked
function order_action_print_label_action( $order ) {

    $dbh = new Database_Handler();

    $label = $dbh->get_labels_by_order_id($order->get_id(),false);

    $label = get_object_vars($label);
    $label_id = $label['label_id'];
    get_label( $order->get_id(), $label_id, false );

}
add_action( 'woocommerce_order_action_print_label', 'order_action_print_label_action' );

//Add an print return label when custom action is clicked
function order_action_print_return_label_action( $order ) {

    $dbh = new Database_Handler();
    $label = $dbh->get_labels_by_order_id($order->get_id(),true);
    if($label == null || empty($label)){
        $dhl_client = new DhlClient();
        $json = $dhl_client->createDhlReturnLabel($order->get_id());
        $json = get_object_vars($json);
        $tracking_code = $json['trackerCode'];
        $dbh->insert_labels($json['labelId'],true,$order->get_id(),$tracking_code);
        $label = $dbh->get_labels_by_order_id($order->get_id(),true);
    }
    $label = get_object_vars($label);
    $label_id = $label['label_id'];
    get_label( $order->get_id(), $label_id, true );
}
add_action( 'woocommerce_order_action_print_return_label', 'order_action_print_return_label_action' );

//Show the label
function get_label( $order_id, $label_id, $is_return ){

    if(!$label_id || $label_id==null){
        die();
    }else{
        $dhl_client = new DhlClient();
        try{
            $pdf = $dhl_client->getLabelPDF($label_id);
        }catch(Exception $e){
        die();
        }
        $tmpName = tempnam(sys_get_temp_dir(), $order_id.'.pdf');
        $file = fopen($tmpName, 'w');
        fputs($file, $pdf);
        fclose($file);
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename='.($is_return>0?'return_':'shipping_').$order_id.'.pdf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmpName));
        ob_clean();
        flush();
        readfile($tmpName);
        unlink($tmpName);
        die;
    }
}

//Add custom service point field to the order details
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'dhl_shipping_service_point_field', 10, 1 );

function dhl_shipping_service_point_field($order){
    $dhl_client = new DhlClient();
    $servicePointId = $dhl_client->getServicePointId($order);
    if (!is_null($servicePointId) && !empty($servicePointId)) {
        echo '<p><strong>'.__('Service Point').':</strong> <br/>' . $servicePointId['sp_id'] . '</p>';
    }
}

//Add custom shipping tracking field to the order details
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'dhl_shipping_tracking_field', 10, 1 );

function dhl_shipping_tracking_field( $order, $is_admin = true ){
    $trackingUrl = apply_filters( 'dhl_tracking_url', 'https://clientesparcel.dhl.es/seguimientoenvios/integra/SeguimientoDocumentos.aspx?codigo=@&anno=$year&lang=$lang' );

    $dbh = new Database_Handler();
    $label = $dbh->get_labels_by_order_id($order->get_id(),false);

    if(count($label)){
        $label = get_object_vars($label);
        $tracking_code = $label['tracking_code'];
        $tracking_data = getTrackingData($order);
        $last_status = '';

        if( $tracking_data != null){

            $tracking_data = dhl_parcel_arrayCastRecursive( json_decode($tracking_data) );
            $tracking_data = $tracking_data[0];
            if($tracking_data['events'] != null){
                $last_status = end($tracking_data['events'])['status'];
            }
        }

        $lang = $order->get_shipping_country();
        $year = (new DateTime($label['creation_date']))->format('Y');
        $url = $trackingUrl;
        $url = str_replace('@', $tracking_code, $url);
        $url = str_replace('$year', $year, $url);
        $url = str_replace('$lang', $lang, $url);

        $filter = $is_admin ? 'dhl_admin_order_tracking_code' : 'dhl_order_tracking_code';

        echo apply_filters( $filter, '<p><strong>'.__('Tracking code').':</strong> <br/> <a id="trackAndTraceLink" href="'.esc_html($url).'">'.esc_html($tracking_code).'</a></p>
        <p><label>'.__("Last shipping status: ") . $last_status.'</label></p>', $url, $tracking_code, $last_status );
    }

}

//add custom data to the view order field
add_action( 'woocommerce_view_order', 'dhl_shipping_tracking_field_view_order', 20, 1 );

function dhl_shipping_tracking_field_view_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( $order ) {
        dhl_shipping_tracking_field( $order, false );
    }
}


function getTrackingData($order){
    if($order){
        $order_id =  $order->get_id();
        $dbh = new Database_Handler();

        $label= $dbh->get_labels_by_order_id($order_id, false);
        $label =  get_object_vars($label);
        $tracking_code = $label['tracking_code'];
        $postcode = $order->get_shipping_postcode();

        $tracker = new DhLClient();
        return $tracker->getTrackingInfo($tracking_code, $postcode);
    }
}

//Register Cronjob
register_activation_hook(__FILE__, 'dhl_pickup_request_cronjob');

function dhl_pickup_request_cronjob() {
    if (! wp_next_scheduled ( 'dhl_pickup_request_hourly_action' )) {
	wp_schedule_event(time(), 'hourly', 'dhl_pickup_request_hourly_action');
    }
}

add_action('dhl_pickup_request_hourly_action', 'dhl_pickup_request_hourly');

function dhl_pickup_request_hourly() {
	try{
        $dhl_client = new DhlClient();
        $dhl_client->createPickUpRequest(null,true);
    }catch(Exception $e){
    }
}

//On shipping zone saves
add_action( "woocommerce_before_shipping_zone_object_save", 'action_woocommerce_before_shipping_zone_object_save', 10, 2 );

function action_woocommerce_before_shipping_zone_object_save( $instance, $this_data_store ) {
    // make action magic happen here...
    $shipping_methods = $instance->get_shipping_methods();
    foreach($shipping_methods as $shipping_method){
        if( isset($instance->get_changes()['zone_locations']) ){
            update_shipping_rules($shipping_method, $instance->get_changes()['zone_locations'], $instance->get_id());
        }

    }

};

function update_shipping_rules( $shipping_method, $new_location, $zone_id){
    $shipping_methods_accepted_ids = array("dhl_service_point_shipping_method", "dhl_normal_shipping_method" );
    $dbh = new Database_Handler();
    $shipping_method_array = (array) $shipping_method; //For some reason there isnt a function to return the shipping method id
    $shipping_method_id = $shipping_method_array['id'];
    if( in_array($shipping_method_id, $shipping_methods_accepted_ids)  ){
        $option_name = 'woocommerce_'.$shipping_method_id.'_' .$shipping_method->get_instance_id().'_settings';
        $options = get_option($option_name);
        $rules = json_decode($options["shipping_rules_text"]);
        foreach( $rules as $rule){
            $rule = json_decode(json_encode($rule), True);
			$dbh->saveShippingRule($shipping_method_id, $rule, $new_location, $zone_id);
        }
    }
}

// define the woocommerce_shipping_zone_method_deleted callback
function remove_shipping_method_rules_on_shipping_method_delete( $instance_id, $method_id, $zone_id ) {

    $dbh = new Database_Handler();
    $zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );
    $dbh->delete_shipping_rule_by_location($zone->get_zone_locations(), $method_id, $zone_id);

};

// add the action
add_action( 'woocommerce_shipping_zone_method_deleted', 'remove_shipping_method_rules_on_shipping_method_delete', 10, 3 );

// define the woocommerce_delete_shipping_zone callback
function remove_shipping_methods_on_shipping_zone_delete( $id ) {
    $dbh = new Database_Handler();
    $dbh->deleteShippingRulesByZoneId($id);
};

// add the action
add_action( 'woocommerce_delete_shipping_zone', 'remove_shipping_methods_on_shipping_zone_delete', 10, 1 );
