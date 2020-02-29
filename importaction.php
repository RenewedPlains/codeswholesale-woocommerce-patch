<?php
function inital_puller($token, $resulti) {
    $result = file_get_contents('../wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/current_import.txt');
    return $result;
}

if($_GET['importstart'] == 'true') {
    set_time_limit(120);
    ini_set('memory_limit', '512M');
}

function import_cws_product( $from, $to, $import_variable )
{
    set_time_limit(240);
    ini_set('memory_limit', '512M');
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    require_once(ABSPATH . 'wp-load.php');
    require_once(ABSPATH . 'wp-config.php');
    global $wpdb;

    if (!function_exists('check_bearer_valid'))  {
        function check_bearer_valid() {
            global $wpdb, $import_variable;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $current_access_bearer_expire = $wpdb->get_var("SELECT cws_expires_in FROM $table_name");
            if ($current_access_bearer_expire - 120 <= time()) {
                $expire_diff = $current_access_bearer_expire - time();
                error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . ": Bearer is expiring! :( " . $expire_diff . "s  \n", 3, 'wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/passive_log.txt');
                sleep($expire_diff + 10);
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
            }
        }
    }

    check_bearer_valid();

    $wpdb->update(
        $wpdb->prefix . 'bojett_import_worker',
        array(
            'last_update' => time(),
            'from' => $from,
            'to' => $to
        ),
        array('name' => $import_variable),
        array(
            '%s',
            '%s'
        ),
        array('%s')
    );

    if( !function_exists( 'get_single_product_description' ) )
    {
        function get_single_product_description( $productId )
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
            $db_token = $current_access_bearer_expire;
            $ch = curl_init( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description' );
            $authorization = "Authorization: Bearer " . $db_token;
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json', $authorization ) );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            $result = curl_exec($ch);
            $payload_array = json_decode($result, true)['factSheets'];
            $settings_table = $wpdb->prefix . "bojett_credentials";
            $get_defined_import_language = $wpdb->get_var("SELECT description_language FROM $settings_table");
            curl_close($ch);

            if (is_array($payload_array)) {
                foreach ($payload_array as $product_description) {
                    if ($product_description['territory'] == $get_defined_import_language && $product_description['description'] != '') {
                        return $product_description['description'];
                    }
                }
            } else {
                return false;
            }
        }
    }
    if(! function_exists('update_worker')) {
        function update_worker($gameid, $gametitle, $gameprice, $importworker, $message)
        {
            global $wpdb;
            $timestamp = time();
            if($gametitle != false) {
                $wpdb->insert($wpdb->prefix . 'bojett_import', array(
                    'cws_id' => $gameid,
                    'cws_game_title' => $gametitle,
                    'cws_game_price' => $gameprice,
                    'cws_phpworker' => $importworker,
                    'created_at' => $timestamp
                ));
            }
            $wpdb->update(
                $wpdb->prefix . 'bojett_import_worker',
                array(
                    'last_update' => $timestamp,
                    'cws_message' => $message
                ),
                array('name' => $importworker),
                array(
                    '%s',
                    '%s'
                ),
                array('%s')
            );
        }
    }
    if(! function_exists('checktitle')) {
        function checktitle($fix_title, $productId, $db_token)
        {
            if ($fix_title == '') {
                $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId); // Initialise cURL
                $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
                if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                }
                $result = curl_exec($ch); // Execute the cURL statement
                $thetitle = json_decode($result, true)['name'];
                curl_close($ch); // Close the cURL connection
                return $thetitle;
            }
        }
    }
    if(! function_exists('get_single_product_title')) {

        function get_single_product_title($productId)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $current_access_bearer_expire = $wpdb->get_var("SELECT cws_access_token FROM $table_name");
            $db_token = $current_access_bearer_expire;

            $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId); // Initialise cURL
            $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            $result = curl_exec($ch); // Execute the cURL statement
            $thetitle = json_decode($result, true)['name'];
            curl_close($ch); // Close the cURL connection
            return $thetitle;

        }
    }

    if(! function_exists('get_single_product_categories')) {

        function get_single_product_categories($productId)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $current_access_bearer_expire = $wpdb->get_var("SELECT cws_access_token FROM $table_name");
            $db_token = $current_access_bearer_expire;
            $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
            $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            $result = curl_exec($ch); // Execute the cURL statement
            $payload_array = explode(', ', json_decode($result, true)['category']);
            curl_close($ch); // Close the cURL connection
            return $payload_array;
        }
    }

    if(! function_exists('get_single_product_screenshots')) {

        function get_single_product_screenshots($productId)
        {
            global $wpdb, $import_variable;
            $table_name = $wpdb->prefix . "bojett_auth_token";
            $current_access_bearer = $wpdb->get_var("SELECT cws_expires_in FROM $table_name");
            $current_access_bearer_expire = $wpdb->get_var("SELECT cws_access_token FROM $table_name");
            $db_token = $current_access_bearer_expire;

            $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
            $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            }
            $result = curl_exec($ch); // Execute the cURL statement
            $payload_array = json_decode($result, true)['photos'];
            //var_dump($payload_array);
            $wpdb->update(
                $wpdb->prefix . 'bojett_import_worker',
                array(
                    'last_update' => time()
                ),
                array('name' => $import_variable),
                array(
                    '%d',
                ),
                array('%s')
            );
            $photo_array = array();
            if (is_array($payload_array)) {
                foreach ($payload_array as $product_image) {
                    //echo $product_image['url'];
                    $ch5 = curl_init($product_image['url']);
                    curl_setopt($ch5, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch5);
                    $url = curl_getinfo($ch5, CURLINFO_EFFECTIVE_URL);
                    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                        curl_setopt($ch5, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch5, CURLOPT_SSL_VERIFYPEER, 0);
                    }
                    array_push($photo_array, array('url' => $url, 'content_type' => curl_getinfo($ch5, CURLINFO_CONTENT_TYPE)));
                    curl_close($ch5); // Close the cURL connection
                }
                curl_close($ch); // Close the cURL connection

                return $photo_array;
            } else {
                return false;
            }
        }
    }

    if(! function_exists('attach_product_thumbnail')) {

        function attach_product_thumbnail($post_id, $url, $flag, $extension)
        {
            global $wpdb, $import_variable;
            $image_url = $url;
            if (strstr($image_url, 'no-image')) {
                $image_url = $wpdb->get_var('SELECT placeholder_image FROM '.$wpdb->prefix.'bojett_credentials');
            }
            $url_array = explode('/', $url);
            $image_name = $url_array[count($url_array) - 1];
            $image_data = file_get_contents($image_url); // Get image data
            $upload_dir = wp_upload_dir(); // Set upload folder
            $unique_file_name = wp_unique_filename($upload_dir['path'], $image_name); //    Generate unique name
            if ($extension != '') {
                $filename = basename($unique_file_name . $extension); // Create image file name
            } else {
                $filename = basename($unique_file_name); // Create image file name
            }
            // Check folder permission and define file location
            if (wp_mkdir_p($upload_dir['path'])) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }
            // Create the image file on the server
            file_put_contents($file, $image_data);
            // Check image file type
            $wp_filetype = wp_check_filetype($filename, null);
            // Set attachment data
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            // Create the attachment
            $attach_id = wp_insert_attachment($attachment, $file, $post_id);
            // Include image.php
            // Define attachment metadata
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            // Assign metadata to attachment
            wp_update_attachment_metadata($attach_id, $attach_data);
            // asign to feature image
            $wpdb->update(
                $wpdb->prefix . 'bojett_import_worker',
                array(
                    'last_update' => time()
                ),
                array('name' => $import_variable),
                array(
                    '%d',
                ),
                array('%s')
            );
            if ($flag == 0) {
                // And finally assign featured image to post
                set_post_thumbnail($post_id, $attach_id);
            }
            // assign to the product gallery
            if ($flag == 1) {
                // Add gallery image to product
                $attach_id_array = get_post_meta($post_id, '_product_image_gallery', true);
                $attach_id_array .= ',' . $attach_id;
                update_post_meta($post_id, '_product_image_gallery', $attach_id_array);
            }
        }
    }


    if(! function_exists('get_wc_products_where_custom_field_is_set')) {

        function get_wc_products_where_custom_field_is_set($field, $value)
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

    if(! function_exists('inital_pull')) {

        function inital_pull($token, $resulti)
        {
            $result = file_get_contents('wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/current_import.txt');
            return $result;
        }
    }

    set_time_limit(120);
    ini_set('memory_limit', '512M');

    global $wpdb;

    $importtime = array();

    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;

    $result = file_get_contents('wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/current_import.txt');
    $result_array = json_decode($result,true);
    $products_count = count(json_decode($result,true)['items']);


    global $products_count, $result;
    for ($i = $from; $i < $to; $i++) {
        $get_credentials_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
        $productarray = $i + 1;
        $wpdb->update(
            $wpdb->prefix.'bojett_credentials',
            array(
                'productarray_id' => $productarray
            ),
            array( 'id' => $get_credentials_id ),
            array(
                '%d'
            ),
            array( '%d' )
        );
    //for ($i = 0; $i <= $products_count - 1; $i++) {
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;
    $result = inital_pull($db_token, $result);

    $product_thumbs = json_decode($result, true)['items'][$i]['images'];
    $products_count = count(json_decode($result, true)['items']);
    foreach ($product_thumbs as $product_thumb) {
        if ($product_thumb['format'] == 'MEDIUM') {
            $productthumb = $product_thumb['image'];
            // new curl pull to follow the permalink of the product thumbnail.
            $ch4 = curl_init($productthumb);
            curl_setopt($ch4, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch4, CURLOPT_RETURNTRANSFER, true);
            if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
                curl_setopt($ch4, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch4, CURLOPT_SSL_VERIFYPEER, 0);
            }
            curl_exec($ch4);
            $thumb = curl_getinfo($ch4, CURLINFO_EFFECTIVE_URL);
            curl_close($ch4);
        }
    }



// Get productdata
    $cws_productid = json_decode($result, true)['items'][$i]['productId']; // gets cws product id e.g. 04a8137c-0de9-42d4-8959-f15ca2567862

    $productpicture = $thumb; // will return a single string for main productimage e.g. https://api.codeswholesale.com/v1/products/f62cab33-27ec-4c3d-a0ea-b3c7925c7fbf/image?format=MEDIUM
    $catalognumber = json_decode($result, true)['items'][$i]['identifier']; // gets cws product SKU as String e.g. "MMCOHEU"
    $platform = json_decode($result, true)['items'][$i]['platform'];
    $regions = json_decode($result, true)['items'][$i]['regions'];

    if(count($regions) > 1) {
        $region = implode(", ", $regions);
    } else  {
        $region =  $regions[0];
    }

    $languages = json_decode($result, true)['items'][$i]['languages'];
    if(count($languages) > 1) {
        $language = implode(", ", $languages);
    }else  {
        $language =  $languages[0];
    }
    $producttitle = get_single_product_title($cws_productid);
    if($producttitle == '' || $producttitle == ' ') {
        continue;
    }

    $productdescription = get_single_product_description($cws_productid);
    $productcategories = get_single_product_categories($cws_productid);
    $productphotos = get_single_product_screenshots($cws_productid);
    $cws_productprice = json_decode($result, true)['items'][$i]['prices'][2]['value'];
    $cws_quantity = json_decode($result, true)['items'][$i]['quantity'];
    $existcheck = get_wc_products_where_custom_field_is_set('_codeswholesale_product_id', $cws_productid);
    update_worker($cws_productid, $producttitle, $cws_productprice, $import_variable, __("Product data are read in.", "codeswholesale_patch"));
    if($existcheck[0] >= 1 ) {
        // Product exists
        $main_currency = $wpdb->get_var('SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials');
        $get_currency_value = $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency .'"');
        $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials');
        if(substr($profit_margin_value, -1, 1) == 'a') {
            $profit_margin_value = substr($profit_margin_value, 0, -1);
            $setprice = ($cws_productprice * $get_currency_value) + $profit_margin_value;
            update_post_meta($existcheck[1], '_regular_price', $setprice);
            update_post_meta($existcheck[1], '_price', $setprice);
            update_post_meta($existcheck[1], '_codeswholesale_product_stock_price', $cws_productprice);
            wc_update_product_stock($existcheck[1], $cws_quantity, 'set');
        } else {
            $profit_margin_value = substr($profit_margin_value, 0, -1);
            $cws_productprice_currency = $cws_productprice * $get_currency_value;
            $setprice = $cws_productprice_currency * ($profit_margin_value / 100) + $cws_productprice_currency;
            update_post_meta($existcheck[1], '_regular_price', $setprice);
            update_post_meta($existcheck[1], '_price', $setprice);
            update_post_meta($existcheck[1], '_codeswholesale_product_stock_price', $cws_productprice);
            wc_update_product_stock($existcheck[1], $cws_quantity, 'set');
        }
        update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product price is adjusted for currency and profit", "codeswholesale_patch"));
        set_time_limit(240);
        error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . " included wp-cron successfully after updating Product \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');

        set_time_limit(120);
        $wpdb->update(
            $wpdb->prefix.'bojett_import_worker',
            array(
                'last_product' => $i,
                'last_update' => time()
            ),
            array( 'name' => $import_variable ),
            array(
                '%d',
                '%d'
            ),
            array( '%s' )
        );
        update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product was successfully updated (price and stock)", "codeswholesale_patch"));
        $settings_table = $wpdb->prefix . "bojett_import_worker";
        $from = $wpdb->get_var("SELECT `from` FROM $settings_table WHERE `name` = '$import_variable'");
        $to = $wpdb->get_var("SELECT `to` FROM $settings_table WHERE `name` = '$import_variable'");
        if ($i == $to - 1) {
            $timestamp = time();
            update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Import worker was completed. Start new import batch", "codeswholesale_patch"));
            $table_name = $wpdb->prefix . "bojett_import_worker";
            wp_clear_scheduled_hook($import_variable, array($from, $to, $import_variable));
            $get_import_state = $wpdb->get_var('SELECT last_updated FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_batch_size = $wpdb->get_var('SELECT batch_size FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_worker = $wpdb->get_var('SELECT phpworker FROM ' . $wpdb->prefix . 'bojett_credentials');
            $from_new = $from + ($get_worker * $get_batch_size);
            $to_new = $from_new + $get_batch_size;
            $wpdb->update(
                $table_name,
                array(
                    'from' => $from_new,
                    'to' => $to_new,
                    'last_update' => time()
                ),
                array('name' => $import_variable),
                array(
                    '%d',
                    '%d',
                    '%d'
                ),
                array('%s')
            );
            if ($get_import_state != 'ABORTED') {
                add_action($import_variable, 'import_cws_product', 5, 3);
                wp_schedule_single_event($timestamp, $import_variable, array($from_new, $to_new, $import_variable));
                error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . " closed and restarted \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
            } else {
                $table_name = $wpdb->prefix . "bojett_import_worker";
                $wpdb->query("DELETE FROM $table_name WHERE `name` = '$import_variable'");
                error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . " closed and failed (ABORTED) restart \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
            }
            error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . " has returned and should be executed now \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
        }
        continue;
    }
    if ($productcategories[0] != "") {
        $tager = [];
        update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product categories are collected and applied", "codeswholesale_patch"));
        foreach ($productcategories as $prod_cat) {
            if (!term_exists($prod_cat, 'product_cat')) {
                $term = wp_insert_term($prod_cat, 'product_cat');
                var_dump($term);
                array_push($tager, $term['term_id']);
            } else {
                $term_s = get_term_by('name', $prod_cat, 'product_cat');
                array_push($tager, $term_s->term_id);
            }
        }

    }

    $user_id = 1; // So, user is selected..
    if ($producttitle == '') {
        $producttitle_set = json_decode($result, true)['items'][$i]['name'];
    } else {
        $producttitle_set = $producttitle;
    }
    if ($productdescription == '') {
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
    $main_currency = $wpdb->get_var('SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials');
    $get_currency_value = $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency .'"');
    $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials');
    $post_id = wp_insert_post($post_pro);
        $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials');
        if(substr($profit_margin_value, -1, 1) == 'a') {
            $profit_margin_value = substr($profit_margin_value, 0, -1);
            $realprice = ($cws_productprice * $get_currency_value) + $profit_margin_value;
        } else {
            $profit_margin_value = substr($profit_margin_value, 0, -1);
            $cws_productprice_currency = $cws_productprice * $get_currency_value;
            $realprice = $cws_productprice_currency * ($profit_margin_value / 100) + $cws_productprice_currency;
        }
        update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product prices are calculated", "codeswholesale_patch"));

        //$realprice = ($cws_productprice * $get_currency_value) + $profit_margin_value;
    wp_set_object_terms($post_id, 'simple', 'product_type');
    update_post_meta($post_id, '_visibility', 'visible');
    update_post_meta($post_id, '_stock_status', 'instock');
    update_post_meta($post_id, 'total_sales', '0');
    update_post_meta($post_id, '_downloadable', 'no');
    update_post_meta($post_id, '_virtual', 'yes');
    update_post_meta($post_id, '_regular_price', $realprice);
    update_post_meta($post_id, '_sale_price', '');
    update_post_meta($post_id, '_purchase_note', '');
    update_post_meta($post_id, '_featured', 'no');
    update_post_meta($post_id, '_codeswholesale_product_id', $cws_productid);
    update_post_meta($post_id, '_codeswholesale_product_stock_price', $cws_productprice);
    update_post_meta($post_id, '_sku', $catalognumber);

    $attr = array(
        array('name' => 'Language', // set attribute name
            'value' => $language, // set attribute value
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        ),
        array('name' => 'Platform', // set attribute name
            'value' => $platform, // set attribute value
            'position' => 2,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        ),
        array('name' => 'Region', // set attribute name
            'value' => $region, // set attribute value
            'position' => 3,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        ),
    );
    update_post_meta($post_id, '_product_attributes', $attr);
    update_post_meta($post_id, '_sale_price_dates_from', '');
    update_post_meta($post_id, '_sale_price_dates_to', '');
    update_post_meta($post_id, '_price', $realprice);
    update_post_meta($post_id, '_sold_individually', '');
    update_post_meta($post_id, '_manage_stock', 'yes');
    update_post_meta($post_id, '_backorders', 'no');
    wc_update_product_stock($post_id, $cws_quantity, 'set');
    wp_set_object_terms($post_id, $tager, 'product_cat');
    set_time_limit(120);
    update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product was successfully added (" . $post_id . ")", "codeswholesale_patch"));
    /**
     * Attach images to product (feature/ gallery)
     */


    attach_product_thumbnail($post_id, $productpicture, 0, '');
    if(is_array($productphotos)) {
        update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product pictures are collected", "codeswholesale_patch"));
        foreach ($productphotos as $screenshots) {
            //set gallery image
            $screenshot_url = $screenshots['url'];
            $screenshot_mime = $screenshots['content_type'];
            if ($screenshot_mime == 'image/jpeg') {
                $screenshot_ext = '.jpg';
                attach_product_thumbnail($post_id, $screenshot_url, 1, $screenshot_ext);
                update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product images were added to the product", "codeswholesale_patch"));
            } else if ($screenshot_mime == 'image/png') {
                $screenshot_ext = '.png';
                attach_product_thumbnail($post_id, $screenshot_url, 1, $screenshot_ext);
                update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product images were added to the product", "codeswholesale_patch"));
            }
        }
    }

    global $timestamp_start;

    $importtime[$i] = time();
    $stamp = date('d.m.Y - H:i:s', $importtime[$i]);
    $twiggle = $i - 1;
    $time_for_product = $importtime[$i] - $importtime[$twiggle];
    $time_for_product_s = $importtime[$i] - $timestamp_start;


    global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bojett_import_worker',
            array(
                'last_product' => $i,
                'last_update' => time()
            ),
            array('name' => $import_variable),
            array(
                '%d',
                '%d',
            ),
            array('%s')
        );
        if($i == $to - 1) {
            update_worker($cws_productid, false, $cws_productprice, $import_variable, _("Import worker was completed. Start new import batch", "codeswholesale_patch"));
            $timestamp = time();
            $table_name = $wpdb->prefix . "bojett_import_worker";
            wp_clear_scheduled_hook( $import_variable, array( $from, $to, $import_variable ) );
            $get_import_state = $wpdb->get_var('SELECT last_updated FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_batch_size = $wpdb->get_var('SELECT batch_size FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_worker = $wpdb->get_var('SELECT phpworker FROM ' . $wpdb->prefix . 'bojett_credentials');
                $from_new = $from + ($get_batch_size * $get_worker);
                $to_new = $from_new + $get_batch_size;
                $wpdb->update(
                    $table_name,
                    array(
                        'from' => $from_new,
                        'to' => $to_new,
                        'last_update' => time()
                    ),
                    array( 'name' => $import_variable ),
                    array(
                        '%d',
                        '%d',
                        '%d'
                    ),
                    array( '%s' )
                );
            if($get_import_state != 'ABORTED') {
                add_action( $import_variable, 'import_cws_product', 5, 3 );
                wp_schedule_single_event( $timestamp, $import_variable, array( $from_new, $to_new, $import_variable ) );
                error_log('1 ' . date('d.m.Y H:i:s') . " || " . $existcheck[1] . " - " . $import_variable . " closed and restarted after new product \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive.log');

            } else {
                $table_name = $wpdb->prefix . "bojett_import_worker";
                $wpdb->query("DELETE FROM $table_name WHERE `name` = '$import_variable'");
                error_log('1 ' . date('d.m.Y H:i:s') . " ||  - " . $import_variable . " closed and failed restart -- Productarray number " . $i . " / " . $from . " / " . $to . " \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
            }
        }
    }
}