<?php

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-config.php');

global $wpdb;

// Get the bearer authorizationcode from your database and recheck the expiringtime
$table_name = $wpdb->prefix . "bojett_auth_token";
$options_name = $wpdb->prefix . "bojett_credentials";
$access_bearer = $wpdb->get_var("SELECT cws_access_token FROM $table_name");
$access_expires_in = $wpdb->get_var("SELECT cws_expires_in FROM $table_name");
$client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
$client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);
$db_token = $access_bearer;
$db_expires_in = $access_expires_in;
$current_timestamp = time();

if ($db_expires_in > $current_timestamp && $db_expires_in !== NULL && $access_bearer !== NULL) {
    if ($client_id == NULL || $client_secret == NULL) {
        // Delete current bearer because no clientkeys are set
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $wpdb->query("TRUNCATE TABLE $table_name");
    }
    // Do nothing, the bearer is already up to date.
} else {
    $options_name = $wpdb->prefix . "bojett_credentials";
    $client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
    $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);

    $ch = curl_init('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret); // Initialise cURL
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    curl_close($ch); // Close the cURL connection*/
    $new_bearer = json_decode($result, true)['access_token'];
    $new_bearer_expires = json_decode($result, true)['expires_in'];
    $new_db_expires_in = $current_timestamp + $new_bearer_expires;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $wpdb->query("TRUNCATE TABLE $table_name");
    $wpdb->insert($table_name, array(
        'cws_expires_in' => $new_db_expires_in,
        'cws_access_token' => $new_bearer
    ));
}
