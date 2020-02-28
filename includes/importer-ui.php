<?php

if($_GET['get_as_json'] == 'true') {
    function convert_to_time_ago( $orig_time ) {
        return human_time_diff( $orig_time, current_time( 'timestamp' ) ).' '.__( 'ago' );
    }
    include_once( '../../../../wp-load.php' );
    include_once( '../../../../wp-config.php' );
    global $wpdb;


    $import_worker = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'bojett_import_worker');
    $collector = array();
    foreach($import_worker as $single_worker) {
        $this_worker = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bojett_import WHERE `cws_phpworker` = "'. $single_worker->name .'" ORDER BY `created_at` DESC LIMIT 0, 1');
        foreach($this_worker as $the_worker) {
            $collector["$single_worker->name"] = array();
            $collector["$single_worker->name"]['cws_id'] = $the_worker->cws_id;
            $collector["$single_worker->name"]['cws_game_title'] = $the_worker->cws_game_title;
            $collector["$single_worker->name"]['cws_game_price'] = $the_worker->cws_game_price;
            $collector["$single_worker->name"]['cws_phpworker'] = $the_worker->cws_phpworker;
            $collector["$single_worker->name"]['cws_last_update'] = convert_to_time_ago($the_worker->created_at);
        }
    }
    echo '<div class="metaimport">';
    echo json_encode($collector);
    echo '</div>';
} else {
    global $wpdb;
    echo '<br />';
    $import_worker = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'bojett_import_worker');
    $get_import_state = $wpdb->get_var('SELECT last_updated FROM `' . $wpdb->prefix . 'bojett_credentials`');

    if(count($import_worker) != 0) {

        if($get_import_state == 'ABORTED') {
            _e('Import is still in progress. The import is terminated at the next opportunity.', 'codeswholesale_patch');
        } else {
            _e('Import is in progress.', 'codeswholesale_patch');
        }
        echo '<br /><br /><div class="wp-list-table widefat plugin-install">
                <h2 class="screen-reader-text">Pluginliste</h2>	<div id="the-list">';
        foreach($import_worker as $worker) {
            $get_import_number = $wpdb->get_var('SELECT importnumber FROM `' . $wpdb->prefix . 'bojett_credentials`');
            $from_import_worker = $worker->from;
            $to_import_worker = $worker->to;
            $current_import_worker = $worker->last_product; ?>
            <div class="plugin-card">
                <div class="plugin-card-top">
                    <div class="column-name">
                        <h3>
                            <?php $worker_number = substr($worker->name, -1, 1); ?>
                            Import Worker #<?php echo $worker_number + 1; ?>
                        </h3>
                    </div>
                    <div class="action-links">
                        <ul class="plugin-action-buttons">
                            <li>
                                <a href="#" class="button disabled" aria-label="Aktiviere Akismet Anti-Spam"><?php _e('No import active', 'codeswholesale_patch'); ?></a>
                            </li>
                        </ul>
                    </div>
                    <div class="column-description">
                        <p><strong><?php _e('Import Title', 'codeswholesale_patch'); ?></strong> <span class="product_title"><cite>Idle - importer not active</cite></span></p>
                        <p><strong><?php _e('Import Price', 'codeswholesale_patch'); ?></strong> <span class="product_price"><cite>Idle - importer not active</cite></span></p>
                        <p><strong><?php _e('Status', 'codeswholesale_patch'); ?></strong> <span class="product_categories"><cite>Idle - importer not active</cite></span></p>
                    </div>
                </div>
                <div class="plugin-card-bottom">
                    <div class="column-updated">
                        <strong><?php _e('Last Updated:'); ?></strong>
                        <span class="timeago"><?php echo meks_convert_to_time_ago($worker->last_update); ?></span>
                    </div>
                    <div class="column-updated">
                        <strong><?php _e('Import Products', 'codeswholesale_patch'); ?></strong> <span class="from_import"><?php echo $from_import_worker; ?></span> - <span class="to_import"><?php echo $to_import_worker; ?></span> von <?php echo $get_import_number; ?>
                    </div>
                </div>
            </div>
        <?php }
        echo '</div>
            </div>';
    } else {
        _e('No import process is currently active.', 'codeswholesale_patch');
        $get_settings_import_worker = $wpdb->get_var('SELECT `phpworker` FROM `' . $wpdb->prefix .'bojett_credentials`');
        echo '<br /><br /><div class="wp-list-table widefat plugin-install">
                <h2 class="screen-reader-text">Pluginliste</h2>	<div id="the-list">';
        for($i = 1; $i <= $get_settings_import_worker; $i++) {
            ?>

                    <div class="plugin-card">
                        <div class="plugin-card-top">
                            <div class="column-name">
                                <h3>
                                    Import Worker #<?php echo $i; ?>
                                </h3>
                            </div>
                            <div class="action-links">
                                <ul class="plugin-action-buttons">
                                    <li>
                                        <a href="#" class="button disabled" aria-label="Aktiviere Akismet Anti-Spam"><?php _e('No import active', 'codeswholesale_patch'); ?></a>
                                    </li>
                                </ul>
                            </div>
                            <div class="column-description">
                                <p><strong><?php _e('Import Title', 'codeswholesale_patch'); ?></strong> <span class="product_title"><cite>Idle - importer not active</cite></span></p>
                                <p><strong><?php _e('Import Price', 'codeswholesale_patch'); ?></strong> <span class="product_price"><cite>Idle - importer not active</cite></span></p>
                                <p><strong><?php _e('Status', 'codeswholesale_patch'); ?></strong> <span class="product_categories"><cite>Idle - importer not active</cite></span></p>
                            </div>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="column-updated">
                                <strong><?php _e('Last Updated:'); ?></strong>
                                <span class="timeago"><?php echo meks_convert_to_time_ago(1582810273); ?></span>
                            </div>
                            <div class="column-updated">
                                <strong><?php _e('Import Products', 'codeswholesale_patch'); ?></strong> <span class="from_import"></span> - <span class="to_import"></span> von 14242
                            </div>
                        </div>
                    </div>
            <?php
        }
        echo '</div>
            </div>';
    }
}