<?php

require_once dirname(dirname(__FILE__))."/dtos/DhlDTOs.php";

class PickupRequestHelper{

    /**
     * Minumum pickup location opening time slot
     */
    const MIN_PICKUP_REQUEST_FROM = '10:00';
    /**
     * Maximum pickup location opening time slot
     */
    const MAX_PICKUP_REQUEST_TO = '18:00';
    
    /**
     * Minimum time intereval between the pickup location opening time and pickup location closing time
     */
    const MIN_INTERVAL_BETWEEN_FROM_AND_TO = 120+1;


    /**
     * Maximum time intereval between the pickup location cut off time and time slot to
     */
    const MAX_INTERVAL_BETWEEN_CUTOFF_AND_TO = 120-1;

    /**
     * Minimum time intereval between the current time and the pickup location opening time
     */
    const MIN_INTERVAL_BETWEEN_CURRENT_TIME_AND_REQUEST_FROM = 5;


    /**
     * @param DateTime $current_time, the module should send the current time in case it has a different timezone configured.
     * @param String $cut_off_time, cutoff time in the format 'hh:mm'
     */
    public static function fillPickupRequestTimeSlots(
        PickUpRequestWSServices $pickUpWs,
        PickupRequestDTO $pickupRequestDto,
        DateTime $pickup_point_opening_time, 
        DateTime $pickup_point_closing_time,
        $cut_off_time,
        DateTime $current_time = null)
    {
        if(!$current_time){
            $current_time =  new DateTime();
        }
        $current_time = $current_time->add(new DateInterval('PT'.self::MIN_INTERVAL_BETWEEN_CURRENT_TIME_AND_REQUEST_FROM.'M'));

        $time_slot = new PickupRequestTimeSlotDTO();
        $sameDayPickUpCheckRequestDto =  new PickupRequestCheckDTO();

        $margin_cutOff_TimeSlotTo = self::MAX_INTERVAL_BETWEEN_CUTOFF_AND_TO;
        $cut_off_time =  false;
        $sameDayPickUpCheckRequestDto->postalCode = $pickupRequestDto->shipper->address->postalCode;
        $sameDayPickUpCheckRequestDto->countryCode = $pickupRequestDto->shipper->address->countryCode;

        $json = json_decode($pickUpWs->checkPickupRequestAvalabilityForSameDay($sameDayPickUpCheckRequestDto))[0];
        if($json && isset($json->cutOffTime) && isset($json->margin)){
            $cut_off_time = $json->cutOffTime;
            $margin_cutOff_TimeSlotTo = (strtotime($json->margin)-strtotime('00:00'))/60;
        }
        $isNextDay = strtotime($current_time->format('H:m')) > strtotime(self::MIN_PICKUP_REQUEST_FROM)?strtotime($current_time->format('H:m'))  > strtotime($cut_off_time):strtotime(self::MIN_PICKUP_REQUEST_FROM) > strtotime($cut_off_time);
        $possible_pickup_time_from = (($min_pickup_from_date_time = new DateTime(self::MIN_PICKUP_REQUEST_FROM)) > $pickup_point_opening_time)? 
                                     $min_pickup_from_date_time : $pickup_point_opening_time ;
        if(!$isNextDay){
            $time_slot->from = $possible_pickup_time_from > $current_time ? $possible_pickup_time_from->format('H:i') : $current_time->format('H:i');
        }           
        else {
            $time_slot->from = $time_slot->from = self::MIN_PICKUP_REQUEST_FROM;
            date_modify($current_time, '+1 day');
        }

        $pickupRequestDto->pickupDate = $current_time->format('Y-m-d');
        $possible_pickup_time_to = (new DateTime($cut_off_time))->add(new DateInterval('PT'.$margin_cutOff_TimeSlotTo.'M'));

        /*$time_slot->to = ((($max_pickup_to_date_time = new DateTime(self::MAX_PICKUP_REQUEST_TO)) < $pickup_point_closing_time)? 
                         $max_pickup_to_date_time : $pickup_point_closing_time)->format('H:i') ;
        */
        $time_slot->to = $possible_pickup_time_to->format('H:i');

        $pickupRequestDto->timeSlot = $time_slot;
        return $pickupRequestDto;
    }

}