<?php

require_once( __DIR__. '/../utils/utils.php');

//GetParcelLocs
add_action( 'wp_ajax_nopriv_get_parcel_locations', 'get_parcel_locations' );
add_action( 'wp_ajax_get_parcel_locations', 'get_parcel_locations' );

function get_parcel_locations() {
    
    $codePostal = $_POST['codePostal'];
    $address =$_POST['address'];
    $city = $_POST['city'];
    $country = $_POST['country'];
    $searchQuery = $_POST['searchQuery'];

    $dhl_client = new DhlClient();
    if(empty($searchQuery)){
        $params = array(
            'zipcode' => $codePostal,
            'address' => $address,
            'countryCode' => $country,
            'city' => $city,
        );

        $servicepoints=$dhl_client->getServicePointsLocation($params);
    } else {
        $params_q = array(
            'zipcode' => $codePostal,
            'address' => $address,
            'countryCode' => $country,
            'city' => $city,
            'q' => $searchQuery
        );

        $servicepoints=$dhl_client->getServicePointsLocation($params_q);
    }

    echo json_encode(arrayCastRecursive( $servicepoints ));
    

	wp_die();
}

add_action( 'wp_ajax_nopriv_set_service_point_and_update_shipping_price', 'set_service_point_and_update_shipping_price' );
add_action( 'wp_ajax_set_service_point_and_update_shipping_price', 'set_service_point_and_update_shipping_price' );

function set_service_point_and_update_shipping_price() {
    
    //No service point selected
    if(strcmp($_POST['parcel_shop_id'], "") == 0 ){
        $data_sp = array(
            'sp_id' => "",
            'sp_country' => "",
        );
        $data_address = array(
            'codePostal' => "",
            'address' => "",
            'city' => "",
            'country' => "",
        );    
    } else {
        $data_sp = array(
            'sp_id' => $_POST['parcel_shop_id'],
            'sp_country' => $_POST['country'],
        );
        $data_address = array(
            'codePostal' => $_POST['codePostal'],
            'address' =>$_POST['address'],
            'city' => $_POST['city'],
            'country' => $_POST['country'],
        ); 
    }
    WC()->session->set( 'service_point', $data_sp );

    WC()->session->set( 'service_point_address', $data_address );

    do_action( 'woocommerce_checkout_update_order_review');
    
    echo json_encode(array('success' => 'OK'));

	wp_die();
}

add_action( 'wp_ajax_nopriv_update_home_location', 'update_home_location' );
add_action( 'wp_ajax_update_home_location', 'update_home_location' );

function update_home_location() {
    
    $customer=WC()->session->get( 'customer' );
    $postcode = $customer['shipping_postcode'];
    $address = $customer['shipping_address'];
    $country = $customer['shipping_country'];
    $city = $customer['shipping_city'];

    $home_location = array(
        'zipcode' => $postcode,
        'address' => $address,
        'countryCode' => $country,
        'city' => $city,
    );

    echo json_encode($home_location);

	wp_die();
}

