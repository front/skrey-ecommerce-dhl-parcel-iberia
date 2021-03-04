<?php
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}

//Save changes hook
require_once( __DIR__. '/../utils/database_handler.php');
require_once( __DIR__. '/../utils/utils.php');

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function dhl_service_point_init() {
		if ( ! class_exists( 'WC_DHL_Service_Point_Shipping_Method' ) ) {
			class WC_DHL_Service_Point_Shipping_Method extends WC_Shipping_Method {

				const SHIPPING_METHOD = "Service Point";

				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct( $instance_id = 0) {
                    $this->id                 = 'dhl_service_point_shipping_method'; // Id for your shipping method. Should be unique.
                    $this->instance_id        = absint($instance_id);
                    $this->domain             = 'dhl_sps';
					$this->method_title       = __( 'DHL Parcel - Service Point' , $this->domain);  // Title shown in admin
					$this->method_description = __( 'Enables pickup on a dhl service point' , $this->domain); // Description shown in admin
                    $this->supports = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
                    );

                    $this->init();
				}
				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
                    $this->enable = $this->get_option('enabled', $this->domain);
                    $this->title   = $this->get_option( 'title', $this->domain );
                    $this->info    = $this->get_option( 'info', $this->domain );
					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
					
					//Add hook for when updating settings are called
					$option_name = 'pre_update_option_woocommerce_'.$this->id.'_' .$this->instance_id.'_settings';
					add_filter( $option_name , array($this,'before_options_update_shipping'), 10, 2);
                }
                
                /**
                * Initialise Gateway Settings Form Fields
                */
                function init_form_fields() {
                    $this->instance_form_fields = array(
                        'title' => array(
                            'type'          => 'text',
                            'title'         => __('Title', $this->domain),
                            'description'   => __( 'Title to be displayed on site.', $this->domain ),
                            'default'       => __( 'DHL Service Point Shipping ', $this->domain ),
						),
						'max_weigth' => array(
                            'type'          => 'number',
                            'title'         => __('Max Weight', $this->domain),
                            'description'   => __( 'Maximum weight accepted per order.', $this->domain ),
                            'default'       => 9000,
						),
						'default_cost' => array(
                            'type'          => 'number',
                            'title'         => __('Default Cost', $this->domain),
                            'description'   => __( 'Default cost of shipping in case no rules are specified/applicable', $this->domain ),
                            'default'       => 99.99,
						),
						'shipping_rules' => array(
							'type'			=> 'shipping_rules',
							'title'         => __('Shipping Rules', $this->domain),
                            'description'   => __( 'Defined shipping rules.', $this->domain ),
                            'default'       => array(),
						),
						'shipping_rules_text' => array( //Hidden for storing the table info
                            'type'          => 'text',
                            'title'         => __('', $this->domain),
                            'description'   => __( '', $this->domain ),
							'default'       => __( '', $this->domain ),
							'css'			=> 'display : none;'
                        ),
                    );
                } // End init_form_fields()

				
				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = array()) {
					return $this->calculate_shipping_optimized($package);
				}
				/*
				* calculate_shipping_optimized function.
				*
				* @access public
				* @param mixed $package
				* @return void
				*/
			   public function calculate_shipping_optimized( $package = array()) {
				  	global $woocommerce;
				  	$dbh = new Database_Handler();

				  	$zone = WC_Shipping_Zones::get_zone_matching_package($package);
				  	$vars = get_object_vars($this);

				  	$max_weight = $this->get_option( 'max_weigth', $this->domain );
				  	$weight = $woocommerce->cart->cart_contents_weight;
				  	$price = $package[ 'contents_cost' ];
				  	if($weight > $max_weight){
					   return null;
				  	}

				  	$rules_cost = $dbh->get_shipping_rules_by_zone_id_and_criteria($zone->get_id(),$weight,$price, $vars['id']);

				  	$final_cost = 0;
					if(empty($rules_cost)){
						$final_cost = $this->get_option( 'default_cost', $this->domain );
					} else {
						foreach($rules_cost as $rule){
							if( $final_cost == 0){
								$final_cost = $rule['cost'];
							} else if($final_cost > $rule['cost']) {
								$final_cost = $rule['cost'];;
							}
						}
					}

				 	$rate = array(
					   'id' => $this->id,
					   'label' => $this->title,
					   'cost' => $final_cost,
					   'calc_tax' => 'per_order'
				 	);
				 	// Register the rate
				 	$this->add_rate( $rate );
			   }
				public function generate_shipping_rules_html( $key, $data ) {

					$vars = get_object_vars($this);
					$instance_id = $vars['instance_id'];
					$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id);
					$locations = $zone->get_zone_locations();
					$dbh = new Database_Handler(); 

					$shipping_rules = $dbh->get_shipping_rules_by_zone_id($zone->get_id(), $vars['id']);
					$data['default'] = $shipping_rules;

					$max_id = $dbh->get_next_id();

					ob_start();
					include('views/html-shipping-method-rules-table.php');
					return ob_get_clean();
				}

				function before_options_update_shipping( $new, $old ) {
					//validate input
					
					$dbh = new Database_Handler(); 

					//Delete rules
					$deleted_rules=$this->deleted_rules( $new, $old );
					foreach($deleted_rules as $id){
						$dbh->delete_shipping_rule($id);

					}

					//Get data to insert
					
					$vars = get_object_vars($this);
					$instance_id = $vars['instance_id'];

					$rules = json_decode($new["shipping_rules_text"]);
					$updated_rules = array();
					foreach($rules as $rule){
						$rule = json_decode(json_encode($rule), True);
						$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id);
						$locations = $zone->get_zone_locations();
						//save the rules into the database
						$row_id = $dbh->saveShippingRule($vars['id'], $rule, $locations, $zone->get_id());
						
						$rule['id'] = $row_id;
						array_push($updated_rules,$rule);

					}

					//Update option field
					$new['shipping_rules_text'] = json_encode($updated_rules);

					return $new;
				}

				function validate_input_data( $data ) {
					//validate max_wei
					$max_weight = $new["max_weight"];
					if($max_weight<0){
						?>	
						<script type="text/javascript"> alert(<?php echo esc_html(_e('Max Weight needs to be higher or equal to 0', $dhl_parcel))?>); </script>
						<?php
					}
					$default_cost = $new["default_cost"];
					if($default_cost<0){
						?>	
						<script type="text/javascript"> alert(<?php echo esc_html(_e('Default cost needs to be higher or equal to 0', $dhl_parcel )) ?>) </script>
						<?php
					}
					
					return $data;
				}

				/**
				 * Compares de rules in old and new, and returns an array with the ids that are missing from the new table
				 */
				function deleted_rules( $new, $old ) {

					$new_rules = json_decode($new["shipping_rules_text"]);
					$old_rules = json_decode($old["shipping_rules_text"]);
					
					//Ids on the new table
					$new_ids = array();
					foreach($new_rules as $rule){
						$rule = json_decode(json_encode($rule), True);

						$id = $rule['id'];
						if(strcmp("",$id) ){
							array_push($new_ids,$id);
						}
					}

					//Ids on the old table
					$old_ids = array();
					foreach($old_rules as $rule){
						$rule = json_decode(json_encode($rule), True);

						$id = $rule['id'];
						if( strcmp("",$id) ){
							array_push($old_ids,$id);
						}
					}

					$deleted_ids = array();
					foreach($old_ids as $id){
						if(!in_array($id,$new_ids)){
							array_push($deleted_ids,$id);
						}
					}

					return $deleted_ids;

				}
			}
		}
    }
    
    add_action( 'woocommerce_shipping_init', 'dhl_service_point_init' );
    
	function add_dhl_service_point_shipping_method( $methods ) {
		//Check if the current user is admin and if the current page is "Order Details" 
        if (is_admin() && function_exists('get_current_screen')) { 
			$current_page_id = get_object_vars(get_current_screen())['id'];
			if(!strcmp($current_page_id,"shop_order")){
				return $methods;
			}
		 }
		$methods['dhl_service_point_shipping_method'] = 'WC_DHL_Service_Point_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_dhl_service_point_shipping_method' );

	
}