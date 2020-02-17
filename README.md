# CodesWholesale WooCommerce Patch
Current Version: `0.8.4`-dev

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
**- Running Webserver with PHP > v7.0 and cURL**

**- Completely set up Wordpress instance**

**- Configured and connected CodesWholesale plugin**

### Problems with the plugin? Bug or enhancement?
Please consider reporting bugs and possible improvements via Issue on GitHub or on the official CodesWholesale support Discord Server.

### Changelog
#### Version 0.8.4
  * Check before import can be started if there is a connection to CodesWholesale with redirect
  * Bug fix for missing translationstring
  * Added german translation for plugin
