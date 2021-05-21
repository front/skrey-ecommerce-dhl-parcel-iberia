<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'plugins_loaded', 'dhl_cod_gateway_init', 11 );


function dhl_cod_gateway_init() {

	class WC_Gateway_DHL_COD extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			// Setup general properties.
			$this->setup_properties();

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Get settings.
			$this->enabled			  = $this->get_option( 'enabled' );
			$this->title              = $this->get_option( 'title' );
			$this->description        = $this->get_option( 'description' );
			$this->instructions       = $this->get_option( 'instructions' );


			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

			// Customer Emails.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}

		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                 = 'dhl_cod';
			$this->icon               = apply_filters( 'dhl_cod_icon', '' );
			$this->method_title       = __( 'DHL Parcel Cash on Delivery', 'dhl_parcel_iberia_woocommerce_plugin' );
			$this->method_description = __( 'Have your customers pay upon delivery.', 'dhl_parcel_iberia_woocommerce_plugin' );
			$this->has_fields         = true;

		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {



			$this->form_fields = apply_filters( 'wc_dhl_cod_form_fields', array(
				'enabled'			 => array(
					'title'       => __( 'Enabled', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'type'        => 'checkbox',
					'description' => __( 'Enable DHL COD Payment method', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'title'              => array(
					'title'       => __( 'Title', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'type'        => 'text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'default'     => __( 'DHL Cash On Delivery', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'desc_tip'    => true,
				),
				'description'        => array(
					'title'       => __( 'Description', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website.', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'default'     => __( 'Pay upon delivery.', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'desc_tip'    => true,
				),
				'instructions'       => array(
					'title'       => __( 'Instructions', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page.', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'default'     => __( 'Pay upon delivery.', 'dhl_parcel_iberia_woocommerce_plugin' ),
					'desc_tip'    => true,
				),
			));
		}

		public function is_available() {
			$order          = null;
			$needs_shipping = false;

			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				if ( 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			} elseif ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			}

			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

			// Only apply if is home delivery.
			if ( WC()->session != null ) {
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( ! empty( $chosen_shipping_methods_session ) ) {
					global $woocommerce;
					$dhl_client = new DhlClient();
					$cart = $woocommerce->cart;

					if ( in_array( "dhl_normal_shipping_method", $chosen_shipping_methods_session ) ) {
						$isCODAvailable = $dhl_client->isCodAvailable( $cart, false );
					} else if ( in_array( "dhl_service_point_shipping_method", $chosen_shipping_methods_session ) ) {
						$isCODAvailable = $dhl_client->isCodAvailable( $cart, true );
					} else {
						$isCODAvailable = false;
					}

					if ( ! $isCODAvailable || ! $needs_shipping ) {
						return false;
					}
				}
			}

			return parent::is_available();
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Mark as processing or on-hold (payment won't be taken until delivery).
			$order->update_status( apply_filters( 'woocommerce_dhl_cod_process_payment_order_status', 'on-hold' , $order ), __( 'Payment to be made upon delivery.', 'dhl_parcel_iberia_woocommerce_plugin' ) );

			// Reduce stock levels.
			wc_reduce_stock_levels( $order_id );

			// Remove cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}


		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}

		}

		public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
			if ( $order && 'cod' === $order->get_payment_method() ) {
				$status = 'completed';
			}
			return $status;
		}


		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}

		public function validate_fields(){
			return true;

		}
	}
}
