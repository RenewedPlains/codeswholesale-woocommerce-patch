<?php
/*
Plugin Name: Bojett Codeswholesale Patch
Description: Authentication for REST API
Text Domain: codeswholesale_patch
Version: 1.0.0
Author: Mario Freuler
License: GPL2
*/
ob_start();
set_time_limit(0);
ignore_user_abort(true);
ini_set('max_execution_time', 0);
global $wpdb;
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );

add_action( 'plugins_loaded', 'check_codeswholesale_plugin', 100 );
function check_codeswholesale_plugin()
{
    if (is_plugin_active( ABSPATH . 'wp-content/plugins/codeswholesale/codeswholesale.php') ) {

    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        unset($_GET['activate']);
        function my_error_notice() {
            ?>
            <div class="error notice">
                <p><?php _e( 'Plugin could not be activated. Please download the official plugin from <a href="/wp-admin/plugin-install.php?s=codeswholesale&tab=search&type=term">Codeswholesale</a> and configure it. ', 'my_plugin_textdomain' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'my_error_notice' );
    }
}


$fp=fopen("../../../tmp/php-error.log", "w");
fclose($fp);

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
// Get the bearer authorizationcode from your database and recheck the expiringtime
$table_name = $wpdb->prefix . "codeswholesale_access_tokens";
$access_bearer = $wpdb->get_results( "SELECT expires_in, access_token FROM $table_name" );
$db_token = $access_bearer[0]->access_token;
$db_expires_in = $access_bearer[0]->expires_in;
$current_timestamp = time();

if($db_expires_in > $current_timestamp) {
    // Do nothing, the bearer is already up to date.
} else {
    $options_name = $wpdb->prefix . "options";
    $cws_credentials = $wpdb->get_results( "SELECT option_value FROM $options_name WHERE option_name = 'cw_options'" );
    $client_id = get_string_between($cws_credentials[0]->option_value, 'api_client_id";s:32:"', '"');
    $client_secret = get_string_between($cws_credentials[0]->option_value, 's:17:"api_client_secret";s:60:"', '"');
    $client_signature = get_string_between($cws_credentials[0]->option_value, 's:20:"api_client_singature";s:36:"', '"');

    $ch = curl_init('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret); // Initialise cURL
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' )); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    $new_bearer = json_decode($result, true)['access_token'];
    $new_bearer_expires = json_decode($result, true)['expires_in'];
    $new_db_expires_in = $current_timestamp + $new_bearer_expires;
    $table_name = $wpdb->prefix . "codeswholesale_access_tokens";

    $wpdb->update($table_name, array('expires_in' => $new_db_expires_in, 'access_token' => $new_bearer), array('scope' => 'administration'), array('%s', '%s'), array('%s'));
    curl_close($ch); // Close the cURL connection
}

function add_cws_patch_sub() {
   // add_submenu_page( 'codeswholesale', 'Bojett Patch', 'Bojett Patch', 'manage_options', 'bojett-patch', array('render_custom_link_page'));
}
add_action('admin_menu', 'add_cws_patch_sub');





function render_custom_link_page($result) {
    echo '
<div class="clickme" style="margin-left:  400px; margin-top: 300px;">Klick Me</div>
<script>
jQuery(function() {
    jQuery(".clickme").on("click", function() {
        jQuery.ajax({
  url: "/wp-content/plugins/codeswholesale-patch/importaction.php",
}).done(function( data ) {
      jQuery(".kernel").append(data);

      console.log(data);
      alert("alles io");
  });
    }); 
});
</script>';
}


