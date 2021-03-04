<?php

// Adding Meta container admin shop_order pages
add_action( 'add_meta_boxes', 'mv_add_meta_boxes' );
if ( ! function_exists( 'mv_add_meta_boxes' ) )
{
    function mv_add_meta_boxes()
    {
        add_meta_box( 
            'reference-field', 
            __('DHL Reference Field','dhl_parcel_iberia_woocommerce_plugin'), 
            'mv_add_other_fields_for_packaging', 
            'shop_order', 
            'side', 
            'core' );
    }
}

// Adding Meta field in the meta container admin shop_order pages
if ( ! function_exists( 'mv_add_other_fields_for_packaging' ) )
{
    function mv_add_other_fields_for_packaging()
    {
        global $post;

        $options = get_option( 'dhl_parcel_options' );

        //Check if label has been created
        $dbh = new Database_Handler();
        $label = $dbh->get_labels_by_order_id($post->ID,false);
        if(count($label)){
            $is_editable = false;
        } else {
            $is_editable = true;
        }

        if($options['ppoc_reference_field'] == "manual"){
            $meta_field_data = get_post_meta( $post->ID, '_reference_field', true ) ? get_post_meta( $post->ID, '_reference_field', true ) : '';
        } else if ($options['ppoc_reference_field'] == "order_id"){
            if(get_post_meta( $post->ID, '_reference_field', true )){
                $meta_field_data = get_post_meta( $post->ID, '_reference_field', true );
            } else {
                //First visit
                $meta_field_data = $post->ID;
                update_post_meta( $post->ID, '_reference_field', $meta_field_data );
            }
        }

        echo dhl_parcel_textbox($meta_field_data, $is_editable);

    }

    function dhl_parcel_textbox($reference_field_data, $is_editable) {
        // get the value of the setting we've registered with register_setting()
        if($is_editable ){
            ?>
            <input id="order_dhl_reference_field" name='dhl_reference_field' type='text' maxlength="35" value='<?php echo esc_attr($reference_field_data) ?>'>
            <button class="button wc-reload"><span>Apply</span></button>
            <?php
        } else {
            ?>
            <input id="order_dhl_reference_field" name='dhl_reference_field' style="width:100%" type='text' value='<?php echo esc_attr($reference_field_data) ?>' readonly>
            <?php
        }
    }
}

// Save the data of the Meta field
add_action( 'save_post', 'mv_save_wc_order_other_fields', 10, 1 );
if ( ! function_exists( 'mv_save_wc_order_other_fields' ) )
{

    function mv_save_wc_order_other_fields( $post_id ) {

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( 'page' == $_POST[ 'post_type' ] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
        // --- Its safe for us to save the data ! --- //

        // Sanitize user input  and update the meta field in the database.
        update_post_meta( $post_id, '_reference_field', $_POST[ 'dhl_reference_field' ] );
    }
}