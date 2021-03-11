<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	$field    = $this->get_field_key( $key );

	$options_based_on = apply_filters( 'dhl_parcel_shipping_method_rule_options_based_on', array(
			'Price'  	=> __( 'Price', 'dhl_parcel_iberia_woocommerce_plugin' ),
			'Weight'  	=> __( 'Weight', 'dhl_parcel_iberia_woocommerce_plugin' ),
	));


	$key = 'method_rules[xxx][based_on]';
	$args = array(
			'type' 		=> 'select',
			'options' 	=> $options_based_on,
			'return' 	=> true,
	);
	$value = 'none';
	$field_based_on = woocommerce_form_field( $key, $args, $value );

	$key = 'method_rules[xxx][min]';
	$args = array(
			'type' 		 	=> 'text',
			'return' 	 	=> true,
			'input_class'	=> array( 'wc_input_price' ),
	);
	$value = '';
	$field_min = woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );

	$key = 'method_rules[xxx][max]';
	$args = array(
			'type' 		=> 'text',
			'return' 	=> true,
			'input_class'	=> array( 'wc_input_price' ),
	);
	$value = '';
	$field_max = woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );

	$key = 'method_rules[xxx][cost]';
	$args = array(
			'type' 		=> 'text',
			'return' 	=> true,
			'input_class'	=> array( 'wc_input_price' ),
	);
	$value = '';
	$field_cost_per_order = woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );

	$field_id = '<label id="method_rules[xxx][id]" name="method_rules[xxx][id]"></label> ';

	$count_rules = 0;
?>

<tr valign="top">
	<th class="forminp" colspan="2">
		<label for="<?php echo esc_attr( $field ); ?>"><?php echo $data['title']; ?></label>
	</th>
</tr>

<tr valign="top">
    <td colspan="2" style="padding:0;">
        <table id="<?php echo esc_attr( $field ); ?>" class="dhl_parcel_shipping_method_rules wc_input_table sortable widefat">
            <thead>
            	<tr>
					<th class="id">
            		    <?php esc_html(_e( 'Id', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>
            		    <span class="woocommerce-help-tip" data-tip="<?php esc_html(_e( 'The shipping rule id', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>"></span>
                    </th>
            		<th class="based_on">
            		    <?php esc_html(_e( 'Based on', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>
            		    <span class="woocommerce-help-tip"  data-tip="<?php esc_html(_e( 'Shipping cost will be calculated based on the selected parameter.', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>"></span>
                    </th>
            		<th class="min">
						<label>
						<?php esc_html(_e( 'Min', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>
						<span class="woocommerce-help-tip" data-tip="<?php esc_html(_e( 'Enter minimum value for the &quot;Based on&quot; parameter. Value based on the price will be calculated by WooCommerce tax settings &quot;Display prices during cart and checkout&quot;', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>"></span>
						</label>
					</th>
            		<th class="max">
            			<?php esc_html(_e( 'Max', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>
            			<span class="woocommerce-help-tip" data-tip="<?php esc_html(_e( 'Enter maximum value for the &quot;Based on&quot; parameter. Value based on the price will be calculated by WooCommerce tax settings &quot;Display prices during cart and checkout&quot;', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>"></span>
            		</th>
            		<th class="cost" style="text-align: center;" >
            			<?php esc_html(_e( 'Cost per Order', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>
            			<span class="woocommerce-help-tip" data-tip="<?php esc_html(_e( 'Enter shipment cost for this rule.', 'dhl_parcel_iberia_woocommerce_plugin' )); ?>"></span>
					</th>

            	</tr>
            </thead>
            <tbody>
            	<?php if ( isset( $data['default'] ) ) : ?>
            		<?php foreach ( $data['default'] as $key => $rule ) : $count_rules++; ?>
            			<tr id ="tr_<?php echo($count_rules) ?>">
							<td class="id">
								<!--label id="method_rules[<?php /*echo esc_html($count_rules) ?>][id]" name="method_rules[<?php echo($count_rules) ?>][id]"><?php echo esc_html($rule['id']) */?></label-->
								<?php
            						$key = 'method_rules[' . $count_rules . '][id]';
            						$args = array(
            							'type' 		=> 'text',
            						);
            						$value = '';
            						if ( isset( $rule['id'] ) ) {
            							$value = $rule['id'];
            						}
            						woocommerce_form_field( $key, $args, $value );
            					?>
							</td>
            				<td class="based_on">
            					<?php
            						$key = 'method_rules[' . $count_rules . '][based_on]';
            						$args = array(
            							'type' 		=> 'select',
            							'options' 	=> $options_based_on,
            						);
            						$value = '';
            						if ( isset( $rule['ruleCriteria'] ) ) {
            							$value = $rule['ruleCriteria'];
            						}
            						woocommerce_form_field( $key, $args, $value );
            					?>
            				</td>
            				<td class="min sp_header">
            					<?php
            						$key = 'method_rules[' . $count_rules . '][min]';
            						$args = array(
            								'type' 			=> 'text',
            								'input_class'	=> array( 'wc_input_price' ),
            						);
            						$value = '';
            						if ( isset( $rule['min'] ) ) {
            							$value = $rule['min'];
            						}
            						woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );
            					?>
            				</td>
            				<td class="max">
            					<?php
            						$key = 'method_rules[' . $count_rules . '][max]';
            						$args = array(
            								'type' 			=> 'text',
            								'input_class'	=> array( 'wc_input_price' ),
            						);
            						$value = '';
            						if ( isset( $rule['max'] ) ) {
            							$value = $rule['max'];
            						}
            						woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );
            					?>
            				</td>
            				<td class="cost">
            					<?php
            						$key = 'method_rules[' . $count_rules . '][cost]';
            						$args = array(
            								'type' 			=> 'text',
            								'input_class'	=> array( 'wc_input_price' ),
            						);
            						$value = '';
            						if ( isset( $rule['cost'] ) ) {
            							$value = $rule['cost'];
            						}
            						woocommerce_form_field( $key, $args, wc_format_localized_price( $value ) );
            					?>
							</td>
            			</tr>
            		<?php endforeach; ?>
            	<?php endif; ?>
            </tbody>

            <tfoot>
            	<tr>
            		<th colspan="99">
            			<a id="insert_rule" href="#" class="button plus insert"><?php _e( 'Insert rule', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></a>
            			<a id="remove_rules" href="#" class="button minus"><?php _e( 'Delete selected rule', 'dhl_parcel_iberia_woocommerce_plugin' ); ?></a>
            		</th>
            	</tr>
            </tfoot>
        </table>

        <script type="text/javascript">

            function append_row( id ) {
            	var code = '<tr id="tr_'+id+'">\
								<td class="id">\
								<?php echo (str_replace( "'", '"', str_replace( "\r", "", str_replace( "\n", "",  $field_id)) ) ); ?> \
            					</td> \
            					<td class="based_on">\
            						<?php echo (str_replace( "'", '"', str_replace( "\r", "", str_replace( "\n", "", $field_based_on ) ) )); ?> \
            					</td>\
            					<td class="min">\
            					    <?php echo (str_replace( "'", '"', str_replace( "\r", "", str_replace( "\n", "", $field_min ) ) ) ); ?> \
            					</td>\
            					<td class="max">\
            					    <?php echo (str_replace( "'", '"', str_replace( "\r", "", str_replace( "\n", "", $field_max ) ) ) ); ?> \
            					</td>\
            					<td class="cost">\
            					   <?php echo (str_replace( "'", '"', str_replace( "\r", "", str_replace( "\n", "", $field_cost_per_order ) ) ) ); ?> \
            					</td>\
            				</tr>';
            	var code2 = code.replace(/xxx/g, id );
            	var $tbody = jQuery('#<?php echo esc_attr( $field ); ?>').find('tbody');
            	$tbody.append( code2 );
            }
            jQuery(document).ready(function() {

            	var tbody = jQuery('#<?php echo esc_attr( $field ); ?>').find('tbody');
            	var append_id = <?php echo esc_html($count_rules) ?>;
            	var size = tbody.find('tr').size();
				//Insert rule to table
            	jQuery('#insert_rule').click(function() {
            		append_id = append_id+1;
					append_row(append_id);
					addMaxWeigthListener(append_id);
            		return false;
				});
				//Select row
				var tid='';
				jQuery(document).on('click', '#<?php echo esc_attr( $field ); ?> tbody tr', function (event) {
					tid=jQuery(this).attr('id');
					jQuery('.selected').removeClass('selected');
    				jQuery('#' + tid).addClass("selected");
				});
				//Remove the row that has been selected
            	jQuery('#remove_rules').click(function() {
            		if (jQuery('#' + tid).length) {
						jQuery('#' + tid).remove();
					}
            	});

				//Save table
				jQuery("#btn-ok").click(function() {

					//Save the table as a json in order to access it later with the options API
					var rules = [];
					for (i = 0; i < append_id; i++) {

						var j = i+1;
						var id = jQuery("#method_rules\\[" + j + "\\]\\[id\\]").val();
						var based_on = jQuery("#method_rules\\[" + j + "\\]\\[based_on\\]").val();
						var min = jQuery("#method_rules\\[" + j + "\\]\\[min\\]").val();
						var max = jQuery("#method_rules\\[" + j + "\\]\\[max\\]").val();
						var cost = jQuery("#method_rules\\[" + j + "\\]\\[cost\\]").val();

						//Verify if the row exists
						if (jQuery("#method_rules\\[" + j + "\\]\\[based_on\\]").length){
							rules[i] = {"id":id,"ruleCriteria":based_on,"min":min,"max":max,"cost":cost};
						}

					}
					rules = JSON.stringify(rules);
					var text_field = jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_shipping_rules_text").val(rules);

				});
				//Javascript input validations
				//Max weigth field
				jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_max_weigth").blur(function() {
					 var value = jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_max_weigth").val();
					 if(value < 0){
						add_notice('<?php echo esc_html(__('Max weigth cant be lower that zero!', 'dhl_parcel'));?>');
					 } else {
						 remove_notices();
					 }
					 var higherMax = -1;
					 for (i = 0; i < append_id; i++) {
						var j = i+1
						var based_on = jQuery("#method_rules\\[" + j + "\\]\\[based_on\\]").val();
						var max = jQuery("#method_rules\\[" + j + "\\]\\[max\\]").val();
						if(max > higherMax && based_on == "Weight" ){
							higherMax = max;
						}
					 }
					 if(higherMax > value){
						add_notice('<?php echo esc_html(__('Max weigth cant be lower that the already inserted max weigth rules', 'dhl_parcel'));?>');
						jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_max_weigth").val(higherMax);
						remove_notice_with_timer();
					 }

				});
				//Default cost
				jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_default_cost").blur( function() {
					 var value = jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_default_cost").val();
					 if(value < 0){
						add_notice('<?php echo esc_html(__('Default cost cant be lower that zero!', 'dhl_parcel'));?>');

					 } else {
						remove_notices();
					 }

				});
				//Add listener to existing rules
				for (i = 0; i < append_id; i++) {
					j = i+1;
					addMaxWeigthListener(j);
				}

            	jQuery('#mainform').attr('action', '<?php echo remove_query_arg( 'added', add_query_arg( 'added', '1' ) ); ?>' );
			});
			function addMaxWeigthListener(id){
				//On DOM change to JQuery?
				document.getElementById("method_rules[" + id + "][max]").addEventListener("change", function(){
					var needs_comma =  '<?php echo wc_format_localized_price( '2.2' ); ?>' !='2.2';
					let maxField_value = jQuery(document.getElementById("method_rules[" + id + "][max]")).val().toString();
					let value = jQuery("#woocommerce_<?php echo esc_html($this->id) ?>_max_weigth").val().toString();
					var based_on = jQuery("#method_rules\\[" + id + "\\]\\[based_on\\]").val();
					maxField_value = parseFloat(maxField_value.replace(',', '.'));
					let value_parsed = parseFloat(value.replace(',', '.'));
					if(maxField_value > value_parsed && based_on == "Weight"){
						add_notice('<?php echo __('Weight field cant be higher that the defined Max Weight', 'dhl_parcel');?>');
						if(needs_comma){
							jQuery("#method_rules\\[" + id + "\\]\\[max\\]").val(value.replace('.', ','));
						}else{
							jQuery("#method_rules\\[" + id + "\\]\\[max\\]").val(value_parsed);
						}
						remove_notice_with_timer();
					}
					if(maxField_value == '0' && based_on == "Weight"){
						jQuery("#method_rules\\[" + id + "\\]\\[max\\]").val(value);
					}
				});
			}
			function add_notice(notice) {
				var body = jQuery(document).find('.wc-backbone-modal-header');
				var value = '<div class="notice notice-warning is-dismissible"><p><strong>'+notice+'</strong></p></div>';
				body.append(value);
			}
			function remove_notice_with_timer(){
				jQuery(".notice").delay(5000).fadeOut();
			}
			function remove_notices(){
				jQuery(".notice").remove();
			}
        </script>
<?php
	if( version_compare( WC()->version, '2.6.0', ">=" ) ) {
?>
<script type="text/javascript">
	<?php
		$zone            = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
		$shipping_method_woo = WC_Shipping_Zones::get_shipping_method( $instance_id );
		$content = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping' ) . '">' . __( 'Shipping Zones', 'woocommerce' ) . '</a> &gt ';
		$content .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . absint( $zone->get_id() ) ) . '">' . esc_html( $zone->get_zone_name() ) . '</a> &gt ';
		$content .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&instance_id=' .  $instance_id ) . '">' . esc_html( $shipping_method_woo->get_title() ) . '</a>';
		if ( isset( $data['method_title'] ) && $data['method_title'] != '' ) {
			$content .= ' &gt ';
			$content .= esc_html( $data['method_title'] );
		}
		else {
			$content .= ' &gt ';
			$content .= __( 'Add New', 'dhl_parcel_iberia_woocommerce_plugin' );
		}
	?>
	jQuery('#mainform h2').first().replaceWith( '<h2>' + '<?php echo $content; ?>' + '</h2>' );
</script>
<?php
	}
?>
    </td>
</tr>
