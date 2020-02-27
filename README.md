# CodesWholesale WooCommerce Patch
Current Version: `0.9.1`-dev

This repository requires a Wordpress Woocommerce instance with preconfigured Codeswholesale for WooCommerce plugin. The Codeswholesale patch is installed as an additional plugin, which retrieves the access data from the Codeswholesale for WooCommerce Plugin API and starts a new import process via the V2 API of Codeswholesale.

## Warning: It's beta
For testing purposes only. This plugin is not yet fully developed and may contain unexpected warnings or even errors. Please wait with the usage of the plugin until the version is greater or equal 1.

## What is it?
This Wordpress plugin was developed because the official [Codeswholesale for WooCommerce](https://wordpress.org/plugins/codeswholesale-for-woocommerce/ "CodesWholesale for WooCommerce") cannot import products 
and, for example, produces weird messages like "AWAITING" or "already reported". 
              
With this patch it is possible to import all products via cron (WPCron or external cron). The dispatch of game codes,
and the check for sufficient credit at CWS is still done by the official plugin. The patch
will be developed further until a version is created which completely replaces the official plugin.

### Requirements
- Running Webserver with PHP > v7.0 and cURL
- Completely set up Wordpress instance
- Configured and connected CodesWholesale plugin

### Problems with the plugin? Bug or enhancement?
Please consider reporting bugs and possible improvements via Issue on GitHub or on the official CodesWholesale support Discord Server.

### Changelog
#### Version 0.9.1
  * cURL fix, when SSL it's not available
  * bug fixes
  * product price import and update; added percentage profit margin

#### Version 0.9.0
  * Placeholders inserted in setting fields for easier decision
  * Currency converter in 32 different currencies added
    * Is regenerated via cronjob every hour
  * Simplified product update procedure added
    * As soon as the Postback URL is triggered by CWS, the data of the product to be updated is read and updated

#### Version 0.8.5
  * Setting Placeholder image for products without specified product image possible in the settings
  * Removed unnecessary code 
  * Importer update for lesser download from CWS API
  * Hotfix for the importer, could not change files [#02f248b](https://github.com/RenewedPlains/codeswholesale-woocommerce-patch/commit/c6e3cee434dd57b8dd9309ae352c368a3342d55a)
  
#### Version 0.8.4
  * Check before import can be started if there is a connection to CodesWholesale with redirect
  * Bug fix for missing translationstring
  * Added german translation for plugin
  * Fix for multiple uploader via cron
 

## ToDo's
* 27.02.2020 - Product prices: It is strongly recommended to overwrite 
    the correct Profit Margin Value of CodesWholesale Plugin as 
    Quickfix, so that no inequalities occur during updates, e.g. 
    if the official plugin has a different Profit Margin Value than Postback.
    * 27.02.2020 - Add HTML GUI for importers
    * 27.02.2020 - Add Selectfield _codeswholesale_productid on Prouctpage with all products (for sa)
    * 27.02.2020 - Create Codetresor page for user
    * 27.02.2020 - Add complete order process with CWS