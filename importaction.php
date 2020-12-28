<?php
/* Get all products from CWS importfile json encoded. */
if ( !function_exists( 'inital_puller' ) ) {
    function inital_puller($token, $resulti)
    {
        $result = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
        return $result;
    }
}
/* cURL pull via guzzle incl. valid bearertoken as attribute. */
if ( !function_exists( 'guzzle_get' ) ) {
    function guzzle_get( $uri, $bearer_token ) {
        try {
            $client = new GuzzleHttp\Client( ['defaults' => [ 'exceptions' => false ] ] );
            $headers = [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Accept' => 'application/json',
            ];
            if ( $bearer_token != '' ) {
                $response = $client->request( 'GET', $uri, ['headers' => $headers, 'verify' => false, 'http_errors' => false] );
            } else {
                $response = $client->get( $uri );
            }
            if ( $response->getStatusCode( ) == 404 ) {
                error_log( $uri . " could not be found. bojettpatch/importaction.php:23" );
            } else {
                if ( $response->getBody( ) ) {
                    return $response->getBody( );
                }
            }
        } catch ( GuzzleHttp\Exception\RequestException $e ) {
            echo 'Caught response: ' . $e->getResponse( )->getStatusCode( );
            error_log( '1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " ||  - GUZZLE: Bearer is expiring! bojettpatch/importaction.php:31\n", 3, plugin_dir_path( __FILE__ ) . '/includes/passive_log.txt' );
        }
    }
}

/* Check if the CWS Bearer (code for API authentication) is valid or expired. If expired, wait 120 seconds. */
if ( !function_exists( 'check_bearer_valid' ) ) {
    function check_bearer_valid()
    {

        global $wpdb;
        $options_name = $wpdb->prefix . "bojett_credentials";
        $client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
        $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);
        if ($client_id != NULL && $client_secret != NULL) {

        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var("SELECT cws_expires_in FROM $table_name");
        $import_worker_name = $wpdb->prefix . "bojett_import_worker";
        $check_worker_name = $wpdb->get_var( 'SELECT `name` FROM ' . $import_worker_name );

        if (($current_access_bearer_expire - 15) <= current_time('timestamp') && $check_worker_name != '' ) {
            error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - cheack_bearer_valid(): Bearer is expiring! bojettpatch/importaction.php:43\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
            $expire_diff = $current_access_bearer_expire - current_time('timestamp');
            //error_log('1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " ||  - " . $import_variable . ": Bearer is expiring! :( " . $expire_diff . "s  \n", 3, ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/passive_log.txt');
            set_time_limit( 360 );
            sleep ( 30 );
            $options_name = $wpdb->prefix . "bojett_credentials";
            $client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
            $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);
            $current_timestamp = current_time('timestamp');
            if ($client_id == NULL || $client_secret == NULL || $client_id == NULL && $client_secret == NULL) {
                // Delete current bearer because no clientkeys are set
                $table_name = $wpdb->prefix . "bojett_auth_token";
                $wpdb->query("TRUNCATE TABLE $table_name");
            } else {
                $options_name = $wpdb->prefix . "bojett_credentials";
                $client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
                $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);
                $result = guzzle_get('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret);
                $new_bearer = json_decode($result, true)['access_token'];
                $new_bearer_expires = json_decode($result, true)['expires_in'];
                $new_db_expires_in = $current_timestamp + $new_bearer_expires;
                $table_name = $wpdb->prefix . "bojett_auth_token";
                $wpdb->query("TRUNCATE TABLE $table_name");
                $wpdb->insert($table_name, array(
                    'cws_expires_in' => $new_db_expires_in,
                    'cws_access_token' => $new_bearer
                ));
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - GUZZLE: Bearer was refreshed! Good to go! bojettpatch/importaction.php:71\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                //wp_schedule_single_event(current_time('timestamp') - 3595, 'import_batch', array('import_batch'));
            }
        } else if ( $current_access_bearer_expire == NULL ) {
            // first setup for the patch.
            $current_timestamp = current_time('timestamp');
            $options_name = $wpdb->prefix . "bojett_credentials";
            $client_id = $wpdb->get_var('SELECT cws_client_id FROM ' . $options_name);
            $client_secret = $wpdb->get_var('SELECT cws_client_secret FROM ' . $options_name);
            $result = guzzle_get('https://api.codeswholesale.com/oauth/token?grant_type=client_credentials&client_id=' . $client_id . '&client_secret=' . $client_secret);
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


if( !function_exists( 'get_single_product_description' ) )
{
    function get_single_product_description( $productId )
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
        $db_token = $current_access_bearer_expire;
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $db_token );
        $payload_array = json_decode($result, true)['factSheets'];
        $settings_table = $wpdb->prefix . "bojett_credentials";
        $get_defined_import_language = $wpdb->get_var("SELECT description_language FROM $settings_table");
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
        $timestamp = current_time( 'timestamp' );
        if($gameprice === '') {
            $gameprice = '0';
        }
        if($gametitle != false) {
            if($gameprice == '') {
                $gameprice = '0';
            }
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

if(! function_exists('get_single_product_categories')) {

    function get_single_product_categories($productId)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "bojett_auth_token";
        $current_access_bearer_expire = $wpdb->get_var("SELECT cws_access_token FROM $table_name");
        $db_token = $current_access_bearer_expire;
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $db_token );
        $payload_array = explode(', ', json_decode($result, true)['category']);
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
        $result = guzzle_get( 'https://api.codeswholesale.com/v2/products/' . $productId . '/description', $db_token );
        $payload_array = json_decode($result, true)['photos'];
        //var_dump($payload_array);
        $wpdb->update(
            $wpdb->prefix . 'bojett_import_worker',
            array(
                'last_update' => current_time( 'timestamp' )
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
                'last_update' => current_time( 'timestamp' )
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

//error_log('1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " || After Picture Checker Functiondeclarations. \n", 3, ABSPATH . '/wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');


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
        $result = file_get_contents(plugin_dir_path( __FILE__ ) . '/includes/current_import.txt');
        return $result;
    }
}

function check_stop_import() {
    global $wpdb;
    $is_stop_forced = $wpdb->get_var('SELECT last_updated FROM ' . $wpdb->prefix . 'bojett_credentials');
    if ($is_stop_forced == 'ABORTED') {
        $table_name = $wpdb->prefix . "bojett_import_worker";
        $wpdb->query("DELETE FROM $table_name WHERE `name` = 'import_batch'");
        $get_cred_id = $wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'bojett_credentials');
        $wpdb->update(
            $wpdb->prefix.'bojett_credentials',
            array(
                'last_updated' => '',
            ),
            array( 'id' => $get_cred_id ),
            array(
                '%s'
            ),
            array( '%d' )
        );
        wp_die();
    }
}

if(! function_exists('import_cws_product')) {
    function import_cws_product($import_variable)
    {
        global $wpdb;

        ini_set('memory_limit', '1500M');
        set_time_limit(360);
        check_stop_import( );
        //error_log('1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " || Funktion wird ausgeführt. \n", 3, ABSPATH . '/wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        require_once(ABSPATH . 'wp-load.php');
        require_once(ABSPATH . 'wp-config.php');
        include_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');

        $result = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
        $result_array = json_decode($result, true);
        $product_object = $result_array['items'];
        $loop_iterator = 0;
        foreach ($product_object as $iteration_number => $single_product) {
            check_stop_import( );
            wp_clear_scheduled_hook($import_variable, array('import_batch'));
            check_bearer_valid( );
            $result = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
            $result_array = json_decode($result, true);
            $product_object_count = count($result_array['items']);
            $get_start_productcount = $wpdb->get_var('SELECT importnumber FROM ' . $wpdb->prefix . 'bojett_credentials');
            $current_product_import_number = $get_start_productcount - $product_object_count;
            $wpdb->update(
                $wpdb->prefix . 'bojett_credentials',
                array(
                    'productarray_id' => $current_product_import_number,
                ),
                array('phpworker' => '1'),
                array(
                    '%d'
                ),
                array('%d')
            );

            $result_inner = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
            $result_array_inner = json_decode($result_inner, true);
            $product_object_inner = $result_array_inner['items'];
            $product_counter_inner = count($product_object_inner);

            $args = array($import_variable);

            if ($product_counter_inner == 0 || $product_counter_inner == 1) {
                $client_bearer_expires = $wpdb->get_var('SELECT cws_expires_in FROM wp_bojett_auth_token');
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " || Productarray is 0. Bearer time on: " . date('d.m.Y H:i:s', $client_bearer_expires) . " Abort. \n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                wp_clear_scheduled_hook($import_variable, $args);
                $to_mail = 'renewedplains@gmail.com';
                $subject = 'IMPORT BEENDET';
                $body = 'DER IMPORT WURDE SOEBEN BEENDET BEI BOJETT.COM! Soviele sind übrig in der Datei: ' . $product_counter_inner;
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($to_mail, $subject, $body, $headers);
                wp_die();
            }

            if ($loop_iterator >= 25) {
                $client_bearer_expires = $wpdb->get_var('SELECT cws_expires_in FROM wp_bojett_auth_token');
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " || Bearer time on: " . date('d.m.Y H:i:s', $client_bearer_expires) . " Aktueller Worker wurde beendet. Kennzahl 50 wurde im Counter erreicht. Es wird gewartet, bis der nächste Worker innert 5 Sekunden beginnt. \n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                /*if ( ! has_filter( 'import_batch', 'import_cws_product' ) ) {
                    add_action( 'import_batch', 'import_cws_product', 1, 1 );
                }*/
                check_stop_import( );
                wp_schedule_single_event( time( ) + 10 , $import_variable, $args);
                $complete_productfile = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
                $product_complete = json_decode($complete_productfile, true);
                array_shift($product_complete['items']);
                $result_inner = json_encode($product_complete);
                file_put_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt', $result_inner);

                wp_die();
            }
            // wp_schedule_single_event( current_time( 'timestamp' ) - 3590, 'import_batch', $args );
            // Check all images and thumbnails from  products via API.
            $product_thumbs = $single_product['images'];
            foreach ($product_thumbs as $product_thumb) {
                if ($product_thumb['format'] == 'MEDIUM') {
                    $productthumb = $product_thumb['image'];
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


            $cws_productid = $single_product['productId']; // gets cws product id e.g. 04a8137c-0de9-42d4-8959-f15ca2567862
            $productpicture = $thumb; // will return a single string for main productimage e.g. https://api.codeswholesale.com/v1/products/f62cab33-27ec-4c3d-a0ea-b3c7925c7fbf/image?format=MEDIUM
            $catalognumber = $single_product['identifier']; // gets cws product SKU as String e.g. "MMCOHEU"
            $platform = $single_product['platform'];
            $regions = $single_product['regions'];
            if (count($regions) > 1) {
                $region = implode(", ", $regions);
            } else {
                $region = $regions[0];
            }
            $languages = $single_product['languages'];
            if (count($languages) > 1) {
                $language = implode(", ", $languages);
            } else {
                $language = $languages[0];
            }
            $producttitle = $single_product['name'];
            if ($producttitle == '' || $producttitle == ' ') {
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Product import cancel. Producttitle don't exist. ((" . $producttitle . ")) bojettpatch/importaction.php:391\n", 3, ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/passive_log.txt');
                $complete_productfile = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
                $product_complete = json_decode($complete_productfile, true);
                array_shift($product_complete['items']);
                $result_inner = json_encode($product_complete);
                $count_productfile = count($product_complete['items']);
                file_put_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt', $result_inner);
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Garbaged., error no title found... ((" . $count_productfile . ")) ((" . $producttitle . ")) bojettpatch/importaction.php:680\n", 3, ABSPATH . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/includes/passive_log.txt');
                $loop_iterator++;
                continue;
            }
            $productdescription = get_single_product_description($cws_productid);
            $productcategories = get_single_product_categories($cws_productid);
            $productphotos = get_single_product_screenshots($cws_productid);
            $cws_productprice = $single_product['prices'][0]['value'];
            $cws_quantity = $single_product['quantity'];
            $existcheck = get_wc_products_where_custom_field_is_set('_codeswholesale_product_id', $cws_productid);
            update_worker($cws_productid, $producttitle, $cws_productprice, $import_variable, __("Product data are read in.", "codeswholesale_patch"));

            if ($existcheck[0] >= 1) {
                // Product exists
                $main_currency = $wpdb->get_var('SELECT product_currency FROM ' . $wpdb->prefix . 'bojett_credentials');
                $get_currency_value = $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = "' . $main_currency . '"');
                $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM ' . $wpdb->prefix . 'bojett_credentials');
                if (substr($profit_margin_value, -1, 1) == 'a') {
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
                if ($cws_quantity == 0) {
                    $out_of_stock_staus = 'outofstock';
                    update_post_meta($existcheck[1], '_stock_status', wc_clean($out_of_stock_staus));
                    wp_set_post_terms($existcheck[1], 'outofstock', 'product_visibility', true);
                }
                update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product price is adjusted for currency and profit", "codeswholesale_patch"));
                //error_log('1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " ||  - " . $import_variable . " included wp-cron successfully after updating Product \n", 3, 'wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');

                $wpdb->update(
                    $wpdb->prefix . 'bojett_import_worker',
                    array(
                        'last_product' => $iteration_number,
                        'last_update' => current_time('timestamp')
                    ),
                    array('name' => $import_variable),
                    array(
                        '%d',
                        '%d'
                    ),
                    array('%s')
                );
                update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product was successfully updated (price and stock)", "codeswholesale_patch"));
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Product update. ((" . $producttitle . ")) bojettpatch/importaction.php:452\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                //error_log('1 ' . date( 'd.m.Y H:i:s', current_time( 'timestamp' ) ) . " || Produkt wurde hinzugefügt. \n", 3, ABSPATH . '/wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/passive_log.txt');
                $complete_productfile = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
                $product_complete = json_decode($complete_productfile, true);
                array_shift($product_complete['items']);
                $result_inner = json_encode($product_complete);
                $count_productfile = count($product_complete['items']);
                $client_bearer_expires = $wpdb->get_var('SELECT cws_expires_in FROM wp_bojett_auth_token');
                file_put_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt', $result_inner);
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Garbaged. ((" . $count_productfile . ")) ((" . $producttitle . ")) Bearer time on: " . date('d.m.Y H:i:s', $client_bearer_expires) . " bojettpatch/importaction.php:680\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                /*if ( ! has_filter( 'import_batch', 'import_cws_product' ) ) {
                    add_action( 'import_batch', 'import_cws_product', 1, 1 );
                }*/
                wp_schedule_single_event(current_time('timestamp') - 3585, 'import_batch', array('import_batch'));
                $loop_iterator++;
                continue;
            } else {
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
                    $producttitle_set = $single_product['name'];
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
                $get_currency_value = $wpdb->get_var('SELECT `value` FROM ' . $wpdb->prefix . 'bojett_currency_rates WHERE `name` = ' . $main_currency);
                $post_id = wp_insert_post($post_pro);
                $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM ' . $wpdb->prefix . 'bojett_credentials');
                if (substr($profit_margin_value, -1, 1) == 'a') {
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
                set_time_limit(360);
                update_worker($cws_productid, false, $cws_productprice, $import_variable, __("Product was successfully added (" . $post_id . ")", "codeswholesale_patch"));
                /**
                 * Attach images to product (feature/ gallery)
                 */


                attach_product_thumbnail($post_id, $productpicture, 0, '');
                if (is_array($productphotos)) {
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

                /*global $timestamp_start;

                $importtime[$i] = current_time( 'timestamp' );
                $stamp = date('d.m.Y - H:i:s', $importtime[$i]);
                $twiggle = $i - 1;
                $time_for_product = $importtime[$i] - $importtime[$twiggle];
                $time_for_product_s = $importtime[$i] - $timestamp_start;
    */

                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'bojett_import_worker',
                    array(
                        'last_product' => $iteration_number,
                        'last_update' => current_time('timestamp')
                    ),
                    array('name' => 'import_batch'),
                    array(
                        '%d',
                        '%d',
                    ),
                    array('%s')
                );
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Product inserted. ((" . $producttitle . ")) bojettpatch/importaction.php:755\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');

                $client_bearer_expires = $wpdb->get_var('SELECT cws_expires_in FROM `wp_bojett_auth_token`');
                $complete_productfile = file_get_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt');
                $product_complete = json_decode($complete_productfile, true);
                array_shift($product_complete['items']);
                $result_inner = json_encode($product_complete);
                $count_productfile = count($product_complete['items']);
                file_put_contents(plugin_dir_path(__FILE__) . '/includes/current_import.txt', $result_inner);
                error_log('1 ' . date('d.m.Y H:i:s', current_time('timestamp')) . " ||  - Bearer time on: " . date('d.m.Y H:i:s', $client_bearer_expires) . " Garbaged. ((" . $count_productfile . ")) ((" . $producttitle . ")) bojettpatch/importaction.php:680\n", 3, plugin_dir_path(__FILE__) . '/includes/passive_log.txt');
                $loop_iterator++;
                /*if ( ! has_filter( 'import_batch', 'import_cws_product' ) ) {
                    add_action( 'import_batch', 'import_cws_product', 1, 1 );
                }*/
                wp_schedule_single_event(current_time('timestamp'), 'import_batch', array('import_batch'));
                continue;
            }
        }
    }
}