<div id="trackAndTraceModal" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2><?php echo esc_html(__("DHL Track and Trace","dhl_parcel")) ?> </h2>
    <table id="modalHeaderTable" class="widefat">
        <tbody>
            <tr>
                <td>
                    <strong><?php echo esc_html(__("Tracking number", "dhl_parcel"))?>: </strong> <?php echo $tracking_data['barcode'] ?>
                </td>
                <td>
                    <strong><?php echo esc_html(__("Origin", "dhl_parcel"))?>: </strong> 
                    <?php $address = $tracking_data['shipper']['address'];
                        $origin = " " .$address['street']." ". $address['houseNumber'] . ' ' . $address['postalCode'] . ' ' . $address['city'];
                        echo esc_html($origin) ?>
                </td>                   
            </tr>
            <tr>
                <td>
                    <strong><?php echo esc_html(__("Date", "dhl_parcel"))?>: : </strong> <?php $date=new DateTime($tracking_data['date']); 
                    
                    echo esc_html($date->format('H:i:s d-m-Y')); ?>
                </td>
                <td>
                    <strong><?php echo esc_html(__("Destination", "dhl_parcel"))?>:</strong>
                    <?php $address = $tracking_data['destination']['address'];
                        $destination = " " .$address['street']." ". $address['houseNumber'] . ' ' . $address['postalCode'] . ' ' . $address['city'];
                        echo $destination ?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong><?php echo esc_html(__("Estimated Delivery Time", "dhl_parcel"))?>:</strong> <?php $transitTime = $tracking_data['transitTime'] ;echo $transitTime['expectedDeliveryMoment'] ?>
                </td>
            </tr>
        </tbody>
    </table>
    <h3>History:</h3>
    <table id="modalHistory" class="widefat">
        <thead>
            <tr>
                <th>
                    <strong> <?php echo esc_html(__("Date", "dhl_parcel"))?> </strong>
                </th>
                <th>
                    <strong> <?php echo esc_html(__("Event", "dhl_parcel"))?> </strong>
                </th>
                <th>
                    <strong> <?php echo esc_html(__("Location", "dhl_parcel"))?> </strong>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php $count_rows = 1;?>
            <?php if($tracking_data['events']) :?>
                <?php foreach ( $tracking_data['events'] as $event ) : $count_rows++; ?>
                    <tr id ="tr_<?php echo($count_rules) ?>">
                        <td>
                            <?php $date=new DateTime($event['timestamp']); 
                            echo $date->format('H:i:s d-m-Y'); ?>
                        </td>
                        <td>
                            <?php echo formatStringToReadableString($event['status']); ?>
                        </td>
                        <td>
                            <?php echo esc_html($event['facility']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
  </div>
</div>

<script>
// Get the modal
var modal = document.getElementById('trackAndTraceModal');

// Get the button that opens the modal
var btn = document.getElementById("trackAndTraceLink");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>