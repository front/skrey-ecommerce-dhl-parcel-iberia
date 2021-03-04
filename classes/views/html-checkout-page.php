<?php

//Add our DIV to the checkout
//Only is called once ( on loading of page)
function dhl_woocommerce_review_order_before_payment() {

    $shipping_methods = dhl_get_shipping_methods();
    if ( count( $shipping_methods )>0 ) {
        ?>
        <div id="dhl_parcel" >

            <p class="form-row form-row-wide">
                <label for="dhl_parcel_point">
                    <?php _e( 'Choose a DHL service point', 'dhl_parcel_iberia_woocommerce_plugin' ); ?>
                    <span class="dhl_parcel-clear"></span>
                </label>
                <span class="dhl_parcel-points-fragment"></span>
            </p>
         
            <div class="dhl_parcel-clear"></div>
            
            <div class="form-row form-row-wide">
                <div class="" id="selected_service_point_div">
                    <label for="selected_service_point">
                        <?php _e( 'Selected Service Point :', 'dhl_parcel_iberia_woocommerce_plugin' ); ?>
                        <label id="selected_service_point_sp"> </label>
                    </label>
                </div>
            </div>

            <div class="row">
                <div id="dhl_search_control">
                    <div class="input-group search-div-dhl">
                        <input type="text"  class="form-control" value="" id="parcel_search_txt"/>
                        <span class="input-group-btn">
                            <button class="btn btn-search-dhl" id="searchBTN" type="button"><?php _e("Search", 'dhl_parcel') ?></button>
                        </span>
                    </div>  
                </div>
            </div>

            <div class="map_wrapper">
                <div id="dhl_parcel_map"></div>
                <div class="over_map" style="display:none" id="over_map">
                    <h4 class="no_results">No results</h4>
                </div>
            </div>
            
            <div id="parcel_loc_cont">
                <h4>Nearby Servicepoints:</h4>
                <div class="parcel_loc_txt" id="parcel_loc_txt"></div>
            </div>
        </div> 
    
        <?php
    }
}


function dhl_get_shipping_methods() {
    return array("dhl_service_point_shipping_method");
}

?>