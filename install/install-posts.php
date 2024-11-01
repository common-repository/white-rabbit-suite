<?php
/**
 * Created by PhpStorm.
 * User: Enterprise
 * Date: 09/04/2016
 * Time: 02:31
 */

/**
 * Multisite supports for every plugin's hooks
 */

function whiterabbitRegistrationInstall() {
    if (is_multisite()) {
        $sites = get_sites();
        $original_site_id = get_current_blog_id();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            // foreach blog
            whiterabbitInstall();
        }
        switch_to_blog($original_site_id);
    }else{
        whiterabbitInstall();
    }
}

function whiterabbitRegistrationUninstall() {
    if (is_multisite()) {
        $sites = get_sites();
        $original_site_id = get_current_blog_id();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            // foreach blog
            whiterabbitUninstall();
        }
        switch_to_blog($original_site_id);
    }else{
        whiterabbitUninstall();
    }
}

function whiterabbitRegistrationDeactivate() {
    if (is_multisite()) {
        $sites = get_sites();
        $original_site_id = get_current_blog_id();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            // foreach blog
            whiterabbitDeactivate();
        }
        switch_to_blog($original_site_id);
    }else{
        whiterabbitDeactivate();
    }
}

function whiterabbitRegistrationUpdate($upgrader_object, $options) {
    if (is_multisite()) {
        $sites = get_sites();
        $original_site_id = get_current_blog_id();
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            // foreach blog
            whiterabbitUpdate($upgrader_object, $options);
        }
        switch_to_blog($original_site_id);
    }else{
        whiterabbitUpdate($upgrader_object, $options);
    }
}

function whiterabbitRegistrationCreate($site) {
    // Makes sure the plugin is defined before trying to use it
    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }

    //replace with your base plugin path E.g. dirname/filename.php
    if ( is_plugin_active_for_network( WHITERABBIT_FILE_PATH ) ) {
        switch_to_blog($site->blog_id);
        whiterabbitInstall();
        restore_current_blog();
    }
}

function whiterabbitInstall () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'whiterabbit_posts';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      whiterabbit_post_id int(10) unsigned NOT NULL auto_increment,
      website_id int(11) NOT NULL default '0',
      post_code VARCHAR(30) NOT NULL,
      post_language VARCHAR(2) NULL,
      post_title VARCHAR(250) NULL,
      post_abstract TEXT NULL,
      post_content TEXT NULL,
      post_meta_title TEXT NULL,
      post_meta_description TEXT NULL,
      post_meta_keywords TEXT NULL,
      post_tags VARCHAR(300) NULL,
      date_start DATETIME NULL,
      date_end DATETIME NULL,
      created_at datetime NULL,
      updated_at datetime NULL,
      PRIMARY KEY(`whiterabbit_post_id`),
      UNIQUE KEY `pk_whiterabbit_posts` (`website_id`,`whiterabbit_post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


    if (!function_exists('wp_install')) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    }

    dbDelta( $sql );

    $table_name = $wpdb->prefix . 'whiterabbit_import_data';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL DEFAULT '0',
          `user_id` int(11) NOT NULL DEFAULT '0',
          `date_import` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY(`id`)     
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    dbDelta( $sql );

    $table_name = $wpdb->prefix . 'whiterabbit_abandoned_carts';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      `whiterabbit_abandoned_cart_id` int(10) unsigned NOT NULL auto_increment,
      `user_id` int(11) NOT NULL,
      `abandoned_cart_info` TEXT NOT NULL,
      `abandoned_cart_time` int(11) NOT NULL,
      `cart_closed` enum('0','1') NOT NULL DEFAULT '0',
      `detail` tinytext NULL,
      PRIMARY KEY(`whiterabbit_abandoned_cart_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    dbDelta( $sql );

    add_option("wr_general_enabled", true);
    add_option("wr_general_debug", false);
    add_option("wr_general_apipath", "https://suite.whiterabbitsuite.com");
    add_option("wr_general_site_token", "");
    add_option("wr_general_enable_gdpr_marketing", true);
    add_option("wr_general_ticket", true);
    add_option("wr_general_consumer_key", "");
    add_option("wr_general_consumer_secret", "");
    add_option("wr_general_abandoned_cart_expiry", 90);


    add_option("wr_plugin_wordpress_rabbit_id", "");
    add_option("wr_plugin_wordpress_site_id", "");
    add_option("wr_plugin_wordpress_wr_token", "");
    add_option("wr_plugin_wordpress_script_url", "");
    add_option("wr_plugin_wordpress_script_token", "");
    add_option("wr_plugin_wordpress_suite_operation_token", "");
    //add_option("wr_plugin_wordpress_mage_token", "");
    add_option("wr_plugin_wordpress_message_log", "");
    add_option("wr_plugin_wordpress_suite_gdpr_list", [__("No Setting !",'white-rabbit-suite')]);

    add_option("wr_plugin_wordpress_analytics_piwik_enabled", true);
    add_option("wr_plugin_wordpress_analytics_piwik_debug_enabled", false);
    add_option("wr_plugin_wordpress_analytics_piwik_code", "");
    add_option("wr_plugin_wordpress_analytics_piwik_api_code", "");
    add_option("wr_plugin_wordpress_analytics_ecommerce_goal_id", "");
    add_option("wr_plugin_wordpress_data_import", "");
    add_option("wr_plugin_wordpress_data_import_run", "");
    add_option("wr_plugin_wordpress_generate_sitemap", "");
    add_option("wr_plugin_log_enabled", false);

    add_option("wr_commonpages", array(
        "home" => "",
        "searchresult" => ""
    ));

    add_option( 'Activated_Plugin', 'white-rabbit-suite' );

//Now add some data to the table:
//    $field_content = 'this is a test note';

//    $wpdb->insert(
//        $table_name,
//        array(
//            'time' => current_time('mysql'),
//            'content' => $field_content,
//        )
//    );



}//end install_tbl function

function load_plugin() {
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'white-rabbit-suite' ) {

        delete_option( 'Activated_Plugin' );

        $html = '<div class="updated">';
        $html .= '<p>';
        $html .= __( 'The Whiterabbit Plugin has succesfully installed! Go to ','white-rabbit-suite') . '<a href="'.admin_url('admin.php?page=wr-mainmenu').'">'. __('this page','white-rabbit-suite') . '</a>' . __(' for complete registration on Whiterabbit Suite.', 'white-rabbit-suite' );
        $html .= '</p>';
        $html .= '</div><!-- /.updated -->';
        echo $html;
    }
}
add_action( 'admin_init', 'load_plugin' );




//Start Get Robots.txt function
function whiterabbit_get_robots()
{
    if (file_exists(ABSPATH . "robots.txt")) {
        return false;
    }
    $robots = Null;
    $robots .= 'User-agent: *' . "\n" .
        'Disallow: /wp-admin/' . "\n" .
        'Allow: /wp-admin/admin-ajax.php' . "\n" .
        'Disallow: /wp-includes/' . "\n" .
        'Allow: /wp-includes/js/' . "\n" .
        'Allow: /wp-includes/images/' . "\n" .
        'Disallow: /trackback/' . "\n" .
        'Disallow: /wp-login.php' . "\n" .
        'Disallow: /wp-register.php' . "\n" ;
        //'Sitemap: http://wordpress2.enterprise-consulting.ovh/sitemap.xml';
    $fp = fopen(ABSPATH . "robots.txt", 'w');
    fwrite($fp, $robots);
    fclose($fp);
    return true;
}//end Get Robots.txt function


function whiterabbitDeactivate() {

}

function whiterabbitUpdate( $upgrader_object, $options ) {

    global $wpdb;
    $table_name = $wpdb->prefix . 'whiterabbit_abandoned_carts';
    $version = '2.7';

    //check if our plugin version is major/equal to 2.7
    if (version_compare(WHITERABBIT_PLUGIN_VERSION, $version, "<=")) {
        if ($options['action'] == 'update' && $options['type'] == 'plugin') {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin == WHITERABBIT_FILE_PATH) {
                    // we are in our plugin
                    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                    $main_sql_create = "CREATE TABLE IF NOT EXISTS $table_name (
                              `whiterabbit_abandoned_cart_id` int(10) unsigned NOT NULL auto_increment,
                              `user_id` int(11) NOT NULL,
                              `abandoned_cart_info` TEXT NOT NULL,
                              `abandoned_cart_time` int(11) NOT NULL,
                              `cart_closed` enum('0','1') NOT NULL DEFAULT '0',
                              `detail` tinytext NULL,
                              PRIMARY KEY(`whiterabbit_abandoned_cart_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

                    maybe_create_table( $table_name, $main_sql_create );
                }
            }
        }
    }
}


function whiterabbitUninstall() {

    global $wpdb;
    $table = $wpdb->prefix."whiterabbit_posts";

    //Delete any options thats stored whiterabbit

    delete_option('wr_general_enabled');
    delete_option('wr_general_debug');
    delete_option('wr_general_apipath');
    delete_option('wr_general_ticket');
    delete_option('wr_general_abandoned_cart_expiry');
    delete_option('wr_plugin_wordpress_rabbit_id');
    delete_option('wr_plugin_wordpress_site_id');
    delete_option('wr_plugin_wordpress_wr_token');
    delete_option('wr_plugin_wordpress_suite_operation_token');
    delete_option('wr_general_site_token');
    delete_option('wr_general_enable_gdpr_marketing');
    delete_option('wr_plugin_wordpress_suite_gdpr_list');
    delete_option('wr_plugin_wordpress_message_log');
    //delete_option('wr_plugin_wordpress_mage_token');
    delete_option('wr_plugin_wordpress_analytics_piwik_enabled');
    delete_option('wr_plugin_wordpress_analytics_piwik_debug_enabled');
    delete_option('wr_plugin_wordpress_analytics_piwik_api_code');
    delete_option('wr_plugin_wordpress_analytics_piwik_code');
    delete_option('wr_plugin_wordpress_analytics_ecommerce_goal_id');
    delete_option('wr_whiterabbit_analytics_newsletter_goal_id');
    delete_option('wr_whiterabbit_piwik_url');
    delete_option('wr_plugin_wordpress_script_url');
    delete_option('wr_plugin_wordpress_script_token');
    delete_option('wr_plugin_wordpress_data_import_run');
    delete_option('wr_plugin_wordpress_generate_sitemap');
    delete_option('wr_plugin_wordpress_data_import');
    delete_option('wr_general_consumer_key');
    delete_option('wr_general_consumer_secret');
    delete_option('wr_plugin_log_enabled');

    delete_option('wr_commonpages');
    delete_option('wr_whiterabbit_wp_token');


    $wpdb->query("DROP TABLE IF EXISTS $table");
    $table = $wpdb->prefix."whiterabbit_import_data";
    $wpdb->query("DROP TABLE IF EXISTS $table");
    $table = $wpdb->prefix."whiterabbit_abandoned_carts";
    $wpdb->query("DROP TABLE IF EXISTS $table");

    //disable crons
    wp_unschedule_hook('wr_execute_cron');
    wp_unschedule_hook('wr_remove_abandoned_cart');

}//end pluginUninstall function

