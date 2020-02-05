<?php
/*
Plugin Name: Bojett Codeswholesale Patch
Description: Currently the codeswholesale importer for WooCommerce is not working. This patch provides a new importer to import all products from CWS.
Text Domain: codeswholesale_patch
Depends: WooCommerce
Version: 1.0.0
Author: Mario Freuler
License: GPL2
*/

ob_start();

global $wpdb;
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );

set_time_limit(0);
ignore_user_abort(true);
ini_set('max_execution_time', 0);

/*
 * Check if the original codeswholesale plugin installed and activated.
 */
add_action( 'plugins_loaded', 'check_codeswholesale_plugin', 100 );
function check_codeswholesale_plugin()
{
    if (is_plugin_active( 'codeswholesale-for-woocommerce/codeswholesale.php' ) ) {
        // TODO: if the codeswholesale plugin not configured, notice the administrator.
        /*add_action('init', 'conf_remember');
        function conf_remember() {
            ?>
            <div class="error notice">
                <p><?php _e( 'Plugin ist activated but not configured yet. Please configure the original plugin - we can start after that with the import! ', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'my_error_notice' );*/
    } else {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        unset($_GET['activate']);
        function my_error_notice() {
            ?>
            <div class="error notice">
                <?php var_dump(plugin_dir_path( 'codeswholesale-for-woocommerce' . DIRECTORY_SEPARATOR . 'codeswholesale.php' )); ?>
                <p><?php _e( 'Plugin could not be activated. Please download the official plugin from <a href="/wp-admin/plugin-install.php?s=codeswholesale&tab=search&type=term">Codeswholesale</a> and configure it. ', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'my_error_notice' );
    }
}
*/

/*
 * Add the plugin to the admin menu with a own page.
 */
function add_admin_menu_patch()
{
    add_menu_page(
        __('Bojett.com', 'codeswholesale-patch'),
        __('Bojett.com', 'codeswholesale-patch'),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page',
        '/wp-content/plugins/codeswholesale-patch/' . plugin_basename( 'img/bojett_icon_24x24.png' ),
        3
    );
    add_submenu_page(
        'cws-bojett-patch',
        __('Importer', 'codeswholesale-patch'),
        __('Importer', 'codeswholesale-patch'),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page'
    );
    add_submenu_page(
        'cws-bojett-patch',
        __('Settings', 'codeswholesale-patch'),
        __('Settings', 'codeswholesale-patch'),
        'manage_options',
        'cws-bojett-settings',
        'cws-bojett-settings'
    );

    function run_my_script() {
        echo 'hehehehe';
    }
    function USERS_MONITORING() {
        if ( ! wp_next_scheduled( 'USERS_MONITORING' ) ) {
            wp_schedule_event( time(), 'every_minute', 'USERS_MONITORING' );
        }
    }
    add_action( 'USERS_MONITORING', 'run_my_script' );*/
    //USERS_MONITORING();

}
add_action('admin_menu', 'add_admin_menu_patch');

if($_POST['set_settings']) {
    function my_error_notice1() {
        ?>
        <div class="success notice notice-success">
            <p><?php _e( 'Deine Einstellungen wurden erfolgreich geändert.', 'codeswholesale_patch' ); ?></p>
        </div>
        <?php
    }
    add_action( 'admin_notices', 'my_error_notice1' );
}

/*
 * Define and run Cronjob for refreshing the bearer in time
 */
function run_cws_cron_script() {
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $access_bearer = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
    $db_expires_in = $access_expires_in;
    $current_timestamp = time();
    if($db_expires_in > $current_timestamp && $db_expires_in !== NULL && $access_bearer !== NULL) {
        // Do nothing, the bearer is already up to date.
    } else {
        $options_name = $wpdb->prefix . "bojett_credentials";
        $client_id = $wpdb->get_var('SELECT cws_client_id FROM '. $options_name);
        $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM '. $options_name);

        $ch = curl_init('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret); // Initialise cURL
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' )); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        curl_close($ch); // Close the cURL connection*/
        $new_bearer = json_decode($result, true)['access_token'];
        $new_bearer_expires = json_decode($result, true)['expires_in'];
        $new_db_expires_in = $current_timestamp + $new_bearer_expires;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        if($current_access_bearer != NULL) {
            $wpdb->query("TRUNCATE TABLE $table_name");
            $wpdb->insert($table_name, array(
                'cws_expires_in' => $new_db_expires_in,
                'cws_access_token' => $new_bearer
            ));
        } else {
            $wpdb->insert($table_name, array(
                'cws_expires_in' => $new_db_expires_in,
                'cws_access_token' => $new_bearer
            ));
        }
    }
}
function USERS_MONITORING() {
    global $wpdb;
    if ( ! wp_next_scheduled( 'USERS_MONITORING' ) ) {
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        wp_schedule_single_event( $access_expires_in, 'USERS_MONITORING' );
    }
}
add_action( 'USERS_MONITORING', 'run_cws_cron_script' );


USERS_MONITORING();
function bojett_settings() {
    global $table_prefix, $wpdb;
    if($_POST['set_settings']) {
        require_once('includes/bearer-refresh.php');
        wp_mail("renewedplains@gmail.com", "Notification TEST", "TEST", null);
        $cws_client_id = $_POST['cws_client_id'];
        $cws_secret_id = $_POST['cws_secret_id'];
        $get_credentials_check = $wpdb->get_var('SELECT cws_client_id, cws_client_secret FROM '.$table_prefix.'bojett_credentials');
        if($get_credentials_check === NULL) {
            $wpdb->insert($table_prefix.'bojett_credentials', array(
                'cws_client_id' => $cws_client_id,
                'cws_client_secret' => $cws_secret_id,
                'batch_size' => '20',
            ));
        } else {
            $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$table_prefix.'bojett_credentials');
            $wpdb->update(
                $table_prefix.'bojett_credentials',
                array(
                    'cws_client_id' => $cws_client_id,
                    'cws_client_secret' => $cws_secret_id
                ),
                array( 'id' => $get_credentials_id ),
                array(
                    '%s',
                    '%s'
                ),
                array( '%s' )
            );
        }
    }

    $get_client_id = $wpdb->get_var('SELECT cws_client_id FROM '.$table_prefix.'bojett_credentials');
    $get_client_secret = $wpdb->get_var('SELECT cws_client_secret FROM '.$table_prefix.'bojett_credentials');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php _e('Settings', 'codeswholesale_patch'); ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=cws-bojett-settings" method="post">
                <table class="form-table" role="presentation">

                    <tbody><tr>
                        <th scope="row"><label for="cws_client_id"><?php _e('Your CWS API Client ID', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="cws_client_id" type="text" id="cws_client_id" value="<?php echo $get_client_id; ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="cws_secret_id"><?php _e('Your CWS API Secret ID', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="cws_secret_id" type="text" id="cws_secret_id" aria-describedby="tagline-description" value="<?php echo $get_client_secret; ?>" class="regular-text">
                            <p class="description" id="tagline-description">In a few words, explain what this site is about.</p></td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="set_settings" id="submit" class="button button-primary" value="<?php _e("Save Changes", 'codeswholesale_patch'); ?>"></p>
            </form>
        </h1>
    </div>
    <?php
}


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






function render_custom_link_page() {
    echo '
<div class="clickme" style="margin-left:  400px; margin-top: 300px;">Klick Me</div>
<script>
jQuery(function() {
    jQuery(".clickme").on("click", function() {
        jQuery.ajax({
  url: "/wp-content/plugins/codeswholesale-patch/importaction.php",
}).done(function( data ) {
      alert("alles io");
  });
    }); 
});
</script>';
}


