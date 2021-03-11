# DHL Parcel Iberia Woocommerce Plugin

Original plugin by [Skrey Software](www.skrey-software.com) version 1.0.19.

## Changelog
### 11-03-2021

Fixes:

* Fixed the result handled when `/track-trace` DHL API request is not authorized

Refactor:

* Reused tracking view code for frontend and admin

Features:

* Added `dhl_get_order_dimensions` filter

### 05-03-2021

Refactor:

* Renamed utils methods names (avoiding conflicts with other external methods)
* Replaced `jQuery.live` by `jQuery.on` (compatibility with jQuery 1.9 and higher)

Features:

* Added `dhl_order_tracking_code` and `dhl_admin_order_tracking_code` filters
