//Globals
var dhl_parcel_locs = new Array();
var infowindow = null;
var days_lang=new Array( "Sunday"
                        ,"Monday"
                        ,"Tuesday"
                        ,"Wednesday"
                        ,"Thursday"
                        ,"Friday"
                        ,"Saturday");
var select_lang = "Select";
var working_hours_label="Working Hours";
var parcels_map = null;
var map_markers = new Array();
var path = plugin_path.path;
var latlngbounds= null;

var defaultText = "Please select a Service Point";
var error_alert = "Something went wrong";

var home_location = null;
var new_home_location = null;

function searchButtonClick() {
    searchQuery=jQuery('#parcel_search_txt').val();
    dhl_parcel_locs = [];
    jQuery('#over_map').hide();
    initParcelMap("","","", home_location.country,searchQuery);
}

function initParcelMap(cust_address, codePostal, city, cust_country,searchQuery)
{
    dhl_parcel_locs = [];
    parcels_map=initMap();
    createHomeMarker(home_location.address+' , '+home_location.city+' , '+home_location.country);
    initParcelMarkers(cust_address, codePostal, city, cust_country,searchQuery);

};


function initMap(){
    var latlang = new google.maps.LatLng(40.6651453,-4.8732714);
    latlngbounds= new google.maps.LatLngBounds();
    var myOptions = {
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        center: latlang,
        zoom: 10,
    }
    map = new google.maps.Map(document.getElementById("dhl_parcel_map"), myOptions);
    return map;
};

function createHomeMarker(address)
{   
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode( { "address": address}, function(results, status) {
        latlngbounds.extend(results[0].geometry.location);
        if (status == google.maps.GeocoderStatus.OK) {
            map.setCenter(results[0].geometry.location);
            new google.maps.Marker({
                map: parcels_map,
                position: results[0].geometry.location,
                icon:path+'assets/img/home.png',
                zIndex:99999999
            }); 
        }
    }); 
};

function initParcelMarkers(address, cp, city, cust_country,searchQuery)
{    
        var data = {
            'action' : 'get_parcel_locations',
            'codePostal': cp,
            'address': address,
            'city': city,
            'country': cust_country,
            'searchQuery': searchQuery,
        };
        
        jQuery.ajax({
            url: ajax_obj.my_ajax_url,
            data: data,
            type: 'POST',
            success: createServicePoints
        });
};

function createServicePoints(json)
{
    var htmlForTxtSection='';
    var ldata=JSON.parse(json);
    if(ldata.error){
        showError(ldata.error);
    }else{

        for(var i=0;i<ldata.length;i++)
        {

            createParcelMarker(ldata[i]);

            
            dhl_parcel_locs[ldata[i].id]=ldata[i];

            htmlForTxtSection+='<div class="checkbox"><label class="service_points_label" ><input type="radio" class="service_points_radio" name="dhl_loc_select" id="parcel_shop_id'
                +ldata[i].id+'" value="'
                +ldata[i].id+'"';
            if(i==0) {
                htmlForTxtSection+=" checked";
                setParcelAndUpdatePrice(ldata[i].id,dhl_parcel_locs[ldata[i].id]);
            }

            htmlForTxtSection +='/> '+ldata[i].name
                +' - '+ldata[i].address.street+' '+ldata[i].address.number+' - '+ldata[i].address.postalCode+' '+ldata[i].address.city+'</label></div>';
        }

        
        jQuery('#parcel_loc_txt').html(htmlForTxtSection);

        if(ldata.length<1) {
            jQuery('#over_map').show();
        }else{
            jQuery('#over_map').hide();
            setParcelAndUpdatePrice(ldata[0].id,dhl_parcel_locs[ldata[0].id]);
            changeSelectedServicePointField(dhl_parcel_locs[ldata[0].id]);
        }
        

        jQuery('input[name="dhl_loc_select"]').change(function() {
            var parcel_shop_id=jQuery('input[name="dhl_loc_select"]:checked').val();
            setParcelAndUpdatePrice(parcel_shop_id, dhl_parcel_locs[parcel_shop_id]);
            changeSelectedServicePointField(dhl_parcel_locs[parcel_shop_id]);
            openParcelInfoWindow(parcel_shop_id);
        });
        
    }

    parcels_map.fitBounds(latlngbounds);
};

function createParcelMarker(dhl_parcel)
{
    var pos=new google.maps.LatLng(dhl_parcel.geoLocation.latitude, dhl_parcel.geoLocation.longitude);
    latlngbounds.extend(pos);
    map_markers[dhl_parcel.id]=new google.maps.Marker({
        map: parcels_map,
        position: pos,
        icon:path+'assets/img/postal_dhl.png'
    });

    google.maps.event.addListener(map_markers[dhl_parcel.id], 'click',
        function() {openParcelInfoWindow(dhl_parcel.id);});
};
function openParcelInfoWindow(parcel_shop_id) {

    if (infowindow) infowindow.close();
    var iwcontent='';

        iwcontent='<div class="pointParcel"><h4>'
            +dhl_parcel_locs[parcel_shop_id].name+'</h4><class="address">'+dhl_parcel_locs[parcel_shop_id].address.street+' '+dhl_parcel_locs[parcel_shop_id].address.number+'<br/ >'+dhl_parcel_locs[parcel_shop_id].address.postalCode
            +' '+dhl_parcel_locs[parcel_shop_id].address.city+'</p>';

        if(dhl_parcel_locs[parcel_shop_id].hasOwnProperty("openingTimesByDay")){
            iwcontent = iwcontent + '<h5>'+working_hours_label+'</h5><table><tbody>'
            +'<tr class="first_item item"><td>'+days_lang[0]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,0)
            +'</td></tr><tr class="alternate_item"><td>'+days_lang[1]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,1)
            +'</td></tr><tr class="item"><td>'+days_lang[2]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,2)
            +'</td></tr><tr class="alternate_item"><td>'+days_lang[3]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,3)
            +'</td></tr><tr class="item"><td>'+days_lang[4]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,4)
            +'</td></tr><tr class="alternate_item"><td>'+days_lang[5]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,5)
            +'</td></tr><tr class="last_item item"><td>'+days_lang[6]+'</td><td>'
            +openingHoursByDay(parcel_shop_id,6)
            +'</td></tr></tbody></table>';
        }

        iwcontent = iwcontent +'<p class="text-right"><input type="hidden" name="parcel_shop_id" value="'+dhl_parcel_locs[parcel_shop_id].id
            +'"/><a class="button_large buttonSelectParcel" href="javascript:;" class="pull-right">'+select_lang+'</a></p>'
            +'</div>';
            
    infowindow = new google.maps.InfoWindow({
        content: iwcontent
    });

    infowindow.open(parcels_map,map_markers[parcel_shop_id]);
};

function openingHoursByDay(parcel_shop_id,day){
    var htlm='<td>';
    var a = dhl_parcel_locs;

    if(dhl_parcel_locs[parcel_shop_id].hasOwnProperty("openingTimesByDay")){
        if(dhl_parcel_locs[parcel_shop_id].openingTimesByDay[day]){
            var array= dhl_parcel_locs[parcel_shop_id].openingTimesByDay[day];
            array.forEach(function(session){
                htlm +=
                +session.timeFrom.split(':')[0]+':'+session.timeFrom.split(':')[1]
                + ' - '+session.timeTo.split(':')[0]+':'+session.timeTo.split(':')[1]+'    |   ';
            });
        }
    }
    else{
        htlm+='<td>'
        
        +'</td>';
    }
    return htlm;
}


function buttonSelectAction(target, e) {
    var parcel_shop_id=jQuery(this).parent().children('input').val();
    var mObj=jQuery('#parcel_shop_id'+parcel_shop_id+'');
    mObj.attr('checked', 'checked');
    mObj.click();

    setParcelAndUpdatePrice(parcel_shop_id, dhl_parcel_locs[parcel_shop_id]);
    changeSelectedServicePointField(dhl_parcel_locs[parcel_shop_id]);
    jQuery.scrollTo(mObj);
};

function showError(error){
    alert(error_alert +': '+ error);
}

function setParcelAndUpdatePrice(parcel_shop_id,servicePoint)
{   
    data={
        'action' : 'set_service_point_and_update_shipping_price',
        'parcel_shop_id' :parcel_shop_id,
        'codePostal': servicePoint.address.postalCode,
        'address': servicePoint.address.street,
        'city': servicePoint.address.city,
        'country': servicePoint.address.countryCode,
    }
    
    jQuery.ajax({
        url: ajax_obj.my_ajax_url,
        data: data,
        type: 'POST',
        success : reloadCheckout,
        fail : ReplyHandle
    });
}

function ReplyHandle(json){
    json = JSON.parse(json);
    if(json.error){
        showError(json.error);
    }
}

function getHomeLocation(){
    var data = {
        'action' : 'update_home_location'
    };
    
    return jQuery.ajax({
        url: ajax_obj.my_ajax_url,
        data: data,
        type: 'POST',
        success : saveHomeLocation
    });
}

function saveHomeLocation(json){

    var response = JSON.parse(json);
    new_home_location = {
        address : response["address"],
        codePostal : response["zipcode"],
        city : response["city"],
        country : response["countryCode"]
    }
    
}

function reloadCheckout(){
    jQuery(document.body).trigger("update_checkout");
}

function changeSelectedServicePointField(service_point_info){
    jQuery("#selected_service_point_sp").text(service_point_info.name+' - '+service_point_info.address.street+' '+service_point_info.address.number+' - '+service_point_info.address.postalCode+' '+service_point_info.address.city); 
}

jQuery(document).ready(function() {
    jQuery("#searchBTN").click(function() {
        searchButtonClick();
    });
    jQuery("#dhl_parcel_map").click(function(e) {
        if( jQuery(e.target).is(".buttonSelectParcel") ){
            buttonSelectAction.call(e.target,e);
        }
    });
    //Disable submit order on "enter"
    jQuery("form").keypress(function(e) {
        //Enter key
        if (e.which == 13) {
          return false;
        }
    });
    //"Enter" on the text box for searching
    jQuery("#parcel_search_txt").keypress(function(e) {
        if(e.which == 13) {
            searchButtonClick();
        }
      });
    
});

jQuery( document ).on( 'updated_checkout', function( e, data ) {
    jQuery( function( $ ) {
        jQuery( '#dhl_parcel' ).hide();
        var shipping_methods = {};
        $( 'select.shipping_method, input[name^="shipping_method"][type="radio"]:checked, input[name^="shipping_method"][type="hidden"]' ).each( function() {
            shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
        } );
        //Only one shipping method chosen
        if ( Object.keys( shipping_methods ).length == 1 ) {
            var shipping_method = $.trim( shipping_methods[0] );
            if ( shipping_method == "dhl_service_point_shipping_method" ) {
                $( '#dhl_parcel' ).show();
                if(parcels_map != null ){
                    parcels_map.fitBounds(latlngbounds);
                }
            }
        }
    } );
    //Update map
    jQuery(function(){
        getHomeLocation().done( function(){
            if( !(JSON.stringify(new_home_location) === JSON.stringify(home_location)) ){
                home_location = new_home_location;
                initParcelMap(home_location.address,home_location.codePostal,home_location.city,home_location.country,'');
            }
        });
    });
});

