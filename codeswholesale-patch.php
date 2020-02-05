<?php
/*
Plugin Name: Bojett Codeswholesale Patch
Description: Currently the codeswholesale importer for WooCommerce is not working. This patch provides a new importer to import all products from CWS.
Text Domain: codeswholesale_patch
Depends: WooCommerce
Version: 1.0.0
Author: Mario Freuler, Bojett.com
License: GPL2
*/

ob_start();

function add_quick_style()
{
    return '<style>.toplevel_page_cws-bojett-patch img { margin-top:-4px}</style>';
}
add_action('init', 'add_quick_style');

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );
global $wpdb;

//TODO: Add select element for every product page with the cws_product_ids
//TODO: Check if product exist with this cws product id
//TODO: Check code for correct values from old script
/*
 * Create the required tables for the plugin by activation of the plugin.
 */
function create_plugin_database_tables()
{
    global $table_prefix, $wpdb;

    $credentials = 'bojett_credentials';
    $bojett_credentials_table = $table_prefix . "$credentials";
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    if($wpdb->get_var( "show tables like '$bojett_credentials_table'" ) != $bojett_credentials_table)
    {
        $sql = "CREATE TABLE `". $bojett_credentials_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `cws_client_id`  varchar(128)  DEFAULT NULL, ";
        $sql .= "  `cws_client_secret`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `batch_size`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `importnumber`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `last_updated`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta($sql);
    }

    $token = 'bojett_auth_token';
    $bojett_token_table = $table_prefix . "$token";
    if($wpdb->get_var( "show tables like '$bojett_token_table'" ) != $bojett_token_table)
    {
        $sql2 = "CREATE TABLE `". $bojett_token_table . "` ( ";
        $sql2 .= "  `id`  int(11) NOT NULL auto_increment, ";
        $sql2 .= "  `cws_expires_in`  varchar(128)  DEFAULT NULL, ";
        $sql2 .= "  `cws_access_token`  varchar(128) DEFAULT NULL, ";
        $sql2 .= "  PRIMARY KEY (`id`) ";
        $sql2 .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta($sql2);
    }

    $import = 'bojett_import';
    $bojett_import_table = $table_prefix . "$import";
    if($wpdb->get_var( "show tables like '$bojett_import_table'" ) != $bojett_import_table)
    {
        $sql3 = "CREATE TABLE `". $bojett_import_table . "` ( ";
        $sql3 .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql3 .= "  `name`  varchar(128)   NOT NULL, ";
        $sql3 .= "  `cws_id`  varchar(128)   NOT NULL, ";
        $sql3 .= "  `created_at`  varchar(128)   NOT NULL, ";
        $sql3 .= "  PRIMARY KEY (`id`) ";
        $sql3 .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta($sql3);
    }
}
register_activation_hook( __FILE__, 'create_plugin_database_tables' );


/*
 * Check if the original codeswholesale plugin installed and activated.
 */

/*
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
        add_action( 'admin_notices', 'my_error_notice' );
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
        'bojett_settings'
    );

    /*function run_my_script() {
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


