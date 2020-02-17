<?php
/*
Plugin Name: Bojett Codeswholesale Patch
Description: Currently the codeswholesale importer for WooCommerce is not working. This patch provides a new importer to import all products from CWS.
Text Domain: codeswholesale_patch
Depends: WooCommerce
Version: 0.8.4
Author: Mario Freuler, Bojett.com
License: GPL2
*/
ob_start();

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );
global $wpdb;

function load_bojett_translations() {
    load_plugin_textdomain( 'codeswholesale_patch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action('init', 'load_bojett_translations');

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
        $sql .= "  `phpworker`  varchar(128)  NOT NULL DEFAULT '5', ";
        $sql .= "  `importnumber`  varchar(128)  NOT NULL DEFAULT '20', ";
        $sql .= "  `description_language`  varchar(128)  NOT NULL DEFAULT 'English', ";
        $sql .= "  `profit_margin_value`  varchar(128)  NOT NULL DEFAULT '10', ";
        $sql .= "  `productarray_id`  varchar(128)   DEFAULT NULL, ";
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

    $worker = 'bojett_import_worker';
    $bojett_worker_table = $table_prefix . "$worker";
    if($wpdb->get_var( "show tables like '$bojett_worker_table'" ) != $bojett_worker_table)
    {
        $sql4 = "CREATE TABLE `". $bojett_worker_table . "` ( ";
        $sql4 .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql4 .= "  `name`  varchar(128)   NOT NULL, ";
        $sql4 .= "  `from`  varchar(128)   NOT NULL, ";
        $sql4 .= "  `to`  varchar(128)   NOT NULL, ";
        $sql4 .= "  `last_product`  varchar(128)   NOT NULL, ";
        $sql4 .= "  `last_update`  varchar(128)   NOT NULL, ";
        $sql4 .= "  PRIMARY KEY (`id`) ";
        $sql4 .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta($sql4);
    }
}
register_activation_hook( __FILE__, 'create_plugin_database_tables' );


function delete_bojett_tables() {
    global $wpdb;
    $sql = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_auth_token';
    $wpdb->query($sql);

    $sql2 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_credentials';
    $wpdb->query($sql2);

    $sql3 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import';
    $wpdb->query($sql3);

    $sql4 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import_worker';
    $wpdb->query($sql4);
}
register_deactivation_hook( __FILE__, 'delete_bojett_tables' );

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
                <p><?php _e( 'Plugin is activated but not configured yet. Please configure the original plugin - we can start after that with the import! ', 'codeswholesale_patch' ); ?></p>
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


/*
 * Add the plugin to the admin menu with a own page.
 */
function add_admin_menu_patch()
{
    add_menu_page(
        __('Bojett.com', 'codeswholesale_patch'),
        __('Bojett.com', 'codeswholesale_patch'),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page',
         plugins_url( 'img/bojett_icon_128x128.png', __FILE__ ),
        3
    );
    add_submenu_page(
        'cws-bojett-patch',
        __('Importer', 'codeswholesale_patch'),
        __('Importer', 'codeswholesale_patch'),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page'
    );
    add_submenu_page(
        'cws-bojett-patch',
        __('Settings', 'codeswholesale_patch'),
        __('Settings', 'codeswholesale_patch'),
        'manage_options',
        'cws-bojett-settings',
        'bojett_settings'
    );
}
add_action('admin_menu', 'add_admin_menu_patch');

add_filter('admin_menu', 'change_icon_style_start',1);
function change_icon_style_start($template) {
    ob_start('change_icon_style_end');
    return $template;
}
function change_icon_style_end($buffer) {
    return str_replace('img/bojett_icon_128x128.png"','img/bojett_icon_128x128.png" style="max-width: 24px;margin-top:-3px;"', $buffer);
}

/*
 * Define and run Cronjob for refreshing the bearer in time
 */
function run_cws_cron_script() {
    global $wpdb;
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

        $chc = curl_init('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret); // Initialise cURL
        curl_setopt($chc, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); // Inject the token into the header
        curl_setopt($chc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chc, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chc, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($chc); // Execute the cURL statement
        curl_close($chc); // Close the cURL connection*/
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
}
function check_update_bearer_token() {
    global $wpdb;
    if ( ! wp_next_scheduled( 'check_update_bearer_token' ) ) {
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        wp_schedule_single_event( $access_expires_in, 'check_update_bearer_token' );
    }
}
add_action( 'check_update_bearer_token', 'run_cws_cron_script' );
check_update_bearer_token();




//import_batch();

function bojett_settings() {
    global $table_prefix, $wpdb;
    if($_GET['notvalid'] == 'true') {
        function bojett_settings_notfound() {
            ?>
            <div class="error notice">
                <p><?php _e( 'First connect to Codeswholesale so that you can start importing products.', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'bojett_settings_notfound' );
        do_action( 'admin_notices' );
    }
    if($_POST['set_settings']) {
        $cws_client_id = $_POST['cws_client_id'];
        $cws_secret_id = $_POST['cws_secret_id'];
        if($_POST['import_worker'] != '') {
            $import_worker = $_POST['import_worker'];
        } else {
            $import_worker = '1';
        }
        if($_POST['import_batch_size'] != '') {
            $import_batch_size = $_POST['import_batch_size'];
        } else {
            $import_batch_size = '20';
        }
        if($_POST['profit_margin_value'] != '') {
            $profit_margin_value = $_POST['profit_margin_value'];
        } else {
            $profit_margin_value = '10';
        }
        $description_language = $_POST['description_language'];
        $get_credentials_check = $wpdb->get_var('SELECT cws_client_id, cws_client_secret FROM '.$table_prefix.'bojett_credentials');
        if($get_credentials_check === NULL) {
            $wpdb->insert($table_prefix.'bojett_credentials', array(
                'cws_client_id' => $cws_client_id,
                'cws_client_secret' => $cws_secret_id,
                'batch_size' => $import_batch_size,
                'phpworker' => $import_worker,
                'description_language' => $description_language,
                'profit_margin_value' => $profit_margin_value,
            ));
        } else {
            $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$table_prefix.'bojett_credentials');
            $wpdb->update(
                $table_prefix.'bojett_credentials',
                array(
                    'cws_client_id' => $cws_client_id,
                    'cws_client_secret' => $cws_secret_id,
                    'batch_size' => $import_batch_size,
                    'phpworker' => $import_worker,
                    'description_language' => $description_language,
                    'profit_margin_value' => $profit_margin_value,
                ),
                array( 'id' => $get_credentials_id ),
                array(
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s'
                ),
                array( '%s' )
            );
        }
        require_once('includes/bearer-refresh.php');
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        if($current_access_bearer != NULL && $current_access_bearer_expire != NULL) {
            function bojett_settings_saved() {
                ?>
                <div class="success notice notice-success">
                    <p><?php _e( 'Your settings have been successfully saved.', 'codeswholesale_patch' ); ?></p>
                </div>
                <?php
            }
            add_action( 'admin_notices', 'bojett_settings_saved' );
            do_action( 'admin_notices' );
        } else {
            function bojett_settings_saved1() {
                ?>
                <div class="error notice">
                    <p><?php _e( 'Connection to CodesWholesale failed. Check your input.', 'codeswholesale_patch' ); ?></p>
                </div>
                <?php
            }
            add_action( 'admin_notices', 'bojett_settings_saved1' );
            do_action( 'admin_notices' );
        }
    }

    $get_client_id = $wpdb->get_var('SELECT cws_client_id FROM '.$table_prefix.'bojett_credentials');
    $get_client_secret = $wpdb->get_var('SELECT cws_client_secret FROM '.$table_prefix.'bojett_credentials');
    $get_batch_size = $wpdb->get_var('SELECT batch_size FROM '.$table_prefix.'bojett_credentials');
    $get_php_worker = $wpdb->get_var('SELECT phpworker FROM '.$table_prefix.'bojett_credentials');
    $get_description_language = $wpdb->get_var('SELECT description_language FROM '.$table_prefix.'bojett_credentials');
    $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$table_prefix.'bojett_credentials');
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php _e('Settings', 'codeswholesale_patch'); ?></h1>
        <h2 class="title"><?php _e('Get your API Keys', 'codeswholesale_patch'); ?></h2>
        <p><?php _e('To enable us to import all products, you need an available account at <a href="https://codeswholesale.com" target="_blank">CodesWholesale.com</a>.
                          In the backend of CWS you can look up and copy your API keys. This will generate a new authentication token so that your server can guarantee an outgoing connection and import all available product.', 'codeswholesale_patch'); ?></p>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=cws-bojett-settings" method="post">
                <table class="form-table" role="presentation">
                    <tbody><tr>
                        <th scope="row"><label for="cws_client_id"><?php _e('Your CWS API Client ID', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="cws_client_id" type="text" id="cws_client_id" value="<?php echo $get_client_id; ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cws_secret_id"><?php _e('Your CWS API Secret ID', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="cws_secret_id" type="text" id="cws_secret_id" aria-describedby="tagline-description" value="<?php echo $get_client_secret; ?>" class="regular-text">
                    </tr>
                    </tbody>
                </table><br />
                <h2 class="title"><?php _e('Import settings', 'codeswholesale_patch'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="import_worker"><?php _e('Import worker', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="import_worker" type="number" id="import_worker" aria-describedby="tagline-description" value="<?php echo $get_php_worker; ?>" class="regular-text">
                            <p class="description" id="tagline-description"><?php _e('This plugin works with cronjobs. Select the number of cronjobs that will be executed by the PHP server.', 'codeswholesale_patch'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="import_batch_size"><?php _e('Import batch size', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="import_batch_size" type="number" id="import_batch_size" aria-describedby="tagline-description" value="<?php echo $get_batch_size; ?>" class="regular-text">
                            <p class="description" id="tagline-description"><?php _e('Number of games to be imported by one running cronjob.', 'codeswholesale_patch'); ?></p></td>
                    </tr>
                    </tbody>
                </table>
                <h2 class="title"><?php _e('Product settings', 'codeswholesale_patch'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><label for="description_language"><?php _e('Description language', 'codeswholesale_patch'); ?></label></th>
                        <td><select name="description_language">
                                <?php //TODO: Add available languages from CWS ?>
                                <option value="English"<?php if($get_description_language == 'English') { echo ' selected'; } ?>><?php _e('English', 'codeswholesale_patch'); ?></option>
                                <option value="German"<?php if($get_description_language == 'German') { echo ' selected'; } ?>><?php _e('German', 'codeswholesale_patch'); ?></option>
                                <option value="Italian"<?php if($get_description_language == 'Italian') { echo ' selected'; } ?>><?php _e('Italian', 'codeswholesale_patch'); ?></option>
                            </select>
                            <p class="description" id="tagline-description"><?php _e('Select the language for the description imported from CodesWholesale.', 'codeswholesale_patch'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="profit_margin_value"><?php _e('Profit margin value', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="profit_margin_value" type="number" id="profit_margin_value" aria-describedby="tagline-description" value="<?php echo $profit_margin_value; ?>" class="regular-text">
                            <p class="description" id="tagline-description"><?php _e('The product is imported in EUR. If your shop has set a different currency as the main currency, this has to be considered manually.', 'codeswholesale_patch'); ?></p></td>
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
}/*
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
}*/
function isa_add_cron_recurrence_interval( $schedules ) {

    $schedules['bojett_cws_import'] = array(
        'interval'  => 30,
        'display'   => __( 'Every 30 seconds', 'codeswholesale_patch' )
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'isa_add_cron_recurrence_interval' );
require_once ( plugin_dir_path( __FILE__ ) . "importaction.php");

function bojett_import_struggle() {
    ?>
    <div class="error notice">
        <p><?php _e( '<b>Uhoh!</b> It seems the importer got stuck. <a href="admin.php?page=cws-bojett-patch&forcekill=true">Click here</a> to force the import to stop. <a href="https://github.com/RenewedPlains/codeswholesale-woocommerce-patch" target="_blank">Please inform me</a> about the problem if it persists.', 'codeswholesale_patch' ); ?></p>
    </div>
    <?php
}
function bojett_import_killed() {
    ?>
    <div class="success notice-success notice">
        <p><?php _e( 'The importer was interrupted and removed. Please try the import again again. <a href="https://github.com/RenewedPlains/codeswholesale-woocommerce-patch" target="_blank">Please inform me</a> about the problem if it persists.', 'codeswholesale_patch' ); ?></p>
    </div>
    <?php
}

function validate_importer()
{
    global $wpdb;
    $import_worker_last_update = $wpdb->get_results('SELECT `last_update` FROM '.$wpdb->prefix.'bojett_import_worker');
    foreach($import_worker_last_update as $last_time) {
        if($last_time->last_update + 360 < time()) {

            if($_GET['forcekill'] == 'true') {
                $table_name = $wpdb->prefix . "bojett_import_worker";
                $wpdb->query("TRUNCATE TABLE $table_name");
                add_action( 'admin_notices', 'bojett_import_killed' );
            } else {
                add_action( 'admin_notices', 'bojett_import_struggle' );
            }
        }
    }
}
validate_importer();

if($_GET['importstart'] == 'true' && $_POST['importstart']) {
    $table_name = $wpdb->prefix . "bojett_import_worker";
    $wpdb->query("TRUNCATE TABLE $table_name");
}

$get_php_worker = $wpdb->get_var('SELECT phpworker FROM '.$wpdb->prefix.'bojett_credentials');
if($get_php_worker == '1') {
    function import_batch() {
        global $wpdb;
        $table_title = $wpdb->prefix . 'bojett_import_worker';
        $get_batch_size = $wpdb->get_var('SELECT batch_size FROM '.$wpdb->prefix.'bojett_credentials');
        $wpdb->insert($table_title, array(
            'name' => "import_batch",
            'from' => "0",
            'to' => $get_batch_size,
            'last_product' => "0",
            'last_update' => time(),
        ));
        $get_import_from = $wpdb->get_var('SELECT `from` FROM '.$wpdb->prefix.'bojett_import_worker');
        $get_import_to = $wpdb->get_var('SELECT `to` FROM '.$wpdb->prefix.'bojett_import_worker');
        $import_variable = 'import_batch';
        if ( ! wp_next_scheduled( 'import_batch' ) ) {
            $timestamp = time();
            $from = $get_import_from;
            $to = $get_import_to;
            $args = array($from, $to, $import_variable);
            wp_clear_scheduled_hook( $import_variable, $args );
            wp_schedule_single_event( $timestamp + 30, 'import_batch', $args );
        }
    }
    add_action( 'import_batch', 'import_cws_product', 1, 3 );
} else {
    for ($i = 0; $i <= $get_php_worker - 1; $i++) {
        $iteratorfun = "import_batch_" . $i;
        $$iteratorfun = function() {
            global $iteratorfun;
            global $i;
            if ( ! wp_next_scheduled( 'import_batch_' . $i ) ) {
                global $wpdb;
                $get_batch_size = $wpdb->get_var('SELECT batch_size FROM ' . $wpdb->prefix . 'bojett_credentials');
                $import_from = $i * $get_batch_size;
                $import_to = $i * $get_batch_size + $get_batch_size;
                $iteratorfun = "import_batch_" . $i;
                $table_title = $wpdb->prefix . 'bojett_import_worker';
                $import_variable = $iteratorfun;
                $wpdb->insert($table_title, array(
                    'name' => "$iteratorfun",
                    'from' => $import_from,
                    'to' => $import_to,
                    'last_product' => "0",
                    'last_update' => time(),
                ));

                $get_import_from = $wpdb->get_var('SELECT `from` FROM '.$wpdb->prefix.'bojett_import_worker WHERE `name` = "' . $iteratorfun .'"');
                $get_import_to = $wpdb->get_var('SELECT `to` FROM '.$wpdb->prefix.'bojett_import_worker WHERE `name` = "' . $iteratorfun .'"');
                $timestamp = time();
                $from = $get_import_from;
                $to = $get_import_to;
                $args = array($from, $to, $import_variable);
                wp_clear_scheduled_hook( $import_variable, $args );
                wp_schedule_single_event($timestamp + 30, 'import_batch_' . $i, $args);
            }
        };
        add_action( 'import_batch_' . $i, 'import_cws_product', 1, 3 );
    }
}

if($_GET['importstart'] == 'true' && $_POST['importstart']) {

    //do_action('import_batch');
    $get_php_worker = $wpdb->get_var('SELECT phpworker FROM '.$wpdb->prefix.'bojett_credentials');
    if($get_php_worker == '1') {
        import_batch();
    } else {
        for ($i = 0; $i <= $get_php_worker - 1; $i++) {
            $$iteratorfun();
        }
    }
} elseif($_GET['importabort'] == 'true') {
    $get_cred_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
    $wpdb->update(
        $wpdb->prefix.'bojett_credentials',
        array(
            'last_updated' => 'ABORTED'
        ),
        array( 'id' => $get_cred_id ),
        array(
            '%s'
        ),
        array( '%d' )
    );
}

function render_custom_link_page() {
    global $wpdb;
    $get_acct = $wpdb->get_var('SELECT cws_access_token FROM '.$wpdb->prefix.'bojett_auth_token');
    $get_exp = $wpdb->get_var('SELECT cws_expires_in FROM '.$wpdb->prefix.'bojett_auth_token');

    if($get_acct == '' || $get_exp < time()) {
        header("Location: admin.php?page=cws-bojett-settings&notvalid=true");
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $get_importstate = $wpdb->get_var('SELECT last_updated FROM '.$wpdb->prefix.'bojett_credentials');
    $active_workers = $wpdb->get_results("SELECT * from "  .$wpdb->prefix . "bojett_import_worker WHERE id != ''");
    if($get_importstate == 'ABORTED' && count($active_workers) == 0) {
        $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
        $wpdb->update(
            $wpdb->prefix.'bojett_credentials',
            array(
                'last_updated' => '',
            ),
            array( 'id' => $get_credentials_id ),
            array(
                '%s',
            ),
            array( '%d' )
        );
    }
    if($_GET['importstart'] == 'true' && $_POST['importstart']) {
        function bojett_import_started() {
            ?>
            <div class="success notice notice-success">
                <p><?php _e( 'Import started successfully. The import will also continue when you leave the page. Come back here to see the status of the import.', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'bojett_import_started' );
        do_action( 'admin_notices' );
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $productcounter = count(json_decode(inital_puller($db_token, ""), true)['items']);
        $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
        $wpdb->update(
            $wpdb->prefix.'bojett_credentials',
            array(
                'importnumber' => $productcounter,
                'productarray_id' => '0'
            ),
            array( 'id' => $get_credentials_id ),
            array(
                '%d',
                '%d'
            ),
            array( '%d' )
        );
        unset($_POST['importstart']);
    }
    echo '<div class="wrap">
    <h1 class="wp-heading-inline">' . __("Product Import", "codeswholesale_patch") . '</h1>';
    $result_check = $wpdb->get_results("SELECT * from "  .$wpdb->prefix . "bojett_import_worker WHERE id != ''");
    if( count( $result_check ) == 0 ) {
        echo '<form style="display: inline;" action="' . $_SERVER['PHP_SELF'] . '?page=cws-bojett-patch&importstart=true" method="POST"><input type="submit" value="' . __('Start new import', 'codeswholesale_patch') . '" name="importstart" href="' . $_SERVER['PHP_SELF'] . '?page=cws-bojett-patch&importstart=true" class="page-title-action" /></form>';
    } else {
        echo '<style>.red-abort-import-button { color: red !important; border-color: red !important; margin-right: 10px !important; } </style>';
        $get_importstate = $wpdb->get_var('SELECT last_updated FROM '.$wpdb->prefix.'bojett_credentials');
        if($get_importstate != 'ABORTED') {
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=cws-bojett-patch&importabort=true" class="page-title-action red-abort-import-button">' . __('Stop current import', 'codeswholesale_patch') . '</a>';
        }
        $get_importnumber = $wpdb->get_var('SELECT importnumber FROM '.$wpdb->prefix.'bojett_credentials');
        echo '<code>' . $get_importnumber . ' ' . __('products were totally imported', 'codeswholesale_patch') . '</code>';
    }
    echo '<hr class="wp-header-end">
    <div class="importer_container"></div>

    </div>'; ?>
        <script>/*
    jQuery(function() {
        jQuery(".clickme").on("click", function() {
            jQuery.ajax({
      url: "/wp-content/plugins/codeswholesale-patch/importaction.php",
    }).done(function( data ) {
        jQuery(".wrap h1").after('<div class="success notice-success notice importcall"><p></p></div>');
          jQuery(".importer_container").html(data);
          console.log(data);
      });
        });
    });*/
    </script>
<?php
}


