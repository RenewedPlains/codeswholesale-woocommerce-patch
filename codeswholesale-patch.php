<?php
/*
Plugin Name: Bojett.com Codeswholesale Plus
Plugin URI: https://github.com/RenewedPlains/codeswholesale-woocommerce-patch
Description: Bojett.com Codeswholesale Plus is installed as an additional plugin, which retrieves the access data from the Codeswholesale for WooCommerce Plugin API and starts a new import process via the V2 API of Codeswholesale.
Text Domain: codeswholesale_patch
Depends: WooCommerce
Version: 0.9.1
Author: Mario Freuler
Author URI: https://www.bojett.com
License: GPL2
*/

ob_start( );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );

global $wpdb;


/*
 * Load the plugin textdomain for using the language templates
 */
function load_bojett_translations( )
{
    load_plugin_textdomain( 'codeswholesale_patch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'load_bojett_translations' );

/*
 * Create the required tables for the plugin by activation of the plugin.
 */
function create_plugin_database_tables( )
{
    // Kill all plugin databases
    global $table_prefix, $wpdb;
    $sql = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_auth_token';
    $wpdb->query($sql);
    $sql2 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_credentials';
    $wpdb->query($sql2);
    $sql3 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import';
    $wpdb->query($sql3);
    $sql4 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import_worker';
    $wpdb->query($sql4);
    $sql5 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_currency_rates';
    $wpdb->query($sql5);
    // Create new plugin databases
    $credentials = 'bojett_credentials';
    $bojett_credentials_table = $table_prefix . "$credentials";
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    if($wpdb->get_var( "show tables like '$bojett_credentials_table'" ) != $bojett_credentials_table)
    {
        $placeholder_image = esc_url( plugins_url( 'img/no-image.jpg', __FILE__ ) );
        $sql = "CREATE TABLE `". $bojett_credentials_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `cws_client_id`  varchar(128)  DEFAULT NULL, ";
        $sql .= "  `cws_client_secret`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `batch_size`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `phpworker`  varchar(128)  NOT NULL DEFAULT '5', ";
        $sql .= "  `importnumber`  varchar(128)  NOT NULL DEFAULT '20', ";
        $sql .= "  `auto_updates`  varchar(128)  NOT NULL DEFAULT '0', ";
        $sql .= "  `description_language`  varchar(128)  NOT NULL DEFAULT 'English', ";
        $sql .= "  `profit_margin_value`  varchar(128)  NOT NULL DEFAULT '10', ";
        $sql .= "  `product_currency`  varchar(128)  NOT NULL DEFAULT 'EUR', ";
        $sql .= "  `productarray_id`  varchar(128)   DEFAULT NULL, ";
        $sql .= "  `placeholder_image`  varchar(128)  NOT NULL DEFAULT '" . $placeholder_image . "', ";
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

    $currency_rates = 'bojett_currency_rates';
    $bojett_currency_rates_table = $table_prefix . "$currency_rates";
    if($wpdb->get_var( "show tables like '$bojett_currency_rates_table'" ) != $bojett_currency_rates_table)
    {
        $sql5 = "CREATE TABLE `". $bojett_currency_rates_table . "` ( ";
        $sql5 .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql5 .= "  `name`  varchar(128)   NOT NULL, ";
        $sql5 .= "  `value`  varchar(128)   NOT NULL, ";
        $sql5 .= "  `selected`  varchar(128)  NOT NULL DEFAULT '0', ";
        $sql5 .= "  `last_update`  varchar(128)   NOT NULL, ";
        $sql5 .= "  PRIMARY KEY (`id`) ";
        $sql5 .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta($sql5);
    }

    $wpdb->query( "TRUNCATE TABLE " .$wpdb->prefix . "bojett_currency_rates" );
    $chc = curl_init( 'https://api.exchangeratesapi.io/latest?base=EUR' );
    curl_setopt( $chc, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt( $chc, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $chc, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $chc, CURLOPT_FOLLOWLOCATION, 1 );
    $result = curl_exec( $chc );
    curl_close( $chc );
    $exchange_rates_eur = json_decode( $result, true )['rates'];
    $current_timestamp = time();
    $wpdb->insert( $bojett_currency_rates_table, array(
        'name' => 'EUR',
        'value' => '1',
        'last_update' => $current_timestamp
    ) );
    foreach($exchange_rates_eur as $currency_name => $currency_rate) {
        $current_timestamp = time();
        $wpdb->insert( $bojett_currency_rates_table, array(
            'name' => $currency_name,
            'value' => $currency_rate,
            'last_update' => $current_timestamp
        ) );
    }
}
register_activation_hook( __FILE__, 'create_plugin_database_tables' );

function delete_bojett_tables( )
{
    global $wpdb;
    $sql = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_auth_token';
    $wpdb->query($sql);
    $sql2 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_credentials';
    $wpdb->query($sql2);
    $sql3 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import';
    $wpdb->query($sql3);
    $sql4 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import_worker';
    $wpdb->query($sql4);
    $sql5 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_currency_rates';
    $wpdb->query($sql5);
}
register_deactivation_hook( __FILE__, 'delete_bojett_tables' );

/*
 * Check if the original codeswholesale plugin installed and activated.
 */
add_action( 'plugins_loaded', 'check_codeswholesale_plugin', 100 );
function check_codeswholesale_plugin( )
{
    if ( is_plugin_active( 'codeswholesale-for-woocommerce/codeswholesale.php' ) ) {
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
        unset( $_GET['activate'] );
        function my_error_notice( ) {
            ?>
            <div class="error notice">
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
function add_admin_menu_patch( )
{
    add_menu_page(
        __( 'Bojett.com', 'codeswholesale_patch' ),
        __( 'Bojett.com', 'codeswholesale_patch' ),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page',
         plugins_url( 'img/bojett_icon_128x128.png', __FILE__ ),
        3
    );
    add_submenu_page(
        'cws-bojett-patch',
        __( 'Importer', 'codeswholesale_patch' ),
        __( 'Importer', 'codeswholesale_patch' ),
        'manage_options',
        'cws-bojett-patch',
        'render_custom_link_page'
    );
    add_submenu_page(
        'cws-bojett-patch',
        __( 'Settings', 'codeswholesale_patch' ),
        __( 'Settings', 'codeswholesale_patch' ),
        'manage_options',
        'cws-bojett-settings',
        'bojett_settings'
    );
}
add_action( 'admin_menu', 'add_admin_menu_patch' );
// Change position from menu icon
add_filter( 'admin_menu', 'change_icon_style_start', 1 );
function change_icon_style_start( $template ) {
    ob_start( 'change_icon_style_end' );
    return $template;
}
function change_icon_style_end( $buffer ) {
    return str_replace( 'img/bojett_icon_128x128.png"','img/bojett_icon_128x128.png" style="max-width: 24px;margin-top:-3px;"', $buffer );
}

/*
 * Define and run Cronjob for refreshing the bearer in time
 */
function run_cws_cron_script( ) {
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $options_name = $wpdb->prefix . "bojett_credentials";
    $access_bearer = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
    $client_id = $wpdb->get_var( 'SELECT cws_client_id FROM ' . $options_name );
    $client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM ' . $options_name );
    $db_expires_in = $access_expires_in;
    $current_timestamp = time( );

    if( $db_expires_in > $current_timestamp && $db_expires_in !== NULL && $access_bearer !== NULL ) {
        if( $client_id == NULL || $client_secret == NULL ) {
            // Delete current bearer because no clientkeys are set
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $wpdb->query( "TRUNCATE TABLE $table_name" );
        }
        // Do nothing, the bearer is already up to date.
    } else {
        $options_name = $wpdb->prefix . "bojett_credentials";
        $client_id = $wpdb->get_var( 'SELECT cws_client_id FROM ' . $options_name );
        $client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM ' . $options_name );
        // Get new bearer from API credentials
        $chc = curl_init( 'https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret );
        curl_setopt( $chc, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
        curl_setopt( $chc, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $chc, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $chc, CURLOPT_FOLLOWLOCATION, 1 );
        $result = curl_exec( $chc );
        curl_close( $chc );
        $new_bearer = json_decode( $result, true )['access_token'];
        $new_bearer_expires = json_decode( $result, true )['expires_in'];
        $new_db_expires_in = $current_timestamp + $new_bearer_expires;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $wpdb->query( "TRUNCATE TABLE $table_name" );
        $wpdb->insert( $table_name, array(
            'cws_expires_in' => $new_db_expires_in,
            'cws_access_token' => $new_bearer
        ) );
    }
}

function check_update_bearer_token( ) {
    global $wpdb;
    if ( !wp_next_scheduled( 'check_update_bearer_token' ) ) {
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        wp_schedule_single_event( $access_expires_in, 'check_update_bearer_token' );
    }
}
add_action( 'check_update_bearer_token', 'run_cws_cron_script' );
check_update_bearer_token();

function pull_currencies() {
    global $wpdb;
    $currency_rates = 'bojett_currency_rates';
    $bojett_currency_rates_table = $wpdb->prefix . "$currency_rates";
    $wpdb->query( "TRUNCATE TABLE " .$wpdb->prefix . "bojett_currency_rates" );
    $chc = curl_init( 'https://api.exchangeratesapi.io/latest?base=EUR' );
    curl_setopt( $chc, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt( $chc, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $chc, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $chc, CURLOPT_FOLLOWLOCATION, 1 );
    $result = curl_exec( $chc );
    curl_close( $chc );
    $exchange_rates_eur = json_decode( $result, true )['rates'];
    $current_timestamp = time();
    $wpdb->insert( $bojett_currency_rates_table, array(
        'name' => 'EUR',
        'value' => '1',
        'last_update' => $current_timestamp
    ) );
    foreach($exchange_rates_eur as $currency_name => $currency_rate) {
        $current_timestamp = time();
        $wpdb->insert( $bojett_currency_rates_table, array(
            'name' => $currency_name,
            'value' => $currency_rate,
            'last_update' => $current_timestamp
        ) );
    }
}

function check_cws_currencies( ) {
    global $wpdb;
    if ( !wp_next_scheduled( 'check_cws_currencies' ) ) {
        $timestamp = time();
        wp_schedule_single_event( $timestamp + 3600, 'check_cws_currencies' );
    }
}
add_action( 'check_cws_currencies', 'pull_currencies' );
check_cws_currencies();

function media_uploader_enqueue() {
    wp_enqueue_media();
    wp_register_script('media-uploader', plugins_url('js/bojett.js' , __FILE__ ), array('jquery'));
    wp_enqueue_script('media-uploader');
}
add_action('admin_enqueue_scripts', 'media_uploader_enqueue');

if( !function_exists( 'get_wc_products_where_custom_field_is_set' ) )
{
    function get_wc_products_where_custom_field_is_set( $field, $value )
    {
        $products = wc_get_products(array('status' => 'publish',
            'meta_key' => $field,
            'meta_value' => $value, //'meta_value' => array('yes'),
            'meta_compare' => 'IN')); //'meta_compare' => 'NOT IN'));
        foreach ($products as $product) {
            $existing_pid = $product->get_id();
            return array(count($products), $existing_pid);
        }
    }
}
$auto_updates = $wpdb->get_var('SELECT auto_updates FROM ' . $wpdb->prefix . 'bojett_credentials');
if($auto_updates == '1') {
    function check_product_updates( )
    {
        global $wpdb;
        $filecontent = file_get_contents( 'php://input' );
        $decode_content = json_decode($filecontent);
        $updated_productid = $decode_content->products[0]->productId;
        $updated_productprice = $decode_content->products[0]->prices[1]->price;
        $updated_productstock = $decode_content->products[0]->quantity;
        $existcheck = get_wc_products_where_custom_field_is_set('_codeswholesale_product_id', $updated_productid);
        if($existcheck[0] >= 1 ) {
            $main_currency = $wpdb->get_var('SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_currency_value = $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency .'"');
            $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM ' . $wpdb->prefix . 'bojett_credentials');
            if(substr($profit_margin_value, -1, 1) == 'a') {
                $profit_margin_value = substr($profit_margin_value, 0, -1);
                $setprice = ($updated_productprice * $get_currency_value) + $profit_margin_value;
            } else {
                $profit_margin_value = substr($profit_margin_value, 0, -1);
                $cws_productprice_currency = $updated_productprice * $get_currency_value;
                $setprice = $cws_productprice_currency * ($profit_margin_value / 100) + $cws_productprice_currency;
            }
            update_post_meta($existcheck[1], '_regular_price', $setprice);
            update_post_meta($existcheck[1], '_price', $setprice);
            update_post_meta($existcheck[1], '_codeswholesale_product_stock_price', $updated_productprice);
            wc_update_product_stock($existcheck[1], $updated_productstock, 'set');
            //error_log('1 ' . $existcheck[1] . " - updated \n", 3, '../wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
        } else {
            //error_log('1 ' . $existcheck[1] . " - Produkt wurde nicht gefunden, somit wurde kein Update angewendet \n", 3, '../wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
        }
    }
    add_action('admin_post_nopriv_codeswholesale_notifications', 'check_product_updates');
}

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
        $auto_updates = $_POST['auto_updates'];
        $main_currency = $_POST['main_currency'];
        $profit_margin = $_POST['margin'];
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
            $profit_margin_value = $_POST['profit_margin_value'] . $profit_margin;
        } else {
            $profit_margin_value = '10' . $profit_margin;
        }
        if($_POST['placeholder_image'] != '') {
            $placeholder_image = $_POST['placeholder_image'];
        } else {
            $placeholder_image = esc_url( plugins_url( 'img/no-image.jpg', __FILE__ ) );
        }
        $description_language = $_POST['description_language'];
        //$get_credentials_check = $wpdb->get_var('SELECT cws_client_id, cws_client_secret FROM '.$table_prefix.'bojett_credentials');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . "bojett_credentials");

        $wpdb->insert($table_prefix . 'bojett_credentials', array(
            'cws_client_id' => $cws_client_id,
            'cws_client_secret' => $cws_secret_id,
            'batch_size' => $import_batch_size,
            'phpworker' => $import_worker,
            'description_language' => $description_language,
            'auto_updates' => $auto_updates,
            'profit_margin_value' => $profit_margin_value,
            'product_currency' => $main_currency,
            'placeholder_image' => $placeholder_image
        ));

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
    $get_place_holder = $wpdb->get_var('SELECT placeholder_image FROM '.$table_prefix.'bojett_credentials');
    $get_currency = $wpdb->get_var('SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials');
    $auto_updates = $wpdb->get_var('SELECT auto_updates FROM ' . $wpdb->prefix . 'bojett_credentials');
    if(substr($profit_margin_value, -1, 1) == 'a') {
        $margin = 'a';
    } else if(substr($profit_margin_value, -1, 1) == 'p') {
        $margin = 'p';
    } else {
        $margin = 'a';
    }

    $profit_margin_value = substr($profit_margin_value, 0, -1);
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
                        <td><input name="import_worker" type="number" placeholder="1-10" min="1" max="10" id="import_worker" aria-describedby="tagline-description" value="<?php echo $get_php_worker; ?>" class="regular-text">
                            <p class="description" id="tagline-description"><?php _e('This plugin works with cronjobs. Select the number of cronjobs that will be executed by the PHP server.', 'codeswholesale_patch'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="import_batch_size"><?php _e('Import batch size', 'codeswholesale_patch'); ?></label></th>
                        <td><input name="import_batch_size" type="number"  placeholder="1-100" min="1" max="100" id="import_batch_size" aria-describedby="tagline-description" value="<?php echo $get_batch_size; ?>" class="regular-text">
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
                        <th scope="row"><label for="main_currency"><?php _e('Main currency', 'codeswholesale_patch'); ?></label></th>
                        <td>
                        <select name="main_currency">
                                <?php
                                    $get_all_currencies = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'bojett_currency_rates');
                                    foreach($get_all_currencies as $single_currency) {
                                        echo '<option';
                                        if($get_currency == $single_currency->name) { echo ' selected'; }
                                        echo ' value="' . $single_currency->name . '">' . $single_currency->name . ' -- ' . $single_currency->value . ' ' . $single_currency->name . '</option>';
                                    }
                                ?>
                                </select><span> = 1 EUR</span>
                            <p class="description" id="tagline-description"><?php _e('CodesWholesale supplies prices in EUR. Choose your shop currency here to convert the prices automatically on a product update and new imports.', 'codeswholesale_patch'); ?></p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="automatic_product_updates"><?php _e('Automatic product updates', 'codeswholesale_patch'); ?></label></th>
                        <td>
                            <select name="auto_updates">
                                <option value="0"<?php if($auto_updates == '0') { echo ' selected'; } ?>><?php _e('Inactive', 'codeswholesale_patch'); ?></option>
                                <option value="1"<?php if($auto_updates == '1') { echo ' selected'; } ?>><?php _e('Active', 'codeswholesale_patch'); ?></option>
                            </select>
                            <?php $postback_url = get_site_url() . '/wp-admin/admin-post.php?action=codeswholesale_notifications'; ?>
                            <p class="description" id="tagline-description"><?php _e('Through the specified Postback URL at Codeswholesale.com, price and stock updates are transmitted individually. <br />Your Postback URL: ', 'codeswholesale_patch'); echo $postback_url; ?></p></td>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="profit_margin_value"><?php _e( 'Profit margin value', 'codeswholesale_patch' ); ?></label></th>
                        <td>
                            <div class="margin_switch" style="margin-top: 8px;">
                                <input type="radio" <?php if($margin == 'a') { echo 'checked '; } else if($margin != 'p' && $margin != 'a') { echo 'checked '; } ?>name="margin" value="a" id="margin_amount" style="margin-top: 0px;" /><label style="margin-right: 25px;" for="margin_amount"><?php _e( 'Amount', 'codeswholesale_patch' ); ?></label>
                                <input type="radio" <?php if($margin == 'p') { echo 'checked '; } ?>name="margin" value="p" id="margin_percentage" style="margin-top: 0px;" /><label for="margin_percentage"><?php _e( 'Percentage', 'codeswholesale_patch' ); ?></label>
                            </div>
                            <br /><br />
                            <?php $get_currency_value = $wpdb->get_var('SELECT `product_currency` FROM ' . $wpdb->prefix . 'bojett_credentials'); ?>
                            <input name="profit_margin_value" type="number" id="profit_margin_value" aria-describedby="tagline-description" value="<?php echo $profit_margin_value; ?>" class="regular-text">
                            <span class="margin_val"><?php echo $get_currency_value; ?></span>
                            <p class="description" id="tagline-description"><?php _e('The product is imported in EUR. Indicate how much profit you want to make in your shop currency per purchase.', 'codeswholesale_patch'); ?></p>
                        </td>
                    </tr>
                    <tr><th scope="row"><label for="placeholder_image"><?php _e('Placeholder image', 'codeswholesale_patch'); ?></label></th>
                        <td><input id="background_image" type="text" class="regular-text" name="placeholder_image" value="<?php echo $get_place_holder; ?>" />
                        <input id="upload_image_button" type="button" class="button-primary" value="<?php _e('Search in Media...', 'codeswholesale_patch'); ?>" />
                            <p class="description" id="tagline-description"><?php _e('If the CWS API does not provide a product image for the importing product, this fallback image is used.', 'codeswholesale_patch'); ?></p></td>
                        </td>
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
    $import_worker_last_update = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'bojett_import_worker');
    $update_lapse = array();
    foreach($import_worker_last_update as $last_time) {
        if($last_time->last_update + 360 < time()) {
            array_push($update_lapse, $last_time->name);
            if(count($import_worker_last_update) == count($update_lapse)) {
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
}
validate_importer();

if($_GET['importstart'] == 'true' && $_POST['importstart']) {
    $table_name = $wpdb->prefix . "bojett_import_worker";
    $wpdb->query("TRUNCATE TABLE $table_name");
    $token = $wpdb->get_var('SELECT cws_access_token FROM '.$wpdb->prefix.'bojett_auth_token');
    $authorization = "Authorization: Bearer " . $token;
    $ch = curl_init('https://api.codeswholesale.com/v2/products');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }
    $result = curl_exec($ch);
    curl_close($ch);

    $handle = fopen (plugin_dir_path( __FILE__ ) . '/includes/current_import.txt', 'w') or die("Unable to open file!");
    fwrite ($handle, $result);
    fclose ($handle);
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
            wp_schedule_single_event( $timestamp, 'import_batch', $args );
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
                wp_schedule_single_event($timestamp, 'import_batch_' . $i, $args);
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


