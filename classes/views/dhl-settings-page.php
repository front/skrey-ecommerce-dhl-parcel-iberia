<?php
/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */
 
require_once dirname(dirname(__DIR__))."/utils/database_handler.php";

/**
 * custom option and settings
 */
function dhl_parcel_settings_init() {

    // register a new setting for "dhl_parcel" page
    register_setting( 'dhl_parcel_iberia_woocommerce_plugin', 'dhl_parcel_options', 'dhl_parcel_options_validator' );
    
    // register a new section in the "dhl_parcel" page
    //Section : DHL WS Credentials
    add_settings_section(
        'dhl_parcel_section_wsc',
        __( 'DHL WS Credentials', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_wsc_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );
 
    // register a new field in the "dhl_parcel_section_developers" section, inside the "dhl_parcel" page
    add_settings_field(
        'dhl_parcel_wsc_pickup_depot', // as of WP 4.6 this value is used only internally
        __( 'Pick-up Depot', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_wsc',
        [
            'label_for' => 'wsc_pickup_depot',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_wsc_account_id',
        __( 'Account Id', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_wsc',
        [
            'label_for' => 'wsc_account_id',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_wsc_user_id',
        __( 'User Id', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_wsc',
        [
            'label_for' => 'wsc_user_id',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_wsc_key',
        __( 'Key', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_hidden_value_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_wsc',
        [
            'label_for' => 'wsc_key',
            'class' => 'dhl_settings_row'
        ]
    );

    //Section : Pick-up Point Address Configuration
    add_settings_section(
        'dhl_parcel_section_pp',
        __( 'Pick-Up Point Address Configuration', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_pp_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );

    add_settings_field(
        'dhl_parcel_pp_company_name', 
        __( 'Company Name', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_company_name',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_street', 
        __( 'Pick-up Point Street', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_street',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_house_number', 
        __( 'Pick-up Point House Number', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_house_number',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_additional_information', 
        __( 'Pick-up Point - Additional Information', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_not_required_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_additional_information',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_town', 
        __( 'Pick-up Point Town', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_town',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_postal_code', 
        __( 'Pick-up Point Postal Code', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_postal_code',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_pp_pickup_point_country', 
        __( 'Pick-up Point Country', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_countries_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_pp',
        [
            'label_for' => 'pp_pickup_point_country',
            'class' => 'dhl_settings_row'
        ]
    );

    //Section : Pick-up Point Additional Information
    add_settings_section(
        'dhl_parcel_section_ppai',
        __( 'Pick-Up Point Additional Information', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_ppai_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_parcel_location', 
        __( 'Pick-up Point Parcel Location', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_parcel_location',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_especial_instructions_for_dhl', 
        __( 'Pick-up Point Especial Instructions For DHL', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_not_required_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_especial_instructions_for_dhl',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_telephone_number', 
        __( 'Pick-up Point Telephone Number', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_telephone_number',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_email', 
        __( 'Pick-up Point Email', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_parcel_email',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_contact_person_firstname', 
        __( 'Pick-up Point Contact Person\'s Firstname', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_contact_person_firstname',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppai_pickup_point_contact_person_lastname', 
        __( 'Pick-up Point Contact Person\'s Lastname', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppai',
        [
            'label_for' => 'ppai_pickup_point_contact_person_lastname',
            'class' => 'dhl_settings_row'
        ]
    );

    //Section : Pick-up Point Working Hours
    add_settings_section(
        'dhl_parcel_section_ppwh',
        __( 'Pick-up Point Working Hours', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_ppwb_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );

    add_settings_field(
        'dhl_parcel_ppwh_pickup_point_opening_hours', 
        __( 'Pick-up Point Opening Hours', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppwh',
        [
            'label_for' => 'ppwh_pickup_point_opening_hours',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppwh_pickup_point_closing_hours', 
        __( 'Pick-up Point Closing Hours', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppwh',
        [
            'label_for' => 'ppwh_pickup_point_closing_hours',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppwh_has_lunch_break', 
        __( 'Has Lunch Break', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_dropdown_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppwh',
        [
            'label_for' => 'ppwh_has_lunch_break',
            'class' => 'dhl_settings_row',
            'list' => array(__( 'Yes', 'dhl_parcel_iberia_woocommerce_plugin' ),__( 'No', 'dhl_parcel_iberia_woocommerce_plugin' ))
        ]
    );

    add_settings_field(
        'dhl_parcel_ppwh_pickup_point_lunch_break_beginning', 
        __( 'Pick-up Point Lunch Break Beginning', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_not_required_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppwh',
        [
            'label_for' => 'ppwh_pickup_point_lunch_break_begginning',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppwh_pickup_point_lunch_break_ending', 
        __( 'Pick-up Point Lunch Break Ending', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_not_required_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppwh',
        [
            'label_for' => 'ppwh_pickup_point_lunch_break_ending',
            'class' => 'dhl_settings_row'
        ]
    );

    //Section : Pick-up Point Other Configurations
    add_settings_section(
        'dhl_parcel_section_ppoc',
        __( 'Other Configurations', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_ppoc_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );

    add_settings_field(
        'dhl_parcel_ppoc_company_vat_number', 
        __( 'Company VAT number', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_company_vat_number',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppoc_order_state_to_create_shipping_label', 
        __( 'Order State To Create Shipping Label', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_dropdown_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_order_state_to_create_shipping_label ',
            'class' => 'dhl_settings_row',
            'list' => wc_get_order_statuses()
        ]
    );

    //Change to checkbox
    add_settings_field(
        'dhl_parcel_ppoc_create_return_label', 
        __( 'Create Return Label', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_dropdown_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_create_return_label',
            'class' => 'dhl_settings_row',
            'list' => array(__( 'No', 'dhl_parcel_iberia_woocommerce_plugin' ),__( 'Yes', 'dhl_parcel_iberia_woocommerce_plugin' ))
        ]
    );

    add_settings_field(
        'dhl_parcel_ppoc_fixed_pickup', 
        __( 'Fixed Pickup', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_dropdown_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_fixed_pickup',
            'class' => 'dhl_settings_row',
            'list' => array(__( 'No', 'dhl_parcel_iberia_woocommerce_plugin' ),__( 'Yes', 'dhl_parcel_iberia_woocommerce_plugin' ))
        ]
    );

    add_settings_field(
        'dhl_parcel_ppoc_same_day_pick_up_hour', 
        __( 'Same day pick up hour', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_same_day_pick_up_hour',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppoc_capabilities_cache_reset_hour', 
        __( 'Capabilities Cache Reset Hour', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_time_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_capabilities_cache_reset_hour',
            'class' => 'dhl_settings_row'
        ]
    );

    add_settings_field(
        'dhl_parcel_ppoc_reference_field', 
        __( 'Reference field filling method', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_dropdown_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_ppoc',
        [
            'label_for' => 'ppoc_reference_field',
            'class' => 'dhl_settings_row',
            'list' => array("order_id" =>__( 'Order Id', 'dhl_parcel_iberia_woocommerce_plugin' ),"manual" =>__( 'Manual', 'dhl_parcel_iberia_woocommerce_plugin' ))
        ]
    );

    //Section : Google Maps API Configuration
    add_settings_section(
        'dhl_parcel_section_gmac',
        __( 'Google Map API Configuration', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_section_gmac_cb',
        'dhl_parcel_iberia_woocommerce_plugin'
    );

    add_settings_field(
        'dhl_parcel_gmac_google_map_api_key', 
        __( 'Google Maps API Key', 'dhl_parcel_iberia_woocommerce_plugin' ),
        'dhl_parcel_textbox_cb',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_section_gmac',
        [
            'label_for' => 'gmac_google_map_api_key',
            'class' => 'dhl_settings_row'
        ]
    );
}
 
/**
 * register our dhl_parcel_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'dhl_parcel_settings_init' );
 


//CALLBACKS
/**
 * custom option and settings:
 * callback functions
 */
 
// developers section cb
 
// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function dhl_parcel_section_wsc_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Configuration for accessing the DHL Web Services', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

function dhl_parcel_section_pp_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Configuration of the pick-up point address', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

function dhl_parcel_section_ppai_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Pick-up point additional information', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

function dhl_parcel_section_ppwb_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Pick-up point working hours', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

function dhl_parcel_section_ppoc_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Pick-up point other configurations', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

function dhl_parcel_section_gmac_cb( $args ) {
    ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Google Maps Api Configuration', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></p>
    <?php
}

 
//FIELDS CALLBACKS

// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function dhl_parcel_textbox_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' size='40' type='text' value='<?php echo esc_html($options[$args['label_for']]); ?>' required>
    <?php
}

function dhl_parcel_textbox_hidden_value_cb( $args ) {
    // output the field
    $options = get_option( 'dhl_parcel_options' );
    if($options[$args['label_for']] != ""){
        ?>
        <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' size='40' type='text' placeholder='<?php echo esc_html(__('Hidding your DHL Web Service Key')); ?>'>
        <?php
    } else {
        ?>
        <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' size='40' type='text' required>
        <?php
    }
}

function dhl_parcel_textbox_not_required_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' size='40' type='text' value='<?php echo esc_html($options[$args['label_for']]); ?>'>
    <?php
}

function dhl_parcel_password_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' size='40' type='password' value='<?php echo esc_html($options[$args['label_for']]); ?>' required>
    <?php
}

function dhl_parcel_time_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' type='time' value='<?php echo esc_html($options[$args['label_for']]); ?>' required>
    <?php
}

function dhl_parcel_time_not_required_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    ?>
    <input id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]' type='time' value='<?php echo esc_html($options[$args['label_for']]); ?>'>
    <?php
}

function dhl_parcel_countries_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    $countries_obj = new WC_Countries();
    $countries = $countries_obj->__get('countries');

    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_attr( $args['label_for'] ); ?>]'>
    <?php
	foreach($countries as $key => $item) :
        $selected = ($options[$args['label_for']]==$key) ? 'selected="selected"' : '';
        ?>
		<option value='<?php echo esc_html($key) ?>'<?php echo esc_html($selected) ?>><?php echo esc_html($item) ?></option>;
        <?php
    endforeach;
    ?>
	</select>
    <?php
}

function dhl_parcel_dropdown_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field

    ?>
    <select id="<?php echo esc_html( $args['label_for'] ); ?>" name='dhl_parcel_options[<?php echo esc_html( $args['label_for'] ); ?>]'>
    <?php
	foreach($args['list'] as $key => $item) :
        $selected = ($options[$args['label_for']]==$key) ? 'selected="selected"' : '';
        ?>
		<option value='<?php echo esc_html($key) ?>'<?php echo esc_html($selected) ?>><?php echo esc_html($item) ?></option>;
        <?php
    endforeach;
    ?>
	</select>
    <?php
}

function dhl_parcel_checkbox_cb( $args ) {
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'dhl_parcel_options' );
    // output the field
    $a = $options[$args['label_for']];
    if($options[$args['label_for']]) { $checked = ' checked="checked" '; }
    ?>
    <input <?php $checked?> id="<?php echo esc_html( $args['label_for'] ); ?>" name="<?php echo esc_html( $args['label_for'] ); ?>" type='checkbox' />
    <?php
}


/**
 * top level menu
 */
function dhl_parcel_options_page() {
    // add top level menu page
    add_menu_page(
        'DHL Parcel Iberia Settings',
        'DHL Parcel Iberia Settings',
        'manage_woocommerce',
        'dhl_parcel_iberia_woocommerce_plugin',
        'dhl_parcel_options_page_html'
    );
}
 
/**
 * register our dhl_parcel_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'dhl_parcel_options_page' );
 

function dhl_parcel_options_validator($opts){  
    //On the first call saves the key
    if($opts['wsc_key'] == ""){
        $options = get_option( 'dhl_parcel_options' );
        $opts['wsc_key'] = $options['wsc_key'];
    } 
    return $opts;
}

/**
 * top level menu:
 * callback functions
 */
function dhl_parcel_options_page_html() {
    
    // add error/update messages
    
    // check if the user have submitted the settings
    // wordpress will add the "settings-updated" $_GET parameter to the url
    if ( isset( $_GET['settings-updated'] ) ) {

        // add settings saved message with the class of "updated"
        add_settings_error( 'dhl_parcel_messages', 'dhl_parcel_message', __( 'Settings Saved', 'dhl_parcel_iberia_woocommerce_plugin' ), 'updated' );
    }
    
    // show error/update messages
    settings_errors( 'dhl_parcel_messages' );
    ?>
    <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
    <?php
    // output security fields for the registered setting "dhl_parcel"
    settings_fields( 'dhl_parcel_iberia_woocommerce_plugin' );
    // output setting sections and their fields
    // (sections are registered for "dhl_parcel", each field is registered to a specific section)
    do_settings_sections( 'dhl_parcel_iberia_woocommerce_plugin' );
    // output save settings button
    submit_button( 'Save Settings' );
    ?>
    </form>
    </div>
    <?php
}