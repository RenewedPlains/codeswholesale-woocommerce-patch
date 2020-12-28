<?php
/*
Plugin Name: Bojett.com Codeswholesale Plus
Plugin URI: https://github.com/RenewedPlains/codeswholesale-woocommerce-patch
Description: Bojett.com Codeswholesale Plus is installed as an additional plugin, which retrieves the access data from the Codeswholesale for WooCommerce Plugin API and starts a new import process via the V2 API of Codeswholesale.
Text Domain: codeswholesale_patch
Depends: WooCommerce
Version: 0.9.3
Author: Mario Freuler
Author URI: https://www.bojett.com
License: GPL2
*/

/*
 * Include and call necessary function and files.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once( ABSPATH . 'wp-load.php' );
require_once( ABSPATH . 'wp-config.php' );
require_once( 'vendor/autoload.php' );
global $wpdb;


/*
 * Load the plugin textdomain for using the language templates.
 */
function load_bojett_translations( ) {
    load_plugin_textdomain( 'codeswholesale_patch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'load_bojett_translations' );


/*
 * Create the required tables for the plugin by activation of the plugin.
 */
function create_plugin_database_tables( ) {
    // Kill all plugin tables before creating new tables for plugin activation.
    global $table_prefix, $wpdb;
    $sql = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_auth_token';
    $wpdb->query( $sql );
    $sql2 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_credentials';
    $wpdb->query( $sql2 );
    $sql3 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import';
    $wpdb->query( $sql3 );
    $sql4 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import_worker';
    $wpdb->query( $sql4 );
    $sql5 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_currency_rates';
    $wpdb->query( $sql5 );
    // Create new plugin databases
    $credentials = 'bojett_credentials';
    $bojett_credentials_table = $table_prefix . "$credentials";
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    if ( $wpdb->get_var( "show tables like '$bojett_credentials_table'" ) != $bojett_credentials_table ) {
        $placeholder_image = esc_url( plugins_url( 'img/no-image.jpg', __FILE__ ) );
        $sql = "CREATE TABLE `". $bojett_credentials_table . "` ( ";
        $sql .= "  `id` int(11) NOT NULL auto_increment, ";
        $sql .= "  `cws_client_id` varchar(128) DEFAULT NULL, ";
        $sql .= "  `cws_client_secret` varchar(128) DEFAULT NULL, ";
        $sql .= "  `batch_size` varchar(128) DEFAULT NULL, ";
        $sql .= "  `phpworker` varchar(128) NOT NULL DEFAULT '5', ";
        $sql .= "  `importnumber` varchar(128) NOT NULL DEFAULT '20', ";
        $sql .= "  `auto_updates` varchar(128) NOT NULL DEFAULT '0', ";
        $sql .= "  `description_language` varchar(128) NOT NULL DEFAULT 'English', ";
        $sql .= "  `profit_margin_value` varchar(128) NOT NULL DEFAULT '10', ";
        $sql .= "  `product_currency` varchar(128) NOT NULL DEFAULT 'EUR', ";
        $sql .= "  `productarray_id` varchar(128) DEFAULT NULL, ";
        $sql .= "  `import_started` varchar(128) DEFAULT NULL, ";
        $sql .= "  `placeholder_image` varchar(128) NOT NULL DEFAULT '" . $placeholder_image . "', ";
        $sql .= "  `postback_creator` int(13) DEFAULT NULL, ";
        $sql .= "  `last_updated` varchar(128) DEFAULT NULL, ";
        $sql .= "  PRIMARY KEY (`id`)";
        $sql .= ") ENGINE=INNODB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta( $sql );
    }
    $token = 'bojett_auth_token';
    $bojett_token_table = $table_prefix . "$token";
    if( $wpdb->get_var( "show tables like '$bojett_token_table'" ) != $bojett_token_table ) {
        $sql2 = "CREATE TABLE `". $bojett_token_table . "` ( ";
        $sql2 .= "  `id`  int(11) NOT NULL auto_increment, ";
        $sql2 .= "  `cws_expires_in` varchar(128) DEFAULT NULL, ";
        $sql2 .= "  `cws_access_token` varchar(128) DEFAULT NULL, ";
        $sql2 .= "  PRIMARY KEY (`id`) ";
        $sql2 .= ") ENGINE=INNODB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta( $sql2 );
    }
    $import = 'bojett_import';
    $bojett_import_table = $table_prefix . "$import";
    if ( $wpdb->get_var( "show tables like '$bojett_import_table'" ) != $bojett_import_table ) {
        $sql3 = "CREATE TABLE `". $bojett_import_table . "` ( ";
        $sql3 .= "  `id` int(11) NOT NULL auto_increment, ";
        $sql3 .= "  `cws_id` varchar(128) NOT NULL, ";
        $sql3 .= "  `cws_game_title` varchar(128) NOT NULL, ";
        $sql3 .= "  `cws_game_price` varchar(128) NOT NULL, ";
        $sql3 .= "  `cws_phpworker` varchar(128) NOT NULL, ";
        $sql3 .= "  `created_at` varchar(128) NOT NULL, ";
        $sql3 .= "  PRIMARY KEY (`id`) ";
        $sql3 .= ") ENGINE=INNODB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta( $sql3 );
    }
    $worker = 'bojett_import_worker';
    $bojett_worker_table = $table_prefix . "$worker";
    if ( $wpdb->get_var( "show tables like '$bojett_worker_table'" ) != $bojett_worker_table ) {
        $sql4 = "CREATE TABLE `". $bojett_worker_table . "` ( ";
        $sql4 .= "  `id` int(11)   NOT NULL auto_increment, ";
        $sql4 .= "  `name` varchar(128) NULL, ";
        $sql4 .= "  `last_product` varchar(128) NULL, ";
        $sql4 .= "  `last_update` varchar(128) NOT NULL, ";
        $sql4 .= "  `cws_message` varchar(128) NULL, ";
        $sql4 .= "  PRIMARY KEY (`id`) ";
        $sql4 .= ") ENGINE=INNODB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta( $sql4 );
    }
    $currency_rates = 'bojett_currency_rates';
    $bojett_currency_rates_table = $table_prefix . "$currency_rates";
    if ( $wpdb->get_var( "show tables like '$bojett_currency_rates_table'" ) != $bojett_currency_rates_table ) {
        $sql5 = "CREATE TABLE `". $bojett_currency_rates_table . "` ( ";
        $sql5 .= "  `id` int(11) NOT NULL auto_increment, ";
        $sql5 .= "  `name` varchar(128) NOT NULL, ";
        $sql5 .= "  `value` varchar(128) NOT NULL, ";
        $sql5 .= "  `selected` varchar(128) NOT NULL DEFAULT '0', ";
        $sql5 .= "  `last_update` varchar(128) NOT NULL, ";
        $sql5 .= "  PRIMARY KEY (`id`) ";
        $sql5 .= ") ENGINE=INNODB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        dbDelta( $sql5 );
    }
    // Request of current currencies with values to EUR
    $wpdb->query( "TRUNCATE TABLE " .$wpdb->prefix . "bojett_currency_rates" );
    $chc = curl_init( 'https://api.exchangeratesapi.io/latest?base=EUR' );
    curl_setopt( $chc, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
    curl_setopt( $chc, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $chc, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $chc, CURLOPT_FOLLOWLOCATION, 1 );
    $result = curl_exec( $chc );
    curl_close( $chc );
    $exchange_rates_eur = json_decode( $result, true )['rates'];
    $current_timestamp = current_time( 'timestamp' );
    $wpdb->insert( $bojett_currency_rates_table, array(
        'name' => 'EUR',
        'value' => '1',
        'last_update' => $current_timestamp
    ) );
    foreach( $exchange_rates_eur as $currency_name => $currency_rate ) {
        $current_timestamp = current_time( 'timestamp' );
        $wpdb->insert( $bojett_currency_rates_table, array(
            'name' => $currency_name,
            'value' => $currency_rate,
            'last_update' => $current_timestamp
        ) );
    }
}
register_activation_hook( __FILE__, 'create_plugin_database_tables' );


/*
 * Delete all plugin tables when deactivating the plugin.
 */
function delete_bojett_tables( ) {
    global $wpdb;
    $sql = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_auth_token';
    $wpdb->query( $sql );
    $sql2 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_credentials';
    $wpdb->query( $sql2 );
    $sql3 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import';
    $wpdb->query( $sql3 );
    $sql4 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_import_worker';
    $wpdb->query( $sql4 );
    $sql5 = "DROP TABLE IF EXISTS $wpdb->prefix". 'bojett_currency_rates';
    $wpdb->query( $sql5 );
}
register_deactivation_hook( __FILE__, 'delete_bojett_tables' );


/*
 * Check if the original codeswholesale plugin is installed and activated.
 */
add_action( 'plugins_loaded', 'check_codeswholesale_plugin', 100 );
function check_codeswholesale_plugin( ) {
    if ( !is_plugin_active( 'codeswholesale-for-woocommerce/codeswholesale.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        unset( $_GET['activate'] );
        function my_error_notice( ) { ?>
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
function add_admin_menu_patch( ) {
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
    /* add_submenu_page(
        'cws-bojett-patch',
        __( 'Postback log', 'codeswholesale_patch' ),
        __( 'Postback log', 'codeswholesale_patch' ),
        'manage_options',
        'cws-bojett-postback-log',
        'bojett_postback_log'
    );
    // TODO: Make logpage in menu. */
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
function bojett_postback_log() {
    global $table_prefix, $wpdb;
    // TODO: Make logpage in menu.
}

function guzzle_get( $uri, $bearertoken = '' ) {
    $client = new GuzzleHttp\Client( );
    $headers = [
        'Authorization' => 'Bearer ' . $bearertoken,
        'Accept'        => 'application/json',
    ];
    if ( $bearertoken != '' ) {
        $response = $client->request( 'GET', $uri, ['headers' => $headers, 'verify' => false, 'http_errors' => false] );
    } else {
        $response = $client->get( $uri );
    }
    if ( $response->getBody( ) ) {
        return $response->getBody( );
    }
}


/*
 * Define and run Cronjob for refreshing the bearer in time.
 */
function run_cws_cron_script( ) {
    global $wpdb;
    $options_name = $wpdb->prefix . "bojett_credentials";
    $client_id = $wpdb->get_var( 'SELECT `cws_client_id` FROM ' . $options_name );
    $client_secret = $wpdb->get_var( 'SELECT `cws_client_secret` FROM ' . $options_name );
    if (  $client_id != NULL && $client_secret != NULL ) {
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $access_bearer = $wpdb->get_var("SELECT `cws_access_token` FROM " . $table_name);
        $access_expires_in = $wpdb->get_var("SELECT `cws_expires_in` FROM " . $table_name);
        $db_expires_in = $access_expires_in - 5;
        $current_timestamp = current_time( 'timestamp' );
        if ( $db_expires_in != NULL && $access_bearer != NULL ) {
            if ( $db_expires_in > $current_timestamp ) {
                // If CWS bearer is higher then the current_time( 'timestamp' ), do nothing.
            } else {
                $import_worker_name = $wpdb->prefix . "bojett_import_worker";
                $check_worker_name = $wpdb->get_var( 'SELECT `name` FROM ' . $import_worker_name );
                if ( $check_worker_name != '' ) {
                    sleep( 5 );
                }
                $current_timestamp = current_time( 'timestamp' );
                $options_name = $wpdb->prefix . "bojett_credentials";
                $client_id = $wpdb->get_var( 'SELECT cws_client_id FROM ' . $options_name );
                $client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM ' . $options_name );
                // Get new bearer from API credentials
                $result = guzzle_get( 'https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret );
                $new_bearer = json_decode( $result, true )['access_token'];
                $new_bearer_expires = json_decode( $result, true )['expires_in'];
                $new_db_expires_in = $current_timestamp + $new_bearer_expires;
                $table_name = $wpdb->prefix . "bojett_auth_token";
                $wpdb->query( "TRUNCATE TABLE " . $table_name );
                // Insert new CWS bearer in table for auth token.
                $wpdb->insert( $table_name, array(
                    'cws_expires_in' => $new_db_expires_in,
                    'cws_access_token' => $new_bearer
                ) );
            }
        } else {
            $client_id = $wpdb->get_var( 'SELECT cws_client_id FROM ' . $options_name );
            $client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM ' . $options_name );
            // Get new bearer from API credentials
            $result = guzzle_get( 'https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret );
            $new_bearer = json_decode( $result, true )['access_token'];
            $new_bearer_expires = json_decode( $result, true )['expires_in'];
            $new_db_expires_in = $current_timestamp + $new_bearer_expires;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $wpdb->query( "TRUNCATE TABLE " . $table_name );
            // Insert new CWS bearer in table for auth token.
            $wpdb->insert( $table_name, array(
                'cws_expires_in' => $new_db_expires_in,
                'cws_access_token' => $new_bearer
            ) );
        }
    }
}


/*
 * Set a crontask for checking CWS bearer with a function, if no crontask is defined.
 */
function check_update_bearer_token( ) {
    global $wpdb;
    $options_name = $wpdb->prefix . "bojett_credentials";
    $client_id = $wpdb->get_var( 'SELECT cws_client_id FROM ' . $options_name );
    $client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM ' . $options_name );
    if( $client_id != NULL && $client_secret != NULL)  {
        if ( !wp_next_scheduled( 'check_update_bearer_token' ) ) {
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $access_expires_in = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
            wp_schedule_single_event( $access_expires_in, 'check_update_bearer_token' );
        }
    }
}
add_action( 'check_update_bearer_token', 'run_cws_cron_script' );

//check_update_bearer_token( );


/*
 * Get exchange value for other currencies and write it to currencies table.
 */
function pull_currencies( ) {
    global $wpdb;
    $currency_rates = 'bojett_currency_rates';
    $bojett_currency_rates_table = $wpdb->prefix . "$currency_rates";
    $wpdb->query( "TRUNCATE TABLE " .$wpdb->prefix . "bojett_currency_rates" );
    $result = guzzle_get('https://api.exchangeratesapi.io/latest?base=EUR' );
    $exchange_rates_eur = json_decode( $result, true )['rates'];
    $current_timestamp = current_time( 'timestamp' );
    $wpdb->insert( $bojett_currency_rates_table, array(
        'name' => 'EUR',
        'value' => '1',
        'last_update' => $current_timestamp
    ) );
    foreach( $exchange_rates_eur as $currency_name => $currency_rate ) {
        $current_timestamp = current_time( 'timestamp' );
        $wpdb->insert( $bojett_currency_rates_table, array(
            'name' => $currency_name,
            'value' => $currency_rate,
            'last_update' => $current_timestamp
        ) );
    }
}


/*
 * Set a crontask for checking current exchange values for currencies with a function, if no crontask is defined.
 */
function check_cws_currencies( ) {
    if ( !wp_next_scheduled( 'check_cws_currencies' ) ) {
        $timestamp = current_time( 'timestamp' );
        wp_schedule_single_event( $timestamp + 3600, 'check_cws_currencies' );
    }
}
add_action( 'check_cws_currencies', 'pull_currencies' );
//check_cws_currencies( );


/*
 * Initialize media uploader script, firstly for set a placeholder product image, if there nothing to import.
 */
function media_uploader_enqueue( ) {
    wp_enqueue_media( );
    wp_register_script( 'media-uploader', plugins_url( 'js/bojett.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script( 'media-uploader' );
}
add_action( 'admin_enqueue_scripts', 'media_uploader_enqueue' );


/*
 * Check product data from existing product with a custom field.
 */
if ( !function_exists( 'get_wc_products_where_custom_field_is_set' ) ) {
    function get_wc_products_where_custom_field_is_set( $field, $value ) {
        $products = wc_get_products( array( 'status' => 'publish',
            'meta_key' => $field,
            'meta_value' => $value, //'meta_value' => array('yes'),
            'meta_compare' => 'IN' ) ); //'meta_compare' => 'NOT IN'));
        foreach ( $products as $product ) {
            $existing_pid = $product->get_id( );
            return array( count( $products ), $existing_pid );
        }
    }
}


/*
 * Check if automatic updates and product inserter via CWS postback is enabled in settings.
 */
$auto_updates = $wpdb->get_var( 'SELECT auto_updates FROM ' . $wpdb->prefix . 'bojett_credentials' );
if ( $auto_updates == '1' ) {
    function check_product_updates( ) {
        global $wpdb;
        $filecontent = file_get_contents( 'php://input' );
        $decode_content = json_decode( $filecontent );
        $updated_productid = $decode_content->products[0]->productId;
        $updated_productname = $decode_content->products[0]->productName;
        $updated_productprice = $decode_content->products[0]->prices[0]->price;
        $updated_productstock = $decode_content->products[0]->quantity;
        $timestamp = current_time( 'timestamp' );
        $existcheck = get_wc_products_where_custom_field_is_set( '_codeswholesale_product_id', $updated_productid );
        if( $updated_productname == '' ) {
            $updated_productname = get_the_title( $existcheck[1] );
        }
        if( $existcheck[0] >= 1 ) {
            // Product exist in the WooCommerce. Just do an update for it.
            $main_currency = $wpdb->get_var( 'SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials' );
            $get_currency_value = $wpdb->get_var( 'SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency .'"' );
            $profit_margin_value = $wpdb->get_var( 'SELECT profit_margin_value FROM ' . $wpdb->prefix . 'bojett_credentials' );
            if ( substr( $profit_margin_value, -1, 1 ) == 'a' ) {
                $profit_margin_value = substr( $profit_margin_value, 0, -1 );
                $setprice = ( $updated_productprice * $get_currency_value ) + $profit_margin_value;
                $wpdb->insert( $wpdb->prefix . 'bojett_import', array(
                    'cws_id' => $updated_productid,
                    'cws_game_title' => $updated_productname,
                    'cws_game_price' => $updated_productprice,
                    'cws_phpworker' => 'postback',
                    'created_at' => $timestamp
                ) );
            } else {
                $profit_margin_value = substr( $profit_margin_value, 0, -1 );
                $cws_productprice_currency = $updated_productprice * $get_currency_value;
                $setprice = $cws_productprice_currency * ( $profit_margin_value / 100 ) + $cws_productprice_currency;
                $wpdb->insert( $wpdb->prefix . 'bojett_import', array(
                    'cws_id' => $updated_productid,
                    'cws_game_title' => $updated_productname,
                    'cws_game_price' => $updated_productprice,
                    'cws_phpworker' => 'postback',
                    'created_at' => $timestamp
                ) );
            }
            update_post_meta( $existcheck[1], '_regular_price', $setprice );
            update_post_meta( $existcheck[1], '_price', $setprice );
            update_post_meta( $existcheck[1], '_codeswholesale_product_stock_price', $updated_productprice );
            update_post_meta( $existcheck[1], '_stock', $updated_productstock );
            if ( $updated_productstock == 0 ) {
                $out_of_stock_staus = 'outofstock';
                update_post_meta( $existcheck[1], '_stock_status', wc_clean( $out_of_stock_staus ) );
                wp_set_post_terms( $existcheck[1], 'outofstock', 'product_visibility', true );
            }
        } else {
            // Product doesn't exist in the WooCommerce.
            // Initialize bearer token and downloaded json file with all products in it
            global $wpdb;
            ini_set( 'memory_limit', '1500M' );
            $get_postback_creator = $wpdb->get_var( 'SELECT `postback_creator` FROM ' . $wpdb->prefix . 'bojett_credentials' );
            if( $get_postback_creator === NULL || $get_postback_creator == 0 ) {
                $get_cred_id = $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix.'bojett_credentials' );
                $wpdb->update(
                    $wpdb->prefix.'bojett_credentials',
                    array(
                        'postback_creator' => 1
                    ),
                    array( 'id' => $get_cred_id ),
                    array(
                        '%d'
                    ),
                    array( '%d' )
                );
                wp_schedule_single_event( current_time( 'timestamp' ) - 3590, 'leave_external_server_s', array( $decode_content ) );
            }
        }
    }
    add_action( 'admin_post_nopriv_codeswholesale_notifications', 'check_product_updates' );
}
add_action( 'leave_external_server_s', 'leave_external_server', 5, 3 );


/*
 * Check title directly from CWS product API. ((TODO: SUSPENDED, IT THOUGHT IT's NOT NEEDED ANYMORE))

if(! function_exists('checktitle' ) ) {
    function checktitle( $fix_title, $productId, $db_token ) {
        if ( $fix_title == '' ) {
            $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId, $db_token );
            $thetitle = json_decode( $result, true )['name'];
            return $thetitle;
        }
    }
}
 */
/*
 * Check title directly from CWS product API. ((TODO: SUSPENDED, IT THOUGHT IT's NOT NEEDED ANYMORE))

if ( !function_exists('get_single_product_title' ) ) {
    function get_single_product_title( $productId ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId, $db_token );
        $thetitle = json_decode( $result, true )['name'];
        return $thetitle;
    }
}
*/


/*
 * Get the current product categories as a array directly from the API.
 */
if ( !function_exists( 'get_single_product_categories' ) ) {
    function get_single_product_categories( $productId ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $db_token );
        $payload_array = explode( ', ', json_decode($result, true)['category'] );
        return $payload_array;
    }
}


/*
 * Get the current product screenshots and thumbnails a collected array directly from the API.
 */
if ( !function_exists( 'get_single_product_screenshots' ) ) {
    function get_single_product_screenshots( $productId ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $current_access_bearer_expire );
        $payload_array = json_decode( $result, true )['photos'];
        $photo_array = array( );
        if ( is_array( $payload_array ) ) {
            foreach( $payload_array as $product_image ) {
                $ch5 = curl_init( $product_image['url'] );
                curl_setopt( $ch5, CURLOPT_FOLLOWLOCATION, true );
                curl_setopt( $ch5, CURLOPT_RETURNTRANSFER, true );
                curl_exec( $ch5 );
                $url = curl_getinfo( $ch5, CURLINFO_EFFECTIVE_URL );
                if ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] == 'off' ) {
                    curl_setopt( $ch5, CURLOPT_SSL_VERIFYHOST, 0 );
                    curl_setopt( $ch5, CURLOPT_SSL_VERIFYPEER, 0 );
                }
                array_push( $photo_array, array( 'url' => $url, 'content_type' => curl_getinfo($ch5, CURLINFO_CONTENT_TYPE ) ) );
                curl_close( $ch5 ); // Close the cURL connection
            }
            return $photo_array;
        } else {
            return false;
        }
    }
}


/*
 * Get the current product description as a single string directly from the API.
 */
if ( !function_exists( 'get_single_product_description' ) ) {
    function get_single_product_description( $productId ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $db_token );
        $payload_array = json_decode( $result, true )['factSheets'];
        $settings_table = $wpdb->prefix . "bojett_credentials";
        $get_defined_import_language = $wpdb->get_var( "SELECT description_language FROM $settings_table" );
        if ( is_array( $payload_array ) ) {
            foreach( $payload_array as $product_description ) {
                if ( $product_description['territory'] == $get_defined_import_language && $product_description['description'] != '' ) {
                    return $product_description['description'];
                }
            }
        } else {
            return false;
        }
    }
}


/*
 * Upload product specific images, screenshots and thumbnail from URL to medias.
 */
if ( !function_exists('attach_product_thumbnail' ) ) {
    function attach_product_thumbnail( $post_id, $url, $flag, $extension ) {
        global $wpdb;
        $image_url = $url;
        if ( strstr( $image_url, 'no-image' ) ) {
            $image_url = $wpdb->get_var( 'SELECT placeholder_image FROM '.$wpdb->prefix.'bojett_credentials' );
        }
        $url_array = explode( '/', $url );
        $image_name = $url_array[count( $url_array ) - 1];
        $image_data = file_get_contents( $image_url ); // Get image data
        $upload_dir = wp_upload_dir( ); // Set upload folder
        $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); //    Generate unique name
        if ( $extension != '' ) {
            $filename = basename( $unique_file_name . $extension ); // Create image file name
        } else {
            $filename = basename( $unique_file_name ); // Create image file name
        }
        // Check folder permission and define file location
        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        // Create the image file on the server
        file_put_contents( $file, $image_data );
        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );
        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
        // Include image.php
        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );
        if ( $flag == 0 ) {
            // And finally assign featured image to post
            set_post_thumbnail( $post_id, $attach_id );
        } else if ( $flag == 1 ) {
            // Add gallery image to product
            $attach_id_array = get_post_meta( $post_id, '_product_image_gallery', true );
            $attach_id_array .= ',' . $attach_id;
            update_post_meta( $post_id, '_product_image_gallery', $attach_id_array );
        }
    }
}


/*
 * Insert product from postback action. Called by a cronjob.
 */
function leave_external_server( $value ) {
    global $wpdb;
    sleep( 10 );
    ini_set( 'memory_limit', '1500M' );
    // Get all products from local JSON file.
    $result = file_get_contents( ABSPATH . '/wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/current_import.txt' );
    $product_array = json_decode( $result, true )['items'];
    foreach( $product_array as $single_prod ) {
        set_time_limit( 120 );
        $searching_pid = $single_prod['productId'];
        if ( $searching_pid != $value ) {
            // Game not found. Proceed with foreach to find the right productId in local product JSON.
            continue;
        } else {
            $timestamp = current_time( 'timestamp' );
            $product_name = $single_prod['name'];
            $wpdb->insert( $wpdb->prefix . 'bojett_import', array(
                'cws_id' => $value,
                'cws_game_title' => $product_name,
                'cws_game_price' => 'DA',
                'cws_phpworker' => 'postbackc',
                'created_at' => $timestamp
            ) );
            // Initialize functions for inserting the new product informations from import sheet.
            global $wpdb;
            $product_thumbs = $single_prod['images'];
            foreach( $product_thumbs as $product_thumb ) {
                if( $product_thumb['format'] == 'MEDIUM' ) {
                    $productthumb = $product_thumb['image'];
                    // new curl pull to follow the permalink of the product thumbnail.
                    $ch4 = curl_init( $productthumb );
                    curl_setopt( $ch4, CURLOPT_FOLLOWLOCATION, true );
                    curl_setopt( $ch4, CURLOPT_RETURNTRANSFER, true );
                    if ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] == 'off' ) {
                        curl_setopt( $ch4, CURLOPT_SSL_VERIFYHOST, 0 );
                        curl_setopt( $ch4, CURLOPT_SSL_VERIFYPEER, 0 );
                    }
                    curl_exec( $ch4 );
                    $thumb = curl_getinfo( $ch4, CURLINFO_EFFECTIVE_URL );
                    curl_close( $ch4 );
                }
            }
            // Get productdata and insert it to tables. Create new products.
            $cws_productid = $single_prod['productId'];
            $productpicture = $thumb;
            $catalognumber = $single_prod['identifier'];
            $platform = $single_prod['platform'];
            $regions = $single_prod['regions'];
            if ( count( $regions ) > 1 ) {
                $region = implode(", ", $regions );
            } else {
                $region =  $regions[0];
            }
            $languages = $single_prod['languages'];
            if ( count( $languages ) > 1 ) {
                $language = implode(", ", $languages );
            } else {
                $language =  $languages[0];
            }
            $producttitle = $single_prod['name'];
            $productdescription = get_single_product_description( $cws_productid );
            $productcategories = get_single_product_categories( $cws_productid );
            $productphotos = get_single_product_screenshots( $cws_productid );
            $cws_productprice = $value->products[0]->prices[0]->price;
            $cws_quantity = $value->products[0]->quantity;
            if ( $productcategories[0] != "" ) {
                $tager = [];
                foreach ( $productcategories as $prod_cat ) {
                    if ( !term_exists( $prod_cat, 'product_cat' ) ) {
                        $term = wp_insert_term( $prod_cat, 'product_cat' );
                        array_push( $tager, $term['term_id'] );
                    } else {
                        $term_s = get_term_by( 'name', $prod_cat, 'product_cat' );
                        array_push( $tager, $term_s->term_id );
                    }
                }
            }

            $user_id = 1; // So, user is selected..
            if ( $producttitle == '' ) {
                $producttitle_set = $single_prod['name'];
            } else {
                $producttitle_set = $producttitle;
            }
            if ( $productdescription == '' ) {
                $productdescription_set = '';
            } else {
                $productdescription_set = $productdescription;
            }
            $post_pro = array(
                'post_author' => $user_id,
                'post_title' => $producttitle_set,
                'post_content' => $productdescription_set,
                'post_status' => 'publish',
                'post_type' => "product",
            );
            $main_currency = $wpdb->get_var( 'SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials' );
            $get_currency_value = $wpdb->get_var( 'SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency .'"' );
            $post_id = wp_insert_post( $post_pro );
            $profit_margin_value = $wpdb->get_var( 'SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials' );
            if( substr( $profit_margin_value, -1, 1 ) == 'a' ) {
                $profit_margin_value = substr( $profit_margin_value, 0, -1 );
                $realprice = ( $cws_productprice * $get_currency_value ) + $profit_margin_value;
            } else {
                $profit_margin_value = substr( $profit_margin_value, 0, -1 );
                $cws_productprice_currency = $cws_productprice * $get_currency_value;
                $realprice = $cws_productprice_currency * ( $profit_margin_value / 100 ) + $cws_productprice_currency;
            }
            wp_set_object_terms( $post_id, 'simple', 'product_type' );
            update_post_meta( $post_id, '_visibility', 'visible' );
            update_post_meta( $post_id, '_stock_status', 'instock' );
            update_post_meta( $post_id, 'total_sales', '0' );
            update_post_meta( $post_id, '_downloadable', 'no' );
            update_post_meta( $post_id, '_virtual', 'yes' );
            update_post_meta( $post_id, '_regular_price', $realprice );
            update_post_meta( $post_id, '_sale_price', '' );
            update_post_meta( $post_id, '_purchase_note', '' );
            update_post_meta( $post_id, '_featured', 'no' );
            update_post_meta( $post_id, '_codeswholesale_product_id', $cws_productid );
            update_post_meta( $post_id, '_codeswholesale_product_stock_price', $cws_productprice );
            update_post_meta( $post_id, '_sku', $catalognumber );
            $attr = array(
                array( 'name' => 'Language',
                    'value' => $language,
                    'position' => 1,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 0
                ),
                array( 'name' => 'Platform',
                    'value' => $platform,
                    'position' => 2,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 0
                ),
                array( 'name' => 'Region',
                    'value' => $region,
                    'position' => 3,
                    'is_visible' => 1,
                    'is_variation' => 0,
                    'is_taxonomy' => 0
                ),
            );
            update_post_meta( $post_id, '_product_attributes', $attr );
            update_post_meta( $post_id, '_sale_price_dates_from', '' );
            update_post_meta( $post_id, '_sale_price_dates_to', '' );
            update_post_meta( $post_id, '_price', $realprice );
            update_post_meta( $post_id, '_sold_individually', '' );
            update_post_meta( $post_id, '_manage_stock', 'yes' );
            update_post_meta( $post_id, '_backorders', 'no' );
            wc_update_product_stock( $post_id, $cws_quantity, 'set' );
            if( $cws_quantity == 0 ) {
                $out_of_stock_staus = 'outofstock';
                update_post_meta( $post_id, '_stock_status', wc_clean( $out_of_stock_staus ) );
                wp_set_post_terms( $post_id, 'outofstock', 'product_visibility', true );
            }
            wp_set_object_terms( $post_id, $tager, 'product_cat' );
            set_time_limit( 360 );
            attach_product_thumbnail( $post_id, $productpicture, 0, '' );
            if ( is_array( $productphotos ) ) {
                foreach( $productphotos as $screenshots ) {
                    //set gallery image
                    $screenshot_url = $screenshots['url'];
                    $screenshot_mime = $screenshots['content_type'];
                    if ( $screenshot_mime == 'image/jpeg' ) {
                        $screenshot_ext = '.jpg';
                        attach_product_thumbnail( $post_id, $screenshot_url, 1, $screenshot_ext );
                    } else if ( $screenshot_mime == 'image/png' ) {
                        $screenshot_ext = '.png';
                        attach_product_thumbnail( $post_id, $screenshot_url, 1, $screenshot_ext );
                    }
                }
            }
            $get_cred_id = $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix.'bojett_credentials' );
            $wpdb->update(
                $wpdb->prefix.'bojett_credentials',
                array(
                    'postback_creator' => 0
                ),
                array( 'id' => $get_cred_id ),
                array(
                    '%d'
                ),
                array( '%d' )
            );
            exit( );
        }
    }
    $get_cred_id = $wpdb->get_var( 'SELECT id FROM '.$wpdb->prefix.'bojett_credentials' );
    // Reset the Postbackimporter block, so that it's possible to import new products through postback.
    $wpdb->update(
        $wpdb->prefix.'bojett_credentials',
        array(
            'postback_creator' => 0
        ),
        array( 'id' => $get_cred_id ),
        array(
            '%d'
        ),
        array( '%d' )
    );
}

/*
 * Import mainimporter script for function references.
 */
require_once ( plugin_dir_path( __FILE__ ) . "importaction.php" );
//check_bearer_valid( );

/*
 * Check the CWS API settings and importer settings.
 */
function bojett_settings( ) {
    global $table_prefix, $wpdb;
    run_cws_cron_script( );
    if ( $_GET['notvalid'] == 'true' ) {
        function bojett_settings_notfound( ) { ?>
            <div class="error notice">
                <p><?php _e( 'First connect to Codeswholesale so that you can start importing products.', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'bojett_settings_notfound' );
        do_action( 'admin_notices' );
    }
    if ( $_POST['set_settings'] ) {
        $cws_client_id = $_POST['cws_client_id'];
        $cws_secret_id = $_POST['cws_secret_id'];
        $auto_updates = $_POST['auto_updates'];
        $main_currency = $_POST['main_currency'];
        $profit_margin = $_POST['margin'];
        if ( $_POST['import_worker'] != '' ) {
            $import_worker = $_POST['import_worker'];
        } else {
            $import_worker = '1';
        }
        if ( $_POST['import_batch_size'] != '' ) {
            $import_batch_size = $_POST['import_batch_size'];
        } else {
            $import_batch_size = '20';
        }
        if ( $profit_margin == 'a' ) {
            $get_cw_options = $wpdb->get_var( 'SELECT `option_value` FROM `' . $wpdb->prefix . 'options` WHERE `option_name` = "cw_options"' );
            $cw_options = unserialize( $get_cw_options );
            $cw_options['spread_type'] = 0;
            $cw_options['spread_value'] = $_POST['profit_margin_value'];
            $cw_options_update = serialize( $cw_options );
            $wpdb->update(
                $wpdb->prefix.'options',
                array(
                    'option_value' => $cw_options_update
                ),
                array( 'option_name' => 'cw_options' ),
                array(
                    '%s'
                ),
                array( '%s' )
            );
        } else {
            $get_cw_options = $wpdb->get_var( 'SELECT `option_value` FROM `' . $wpdb->prefix . 'options` WHERE `option_name` = "cw_options"' );
            $cw_options = unserialize( $get_cw_options );
            $cw_options['spread_type'] = 1;
            $cw_options['spread_value'] = $_POST['profit_margin_value'];
            $cw_options_update = serialize( $cw_options );
            $wpdb->update(
                $wpdb->prefix.'options',
                array(
                    'option_value' => $cw_options_update
                ),
                array( 'option_name' => 'cw_options' ),
                array(
                    '%s'
                ),
                array( '%s' )
            );
        }
        if ( $_POST['profit_margin_value'] != '' ) {
            $profit_margin_value = $_POST['profit_margin_value'] . $profit_margin;
        } else {
            $profit_margin_value = '10' . $profit_margin;
        }
        if( $_POST['placeholder_image'] != '' ) {
            $placeholder_image = $_POST['placeholder_image'];
        } else {
            $placeholder_image = esc_url( plugins_url( 'img/no-image.jpg', __FILE__ ) );
        }
        $description_language = $_POST['description_language'];
        //$get_credentials_check = $wpdb->get_var('SELECT cws_client_id, cws_client_secret FROM '.$table_prefix.'bojett_credentials');
        $wpdb->query( "TRUNCATE TABLE " . $wpdb->prefix . "bojett_credentials" );

        $wpdb->insert( $table_prefix . 'bojett_credentials', array(
            'cws_client_id' => $cws_client_id,
            'cws_client_secret' => $cws_secret_id,
            'batch_size' => $import_batch_size,
            'phpworker' => $import_worker,
            'description_language' => $description_language,
            'auto_updates' => $auto_updates,
            'profit_margin_value' => $profit_margin_value,
            'product_currency' => $main_currency,
            'placeholder_image' => $placeholder_image
        ) );
        run_cws_cron_script( );
        // require_once( plugin_dir_path( __FILE__ ) . '/includes/bearer-refreshrefresh.php' );
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        if ( $current_access_bearer != NULL && $current_access_bearer_expire != NULL ) {
            function bojett_settings_saved( ) { ?>
                <div class="success notice notice-success">
                    <p><?php _e( 'Your settings have been successfully saved.', 'codeswholesale_patch' ); ?></p>
                </div>
                <?php
            }
            add_action( 'admin_notices', 'bojett_settings_saved' );
            do_action( 'admin_notices' );
        } else {
            function bojett_settings_saved1( ) { ?>
                <div class="error notice">
                    <p><?php _e( 'Connection to CodesWholesale failed. Check your input.', 'codeswholesale_patch' ); ?></p>
                </div>
                <?php
            }
            add_action( 'admin_notices', 'bojett_settings_saved1' );
            do_action( 'admin_notices' );
        }
    }

    $get_client_id = $wpdb->get_var( 'SELECT cws_client_id FROM '.$table_prefix.'bojett_credentials' );
    $get_client_secret = $wpdb->get_var( 'SELECT cws_client_secret FROM '.$table_prefix.'bojett_credentials' );
    $get_batch_size = $wpdb->get_var( 'SELECT batch_size FROM '.$table_prefix.'bojett_credentials' );
    $get_php_worker = $wpdb->get_var( 'SELECT phpworker FROM '.$table_prefix.'bojett_credentials' );
    $get_description_language = $wpdb->get_var( 'SELECT description_language FROM '.$table_prefix.'bojett_credentials' );
    $profit_margin_value = $wpdb->get_var( 'SELECT profit_margin_value FROM '.$table_prefix.'bojett_credentials' );
    $get_place_holder = $wpdb->get_var( 'SELECT placeholder_image FROM '.$table_prefix.'bojett_credentials' );
    $get_currency = $wpdb->get_var( 'SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials' );
    $auto_updates = $wpdb->get_var( 'SELECT auto_updates FROM ' . $wpdb->prefix . 'bojett_credentials' );

    if ( substr( $profit_margin_value, -1, 1 ) == 'a' ) {
        $margin = 'a';
    } else if( substr( $profit_margin_value, -1, 1 ) == 'p' ) {
        $margin = 'p';
    } else {
        $margin = 'a';
    }
    $profit_margin_value = substr( $profit_margin_value, 0, -1 );
    $get_cw_options = $wpdb->get_var( 'SELECT `option_value` FROM `' . $wpdb->prefix . 'options` WHERE `option_name` = "cw_options"' );
    $cw_options = unserialize( $get_cw_options );
    if( $cw_options['environment'] == 1 ) {
        $api_client_id = $cw_options['api_client_id'];
        $api_client_secret = $cw_options['api_client_secret'];
    } ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php _e( 'Settings', 'codeswholesale_patch' ); ?></h1>
        <h2 class="title"><?php _e( 'Get your API Keys', 'codeswholesale_patch' ); ?></h2>
        <p><?php _e( 'To enable us to import all products, you need an available account at <a href="https://codeswholesale.com" target="_blank">CodesWholesale.com</a>.
                          In the backend of CWS you can look up and copy your API keys. This will generate a new authentication token so that your server can guarantee an outgoing connection and import all available product.', 'codeswholesale_patch' ); ?></p>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=cws-bojett-settings" method="post">
            <table class="form-table" role="presentation">
                <tbody><tr>
                    <th scope="row"><label for="cws_client_id"><?php _e('Your CWS API Client ID', 'codeswholesale_patch'); ?></label></th>
                    <td><input name="cws_client_id" type="text" id="cws_client_id" value="<?php if ( $get_client_id ) { echo $get_client_id; } else { echo $api_client_id; } ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cws_secret_id"><?php _e('Your CWS API Secret ID', 'codeswholesale_patch'); ?></label></th>
                    <td><input name="cws_secret_id" type="text" id="cws_secret_id" aria-describedby="tagline-description" value="<?php if ( $get_client_secret ) { echo $get_client_secret; } else { echo $api_client_secret; } ?>" class="regular-text">
                </tr>
                </tbody>
            </table><br />
            <!--<h2 class="title"><?php _e('Import settings', 'codeswholesale_patch'); ?></h2>
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
            </table>-->
            <h2 class="title"><?php _e('Product settings', 'codeswholesale_patch'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="description_language"><?php _e('Description language', 'codeswholesale_patch'); ?></label></th>
                    <td><select name="description_language">
                            <?php //TODO: Add available languages from CWS ?>
                            <option value="English"<?php if ( $get_description_language == 'English' ) { echo ' selected'; } ?>><?php _e( 'English', 'codeswholesale_patch' ); ?></option>
                            <option value="German"<?php if ( $get_description_language == 'German' ) { echo ' selected'; } ?>><?php _e( 'German', 'codeswholesale_patch' ); ?></option>
                            <option value="Italian"<?php if ( $get_description_language == 'Italian' ) { echo ' selected'; } ?>><?php _e( 'Italian', 'codeswholesale_patch' ); ?></option>
                        </select>
                        <p class="description" id="tagline-description"><?php _e( 'Select the language for the description imported from CodesWholesale.', 'codeswholesale_patch' ); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="main_currency"><?php _e( 'Main currency', 'codeswholesale_patch' ); ?></label></th>
                    <td>
                        <select name="main_currency">
                            <?php
                            $get_all_currencies = $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'bojett_currency_rates' );
                            foreach( $get_all_currencies as $single_currency ) {
                                echo '<option';
                                if ( $get_currency == $single_currency->name ) { echo ' selected'; }
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
                            <option value="0"<?php if ( $auto_updates == '0' ) { echo ' selected'; } ?>><?php _e( 'Inactive', 'codeswholesale_patch' ); ?></option>
                            <option value="1"<?php if ( $auto_updates == '1' ) { echo ' selected'; } ?>><?php _e( 'Active', 'codeswholesale_patch' ); ?></option>
                        </select>
                        <?php $postback_url = get_site_url( ) . '/wp-admin/admin-post.php?action=codeswholesale_notifications'; ?>
                        <p class="description" id="tagline-description"><?php _e( 'Through the specified Postback URL at Codeswholesale.com, price and stock updates are transmitted individually. <br />Your Postback URL: ', 'codeswholesale_patch' ); echo $postback_url; ?></p></td>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="profit_margin_value"><?php _e( 'Profit margin value', 'codeswholesale_patch' ); ?></label></th>
                    <td>
                        <div class="margin_switch" style="margin-top: 8px;">
                            <input type="radio" <?php if ( $margin == 'a' ) { echo 'checked '; } else if ( $margin != 'p' && $margin != 'a' ) { echo 'checked '; } ?>name="margin" value="a" id="margin_amount" style="margin-top: 0px;" /><label style="margin-right: 25px;" for="margin_amount"><?php _e( 'Amount', 'codeswholesale_patch' ); ?></label>
                            <input type="radio" <?php if ( $margin == 'p' ) { echo 'checked '; } ?>name="margin" value="p" id="margin_percentage" style="margin-top: 0px;" /><label for="margin_percentage"><?php _e( 'Percentage', 'codeswholesale_patch' ); ?></label>
                        </div>
                        <br /><br />
                        <?php $get_currency_value = $wpdb->get_var( 'SELECT `product_currency` FROM ' . $wpdb->prefix . 'bojett_credentials' ); ?>
                        <input name="profit_margin_value" type="number" id="profit_margin_value" aria-describedby="tagline-description" value="<?php echo $profit_margin_value; ?>" class="regular-text">
                        <span class="margin_val"><?php echo $get_currency_value; ?></span>
                        <p class="description" id="tagline-description"><?php _e( 'The product is imported in EUR. Indicate how much profit you want to make in your shop currency per purchase.', 'codeswholesale_patch' ); ?></p>
                    </td>
                </tr>
                <tr><th scope="row"><label for="placeholder_image"><?php _e( 'Placeholder image', 'codeswholesale_patch' ); ?></label></th>
                    <td><input id="background_image" type="text" class="regular-text" name="placeholder_image" value="<?php echo $get_place_holder; ?>" />
                        <input id="upload_image_button" type="button" class="button-primary" value="<?php _e( 'Search in Media...', 'codeswholesale_patch' ); ?>" />
                        <p class="description" id="tagline-description"><?php _e( 'If the CWS API does not provide a product image for the importing product, this fallback image is used.', 'codeswholesale_patch' ); ?></p></td>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="set_settings" id="submit" class="button button-primary" value="<?php _e( "Save Changes", 'codeswholesale_patch' ); ?>"></p>
        </form>
        </h1>
    </div>
    <?php
}


/* Callback function for post time and date filter hooks */
function meks_convert_to_time_ago( $orig_time ) {
    return human_time_diff( $orig_time, current_time( 'timestamp' ) ).' '.__( 'ago' );
}


/*
 * Get a part string from a string between another match.
 */
function get_string_between( $string, $start, $end ) {
    $string = ' ' . $string;
    $ini = strpos( $string, $start );
    if ( $ini == 0 ) return '';
    $ini += strlen( $start );
    $len = strpos( $string, $end, $ini ) - $ini;
    return substr( $string, $ini, $len );
}


/*
 * Call a wordpress message, if the importer is stuck for a while.
 */
function bojett_import_struggle( ) { ?>
    <div class="error notice">
        <p><?php _e( '<b>Uhoh!</b> It seems the importer got stuck. <a href="admin.php?page=cws-bojett-patch&forcekill=true">Click here</a> to force the import to stop. <a href="https://github.com/RenewedPlains/codeswholesale-woocommerce-patch" target="_blank">Please inform me</a> about the problem if it persists.', 'codeswholesale_patch' ); ?></p>
    </div>
    <?php
}


/*
 * Call a wordpress message, if the importworker is successfully killed.
 */
function bojett_import_killed( ) { ?>
    <div class="success notice-success notice">
        <p><?php _e( 'The importer was interrupted and removed. Please try the import again. <a href="https://github.com/RenewedPlains/codeswholesale-woocommerce-patch" target="_blank">Please inform me</a> about the problem if it persists.', 'codeswholesale_patch' ); ?></p>
    </div>
    <?php
}


/*
 * Check if one or more import worker are for longer time inactive. Get wordpress message.
 */
$check_importer_state = $wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'bojett_credentials' );
if ( $check_importer_state != NULL ) {
    function validate_importer() {
        global $wpdb;
        $import_worker_last_update = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bojett_import_worker');
        $update_lapse = array();
        foreach ($import_worker_last_update as $last_time) {
            if ($last_time->last_update + 360 < current_time('timestamp')) {
                array_push($update_lapse, $last_time->name);
                if (count($import_worker_last_update) == count($update_lapse)) {
                    if ($_GET['forcekill'] == 'true') {
                        $table_name = $wpdb->prefix . "bojett_import_worker";
                        $wpdb->query("TRUNCATE TABLE $table_name");
                        add_action('admin_notices', 'bojett_import_killed');
                    } else {
                        add_action('admin_notices', 'bojett_import_struggle');
                    }
                }
            }
        }
    }
    validate_importer();
}


/*
 *
 */
if($_GET['importstart'] == 'true' && $_POST['importstart']) {
    set_time_limit(480);
    ini_set('memory_limit', '1024M');
    $table_name = $wpdb->prefix . "bojett_import_worker";
    $wpdb->query("TRUNCATE TABLE $table_name");
    $token = $wpdb->get_var('SELECT cws_access_token FROM '.$wpdb->prefix.'bojett_auth_token');
    $result = guzzle_get('https://api.codeswholesale.com/v2/products', $token );
    $handle = fopen (plugin_dir_path( __FILE__ ) . '/includes/current_import.txt', 'w') or die("Unable to open file!");
    fwrite ($handle, $result);
    fclose ($handle);
} else if ( $_GET['importgo'] == 'resume' ) {
    set_time_limit( 120 );
    ini_set( 'memory_limit', '512M' );
    $table_name = $wpdb->prefix . "bojett_import_worker";
    $wpdb->query( "TRUNCATE TABLE $table_name" );
}

$get_php_worker = $wpdb->get_var('SELECT phpworker FROM '.$wpdb->prefix.'bojett_credentials');
if($get_php_worker == '1') {
    function import_batch() {
        global $wpdb;
        $table_title = $wpdb->prefix . 'bojett_import_worker';
        $get_batch_size = $wpdb->get_var('SELECT batch_size FROM '.$wpdb->prefix.'bojett_credentials');
        $get_product_count = $wpdb->get_var('SELECT last_product FROM '.$wpdb->prefix.'bojett_import_worker');
        if ( $get_product_count == 0 || $get_product_count == NULL ) {
            $get_product_count = 0;
        }
        $wpdb->insert($table_title, array(
            'name' => "import_batch",
            'last_product' => $get_product_count,
            'last_update' => current_time( 'timestamp' ),
        ));
        $import_variable = 'import_batch';
        if ( ! wp_next_scheduled( 'import_batch' ) ) {
            $timestamp = current_time( 'timestamp' );
            $args = array($import_variable);
            $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
            $current_wp_time = current_time( 'timestamp' );
            $wpdb->update(
                $wpdb->prefix.'bojett_credentials',
                array(
                    'import_started' => $current_wp_time,
                ),
                array( 'id' => $get_credentials_id ),
                array(
                    '%d'
                ),
                array( '%d' )
            );
            wp_clear_scheduled_hook( $import_variable, $args );
            wp_schedule_single_event( $timestamp - 3595, 'import_batch', array('import_batch') );
        }
    }
    add_action( 'import_batch', 'import_cws_product', 1, 1 );
}

if ( $_GET['importstart'] == 'true' && $_POST['importstart'] || $_GET['importgo'] == 'resume' ) {
    $get_php_worker = $wpdb->get_var('SELECT phpworker FROM '.$wpdb->prefix.'bojett_credentials');
    if($get_php_worker == '1') {
        //do_action('import_batch');
        import_batch();
    }
} elseif($_GET['importabort'] == 'true') {
    $get_cred_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
    $wpdb->update(
        $wpdb->prefix.'bojett_credentials',
        array(
            'last_updated' => 'ABORTED',
        ),
        array( 'id' => $get_cred_id ),
        array(
            '%s'
        ),
        array( '%d' )
    );
}
$import_worker_name = $wpdb->prefix . "bojett_import_worker";
$check_worker_name = $wpdb->get_var( 'SELECT `name` FROM ' . $import_worker_name );
$worker_update = $wpdb->prefix . 'bojett_import_worker';
$check_worker_last_update = $wpdb->get_var( 'SELECT `last_update` FROM ' . $worker_update );
$time_diff = current_time( 'timestamp' ) - $check_worker_last_update;
if( $time_diff > 240 ) {
    if( $check_worker_name != '' ) {
        if (!wp_next_scheduled('import_batch', array('import_batch'))) {
            //error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - $time_diff Autoenabler triggered. ! \n", 3, ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/passive_log.txt');
            wp_schedule_single_event(time() + 10, 'import_batch', array('import_batch'));
        }
    }
}


function render_custom_link_page() {
    global $wpdb;
    $get_acct = $wpdb->get_var('SELECT cws_access_token FROM '.$wpdb->prefix.'bojett_auth_token');
    $get_exp = $wpdb->get_var('SELECT cws_expires_in FROM '.$wpdb->prefix.'bojett_auth_token');

    if($get_acct == '' || $get_exp < current_time( 'timestamp' ) + 10 ) {
        run_cws_cron_script( );
        //header("Location: admin.php?page=cws-bojett-settings&notvalid=true");
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
    if($_GET['importstart'] == 'true' && $_POST['importstart'] || $_GET['importgo'] == 'resume') {
        function bojett_import_started() {
            ?>
            <div class="success notice notice-success">
                <p><?php _e( 'Import started successfully. The import will also continue when you leave the page. Come back here to see the status of the import.', 'codeswholesale_patch' ); ?></p>
            </div>
            <?php
        }
        add_action( 'admin_notices', 'bojett_import_started' );
        do_action( 'admin_notices' );
        if ( $_POST['importstart'] == 'true' && $_POST['importstart'] ) {
            $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
            $current_wp_time = current_time( 'timestamp' );
            $wpdb->update(
                $wpdb->prefix.'bojett_credentials',
                array(
                    'import_started' => $current_wp_time,
                ),
                array( 'id' => $get_credentials_id ),
                array(
                    '%d'
                ),
                array( '%d' )
            );
        }
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $productcounter = count(json_decode(inital_puller($db_token, ""), true)['items']);
        $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
        if ( $_GET['importgo'] != 'resume' ) {
            $wpdb->update(
                $wpdb->prefix . 'bojett_credentials',
                array(
                    'importnumber' => $productcounter,
                ),
                array('id' => $get_credentials_id),
                array(
                    '%d'
                ),
                array('%d')
            );
            unset($_POST['importstart']);
        }
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
    echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=cws-bojett-patch&importgo=resume">Play</a>';
    echo '<hr class="wp-header-end">
    <div class="importer_container"></div>';
    include_once('includes/importer-ui.php');
    echo '</div>';
    $import_worker = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'bojett_import_worker');
    $auto_updates = $wpdb->get_var('SELECT auto_updates FROM ' . $wpdb->prefix . 'bojett_credentials');

    if(count($import_worker) != 0 || $auto_updates != 0) {
        $ui_path = esc_url( plugins_url( 'includes/importer-ui.php?get_as_json=true', __FILE__ ) );
        ?>

        <script>
            jQuery(function() {
                setInterval(function() {
                    var i = 1;
                    jQuery.ajax({
                        url: '<?php echo $ui_path; ?>',
                    }).done(function( data ) {
                        var result = JSON.parse(jQuery(data).filter('.metaimport').html());
                        jQuery.each(result, function(importbatch) {
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .product_title').html(this.cws_game_title);
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .product_message').html(this.cws_message);
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .big_count').html(this.last_product);
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .from_import').html(this.from_all);
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .to_import').html(this.to_all);
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .product_price').html(this.cws_game_price + ' EUR');
                            jQuery('.plugin-card.importer:nth-child(' + i + ') .timeago').html(this.cws_last_update);
                            i++;
                        });
                        jQuery('.plugin-card.postbackupdater .product_title').html(result.postback.cws_game_title);
                        jQuery('.plugin-card.postbackupdater .product_message').html(result.postback.cws_message);
                        jQuery('.plugin-card.postbackupdater .product_price').html(result.postback.cws_game_price + ' EUR');
                        jQuery('.plugin-card.postbackupdater .timeago').html(result.postback.cws_last_update);
                    });
                }, 2500);
            });
        </script>
    <?php }
}


