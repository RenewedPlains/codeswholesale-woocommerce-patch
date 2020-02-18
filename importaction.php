<?php
function inital_puller($token, $resulti) {
    $result = file_get_contents('../wp-content/plugins/' . dirname( plugin_basename( __FILE__ ) ) . '/includes/current_import.txt');
    return $result;
}

if($_GET['importstart'] == 'true') {
    set_time_limit(120);
    ini_set('memory_limit', '512M');
// Ignoriere Abbruch durch den Benutzer und erlaube dem Skript weiterzulaufen
    /*
    if(connection_aborted()){
        echo 'die  Zeit  ist um ';
    }
    function shutdown()
    {
        global $timestamp_start;
        // Das ist unsere Shutdown Funktion, in welcher
        // wir noch letzte Anweisungen ausführen können
        // bevor die Ausführung beendet wird.
        $timestamp_end = time();
        $seconds_to_import = $timestamp_end - $timestamp_start;
        $minute_to_import = $seconds_to_import / 60;
        $hours_to_import = $minute_to_import / 60;

    }

    register_shutdown_function('shutdown');
    //exit();
    */


}

function import_cws_product($from, $to, $import_variable) {
    set_time_limit(60);
    ini_set('memory_limit', '512M');
    //ignore_user_abort(true);
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    require_once( ABSPATH . 'wp-load.php' );
    require_once( ABSPATH . 'wp-config.php' );

    function find_empty_games( $cars, $position ) {
        foreach($cars as $index => $car) {
            if($car['name'] == $position) return $index;
        }
        return FALSE;
    }

function get_single_product( $productId )
{
    global $wpdb;

    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;

    $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
    $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    curl_close($ch); // Close the cURL connection

    return json_decode($result, true);
}

function get_single_product_description( $productId )
{
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;

    $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
    $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    $payload_array = json_decode($result, true)['factSheets'];
    $settings_table = $wpdb->prefix . "bojett_credentials";
    $get_defined_import_language = $wpdb->get_var( "SELECT description_language FROM $settings_table" );
    curl_close($ch); // Close the cURL connection

    if(is_array($payload_array)) {
        foreach ($payload_array as $product_description) {
            if ($product_description['territory'] == $get_defined_import_language && $product_description['description'] != '') {
                return $product_description['description'];
            }
        }
    } else {
        return false;
    }
}

function checktitle( $fix_title, $productId, $db_token )
{
    if ($fix_title == '') {
        $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId); // Initialise cURL
        $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement
        $thetitle = json_decode($result, true)['name'];
        curl_close($ch); // Close the cURL connection
        return $thetitle;
    }
}

function get_single_product_title( $productId )
{
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;

    $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId); // Initialise cURL
    $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    $thetitle = json_decode($result, true)['name'];
    curl_close($ch); // Close the cURL connection
    return $thetitle;

}

function get_single_product_categories( $productId )
{
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;
    $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
    $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    $payload_array = explode(', ', json_decode($result, true)['category']);
    curl_close($ch); // Close the cURL connection
    return $payload_array;
}

function get_single_product_screenshots( $productId )
{
    global $wpdb;
    $table_name = $wpdb->prefix . "bojett_auth_token";
    $current_access_bearer = $wpdb->get_var( "SELECT cws_expires_in FROM $table_name" );
    $current_access_bearer_expire = $wpdb->get_var( "SELECT cws_access_token FROM $table_name" );
    $db_token = $current_access_bearer_expire;

    $ch = curl_init('https://api.codeswholesale.com/v2/products/' . $productId . '/description'); // Initialise cURL
    $authorization = "Authorization: Bearer " . $db_token; // Prepare the authorisation token
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
    $result = curl_exec($ch); // Execute the cURL statement
    $payload_array = json_decode($result, true)['photos'];
    //var_dump($payload_array);

    $photo_array = array();
    if(is_array($payload_array)) {
        foreach ($payload_array as $product_image) {
            //echo $product_image['url'];
            $ch5 = curl_init($product_image['url']);
            curl_setopt($ch5, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch5, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch5);
            $url = curl_getinfo($ch5, CURLINFO_EFFECTIVE_URL);
            array_push($photo_array, array('url' => $url, 'content_type' => curl_getinfo($ch5, CURLINFO_CONTENT_TYPE)));
            curl_close($ch5); // Close the cURL connection
        }
        curl_close($ch); // Close the cURL connection

        return $photo_array;
    } else {
        return false;
    }
}

function attach_product_thumbnail( $post_id, $url, $flag, $extension )
{
    $image_url = $url;
    if ( strstr( $image_url, 'no-image' ) ) {
        $image_url = 'https://www.bojett.com/wp-content/uploads/2020/01/noimage.png';
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

function get_wc_products_where_custom_field_is_set($field, $value) {
    $products = wc_get_products(array('status' => 'publish',
        'meta_key' => $field,
        'meta_value' => $value, //'meta_value' => array('yes'),
        'meta_compare' => 'IN')); //'meta_compare' => 'NOT IN'));
    foreach($products as $product) {
        $existing_pid = $product->get_id();
        return array(count($products), $existing_pid);
    }
}

function inital_pull($token, $resulti) {
    if($resulti == '') {
        $ch = curl_init('https://api.codeswholesale.com/v2/products'); // Initialise cURL
//$post = json_encode($post); // Encode the data array into a JSON string
        $authorization = "Authorization: Bearer " . $token; // Prepare the authorisation token
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization)); // Inject the token into the header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
//curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // Set the posted fields
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
        $result = curl_exec($ch); // Execute the cURL statement

        return $result;
    } else {
        return $resulti;
    }
}

    set_time_limit(60);
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
            curl_exec($ch4);
            $thumb = curl_getinfo($ch4, CURLINFO_EFFECTIVE_URL);
            curl_close($ch4);
        }
    }



// Get productdata
    $cws_productid = json_decode($result, true)['items'][$i]['productId']; // gets cws product id e.g. 04a8137c-0de9-42d4-8959-f15ca2567862
    error_log("ATTENTION, IMPORT PREPARE: " .$cws_productid);


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
    $producttitle = get_single_product_title($cws_productid); // will return producttitle in german as string

    if($producttitle == '' || $producttitle == ' ') {

        continue;
    }

    $productdescription = get_single_product_description($cws_productid); // will return description in german as rich text / markup
    $productcategories = get_single_product_categories($cws_productid); // will return an array with all english categories e.g. Array('Action', 'Indie');
    $productphotos = get_single_product_screenshots($cws_productid); // will return an array with all screenshot URLs from CWS
    $cws_productprice = json_decode($result, true)['items'][$i]['prices'][2]['value'];
    $cws_quantity = json_decode($result, true)['items'][$i]['quantity'];
    $existcheck = get_wc_products_where_custom_field_is_set('_codeswholesale_product_id', $cws_productid);
    /*$myfile = fopen(ABSPATH . "tmp/php-error.log", "a") or die("Unable to open file!");
    $txt = "Next Product to import: " . $producttitle . ' -> ' . $cws_productid;
    fwrite($myfile, "\n". $txt);
    fclose($myfile);*/

    if($existcheck[0] >= 1 ) {
        $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials');
        $setprice = $cws_productprice + $profit_margin_value;
        update_post_meta($existcheck[1], '_regular_price', $setprice);
        update_post_meta($existcheck[1], '_price', $setprice);
        update_post_meta($existcheck[1], '_codeswholesale_product_stock_price', $cws_productprice);
        wc_update_product_stock($existcheck[1], $cws_quantity, 'set');

        set_time_limit(60);
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
        if($i == ($to - 1)) {
            $timestamp = time();
            $table_name = $wpdb->prefix . "bojett_import_worker";
            wp_clear_scheduled_hook( $import_variable, array( $from, $to, $import_variable ) );
            $get_import_state = $wpdb->get_var('SELECT last_updated FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_batch_size = $wpdb->get_var('SELECT batch_size FROM ' . $wpdb->prefix . 'bojett_credentials');
            $check_number = is_numeric(substr($import_variable, -1, 1));
            if($check_number === true) {
                $from_new = 0;
                $to_new = 0;
            } else {
                $from_new = $to;
                $to_new = $to + $get_batch_size;
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
            }
            if($get_import_state != 'ABORTED') {
                add_action( $import_variable, 'import_cws_product', 1, 3 );
                wp_schedule_single_event( $timestamp + 30, $import_variable, array( "$from_new", "$to_new", $import_variable ) );
            } else {
                $table_name = $wpdb->prefix . "bojett_import_worker";
                $wpdb->query("DELETE FROM $table_name WHERE `name` = '$import_variable'");
            }
        }
        continue;
    }
    if ($productcategories[0] != "") {

        $tager = [];
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
    /*
    echo '<pre>';
    var_dump(json_decode($result,true)['items'][$current_product]);
    //get_single_product_description($cws_productid);

    //var_dump( get_single_product_screenshots('04a8137c-0de9-42d4-8959-f15ca2567862'));
    //get_single_product( '04a8137c-0de9-42d4-8959-f15ca2567862' );

    //var_dump( json_decode($result,true)['items'][8585]);
    echo '</pre>';
    //WORKS echo count(json_decode($result, true)['items']);*/
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
    $profit_margin_value = $wpdb->get_var('SELECT profit_margin_value FROM '.$wpdb->prefix.'bojett_credentials');
    $post_id = wp_insert_post($post_pro);
    $realprice = $cws_productprice + $profit_margin_value;
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
    set_time_limit(60);
    error_log("ATTENTION, IMPORT READY: " .$post_id . ' -> ' . $producttitle);
    /**
     * Attach images to product (feature/ gallery)
     */


    attach_product_thumbnail($post_id, $productpicture, 0, '');
    if(is_array($productphotos)) {
        foreach ($productphotos as $screenshots) {
            //set gallery image
            $screenshot_url = $screenshots['url'];
            $screenshot_mime = $screenshots['content_type'];


            if ($screenshot_mime == 'image/jpeg') {
                $screenshot_ext = '.jpg';

                attach_product_thumbnail($post_id, $screenshot_url, 1, $screenshot_ext);
            } else if ($screenshot_mime == 'image/png') {
                $screenshot_ext = '.png';
                attach_product_thumbnail($post_id, $screenshot_url, 1, $screenshot_ext);
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
    $table_title = $wpdb->prefix . 'codeswholesale_postback_import_details';
    $wpdb->insert($table_title, array(
        'created_at' => time(),
        'import_id' => 'ID',
        'name' => $producttitle,
        'import_time' => time(),
        'product_id' => $post_id,
    ));
        $wpdb->update(
            $wpdb->prefix.'bojett_import_worker',
            array(
                'last_product' => $i,
                'last_update' => time()
            ),
            array( 'name' => $import_variable ),
            array(
                '%d',
                '%d',
            ),
            array( '%s' )
        );
        if($i == ($to - 1)) {
            $timestamp = time();
            $table_name = $wpdb->prefix . "bojett_import_worker";
            wp_clear_scheduled_hook( $import_variable, array( $from, $to, $import_variable ) );
            $get_import_state = $wpdb->get_var('SELECT last_updated FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_batch_size = $wpdb->get_var('SELECT batch_size FROM ' . $wpdb->prefix . 'bojett_credentials');
            $get_worker = $wpdb->get_var('SELECT phpworker FROM ' . $wpdb->prefix . 'bojett_credentials');
            $check_number = is_numeric(substr($import_variable, -1, 1));
            if($check_number === true) {
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
            } else {
                $from_new = $to;
                $to_new = $to + $get_batch_size;
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
            }
            if($get_import_state != 'ABORTED') {
                add_action( $import_variable, 'import_cws_product', 1, 3 );
                wp_schedule_single_event( $timestamp + 30, $import_variable, array( "$from_new", "$to_new", $import_variable ) );
            } else {
                $table_name = $wpdb->prefix . "bojett_import_worker";
                $wpdb->query("DELETE FROM $table_name WHERE `name` = '$import_variable'");
            }
        }
    }
}