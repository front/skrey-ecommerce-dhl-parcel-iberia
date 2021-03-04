<?php 


abstract class CapabilitiesList {
 
    const MAX_CACHE_LIFETIME_HOURS = 24;

    public abstract function getCapability($requestHash);

    public abstract function saveCapability(CapabilityLine $capability, $flushCache = false, CapabilityLine $capability_cached = null);
}