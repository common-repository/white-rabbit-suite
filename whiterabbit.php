<?php
/*
Plugin Name: White Rabbit All in One Suite
Plugin URI: https://www.whiterabbitsuite.com/create-or-connect-a-wordpress-site/
Version: 3.0.0
Description: White Rabbit software enables you to implement an e-commerce in Wordpress through just one click.
Author: White Rabbit Srl
Author URI: https://www.whiterabbitsuite.com
Text Domain: white-rabbit-suite
Domain Path: /languages

Version 1.0   First release
Version 1.1   New features:
                       * Piwik integration
                       * Author and default category setting
                       * New White Rabbit menu
Version 1.2 Bug fix and performance improvements
Version 1.3 Uninstall and deactivate bug fix; new url api feature
Version 1.4 Various fixes
Version 1.5 Choosing the post status to be published from the suite to WP
             * Recovery Authors by choice in posts to be published from the suite to WP
             * Upload img to WP media
             * Ticket Creation From the contact form using WPCF7 from option
             * Landing from suites as external page
Version 1.6 Bug fix and performance improvements
Version 1.7 Bug fix and performance improvements
Version 1.8 GDPR . Bug fix and performance improvements
Version 1.9 Securety update
Version 2.0 Securety update Login
Version 2.1 fix and performance improvements
Version 2.2 Update Woocomerce 3.0
Version 2.3 New Suite Crm
Version 2.3.1 New Action Site Submitted Form
Version 2.3.2 Check if JWT lib aready exists to exclude JWT.php
Version 2.3.3 Check if JWT lib aready exists to exclude SignatureInvalidException.php
Version 2.3.4 All fields CRM from form contact 7 + user (meta dati)
Version 2.3.5 Bug fix and performance improvements + italian translation
Version 2.3.6 Bug fix and performance improvements
Version 2.3.7 Generate sitemap & robots SEO manually
Version 2.3.8 New WPForms Integration with All fields CRM
Version 2.3.9 Bug fix and performance improvements
Version 2.4 Bug fix and performance improvements
Version 2.4.1 Bug fix and performance improvements
Version 2.5 Added event management and setUserId for Woocommerce
Version 2.5.1 Bug fix and performance improvements
Version 2.5.2 Bug fix and performance improvements
Version 2.6 Scripts retrieve & matomo improvements
Version 2.6.1 Bug fix and performance improvements
Version 2.7 Import Abandoned Carts by custom core Crons
Version 2.7.1 Bug fix and performance improvements
Version 2.7.2 Backward compatibility for WP 4.9.13
Version 2.7.3 Added Custom Variable management from contact form 7
Version 2.7.4 Bug fix and performance improvements
Version 2.8 Added support for multisite network
Version 2.8.1 Added change order status bulk action
Version 2.8.2 Added tags coupon to change order status
Version 2.8.3 Added gdpr purpose to Woocommerce registration custom fields
Version 2.8.4 Bug fix and performance improvements
Version 2.8.5 Bug fix and performance improvements
Version 3.0.0 New Release update
*/

define("WHITERABBIT_PATH", plugin_dir_path(__FILE__));
define("WHITERABBIT_FILE_PATH", plugin_basename(__FILE__));
define('WHITERABBIT_CURRENT_TIME', current_time( 'U' ) );
define('WHITERABBIT_TIMEZONE', (get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : date_default_timezone_get() ));

if( ! function_exists('get_plugin_data') ){
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
$plugin_data = get_plugin_data( __FILE__ );
define("WHITERABBIT_PLUGIN_VERSION", $plugin_data['Version']);

// Inizializzazione

if (!function_exists('whiterabbitInstall')) {
    require_once("install/install-posts.php");
}


if (!class_exists('whiterabbit_client')) {
    require_once("connector/client.php");
}
if (!class_exists('wrConnector')) {
    require_once("connector/connector.php");
}

if (!class_exists('wrCron')) {
    require_once("connector/cron.php");
}

//call install_tbl function when plugin is activated by admin:
register_activation_hook(__FILE__, 'whiterabbitRegistrationInstall');


//hook into WordPress when its being deactivated:
register_deactivation_hook(__FILE__, 'whiterabbitRegistrationDeactivate');


//hook into WordPress when its being unistall:
register_uninstall_hook(__FILE__, 'whiterabbitRegistrationUninstall');

//hook into Wordpress when its upgraded plugin
add_action( 'upgrader_process_complete', 'whiterabbitRegistrationUpdate',10, 2);

//hook into Wordpress Multisite network creation subsite
add_action( 'wp_initialize_site', 'whiterabbitRegistrationCreate', 10, 2);

function whiterabbit_start()
{

    load_plugin_textdomain('white-rabbit-suite', false, basename( dirname( __FILE__ ) ) . '/languages/');

    if (is_admin()) {
        require 'class-admin.php';
    } else {
        require 'class-frontend.php';
    }

}


add_action('init', 'whiterabbit_start');

// add_filter('query_vars', 'custom_query_vars');
add_action('plugins_loaded', array(new whiterabbit_client(), 'init'));
add_action('parse_request', array(new whiterabbit_client(), 'whiterabbit_api_request'));

//WP Cron
//add_filter( 'cron_schedules', array( new wrCron(), 'add_cron_schedule' ) );
if ( ! wp_next_scheduled( 'wr_execute_cron' ) ) {
    wp_schedule_event( time(), 'hourly', 'wr_execute_cron' );
}
add_action( 'wr_execute_cron', array( new wrCron(), 'wr_execute_cron' ) );
if ( ! wp_next_scheduled( 'wr_remove_abandoned_cart' ) ) {
    wp_schedule_event( time(), 'daily', 'wr_remove_abandoned_cart' );
}
add_action( 'wr_remove_abandoned_cart', array( new wrCron(), 'wr_remove_abandoned_cart' ) );

//woocommerce user register
add_action('woocommerce_checkout_order_processed', array(new wrConnector(), 'whiterabbit_crm_insert_user_from_order'));//Insert Registration User into CRM

//Wordpress user register
add_action('user_register', array(new wrConnector(), 'whiterabbit_crm_insert_user'));//Insert Registration User into CRM

//WPCF7 cotact form
add_action('wpcf7_before_send_mail', array(new wrConnector(), 'whiterabbit_crm_insert_user_newsletter'));//Insert Registration User into CRM

//WPForm integration
add_action('wpforms_process_complete', array(new wrConnector(), 'whiterabbit_crm_insert_user_newsletter_form'), 10, 4);//Insert Registration User into CRM
add_filter('wpforms_builder_settings_sections', 'whiterabbit_wpforms_settings_section', 20, 2);//Title of Backend Setting Panel
add_filter('wpforms_form_settings_panel_content', 'whiterabbit_wpforms_settings_section_content', 20);//Fields for Settings Panel
add_filter( 'wpforms_address_schemes', array(new wrConnector(),'whiterabbit_new_address_scheme') ); //add schemes for provinces italians
add_filter( 'wpforms_datetime_date_formats', array(new wrConnector(),'whiterabbit_date_field_formats') ); //add format date for Suite to Work
add_action( 'wpforms_wp_footer_end', 'whiterabbit_track_email', 30 ); //set userid piwik tracking by email

//CRM ORDINI
//if (is_plugin_active("woocommerce/woocommerce.php")) {
    add_action('woocommerce_order_status_changed', array(new wrConnector(), 'whiterabbit_crm_update_order'), 99, 3);
//}

//ABANDONED CART
add_action( 'woocommerce_add_to_cart', array( new wrConnector(), 'whiterabbit_save_abandoned_cart' ), 99);
add_action( 'woocommerce_cart_item_removed', array( new wrConnector(), 'whiterabbit_save_abandoned_cart' ), 99);
add_action( 'woocommerce_cart_item_restored', array( new wrConnector(), 'whiterabbit_save_abandoned_cart' ), 99);
add_action( 'woocommerce_after_cart_item_quantity_update', array( new wrConnector(), 'whiterabbit_save_abandoned_cart' ), 99);
add_action( 'woocommerce_calculate_totals', array( new wrConnector(), 'whiterabbit_save_abandoned_cart' ), 99);
add_action( 'woocommerce_checkout_update_order_meta', array( new wrConnector(), 'whiterabbit_remove_abandoned_cart' ) );
add_action( 'woocommerce_thankyou', array( new wrConnector(), 'whiterabbit_remove_abandoned_cart' ) );

//bulk action change order status
add_filter( 'handle_bulk_actions-edit-shop_order', array( new wrConnector(), 'whiterabbit_crm_update_order_bulk_action_edit_shop_order' ), 10, 3 );

//CURL set
add_action('http_api_curl', 'whiterabbit_http_api_curl');

// One time activation functions
register_activation_hook(WHITERABBIT_PATH, array(new whiterabbit_client(), 'flush_rules'));


function custom_query_vars($vars)
{
    // $vars[] = 'wr_action';
    // foreach($vars as $var) { echo $var . "<br/>\n"; }
    return $vars;
}


// add_action( 'wp_router_generate_routes', 'whiterabbit_wr_add_routes', 20 );


function whiterabbit_add_routes($router)
{
    $route_args = array(
        'path' => 'wr/api/',
        'query_vars' => array(),
        'page_callback' => 'whiterabbit_api_route_callback',
        'page_arguments' => array(),
        'access_callback' => true,
        'title' => esc_html__('Whiterabbit Api','white-rabbit-suite'),
        'template' => array(
            'page.php',
        )
    );

    $router->add_route('wr-post-route-id', $route_args);

    $route_args = array(
        'path' => 'wr/post/',
        'query_vars' => array(),
        'page_callback' => 'whiterabbit_post_route_callback',
        'page_arguments' => array(),
        'access_callback' => true,
        'title' => esc_html__('Whiterabbit post','white-rabbit-suite'),
        'template' => array(
            'page.php',
        )
    );

    $router->add_route('wr-post-route-id', $route_args);

    $route_args = array(
        'path' => 'wr/blog/',
        'query_vars' => array(),
        'page_callback' => 'whiterabbit_blog_route_callback',
        'page_arguments' => array(),
        'access_callback' => true,
        'title' => esc_html__('Whiterabbit blog','white-rabbit-suite'),
        'template' => array(
            'page.php',
        )
    );

    $router->add_route('wr-blog-route-id', $route_args);
}

function whiterabbit_api_route_callback()
{
    return "Congrats! Your demo callback is fully functional. Now make it do something fancy";
}

function whiterabbit_post_route_callback()
{
    return "Congrats! Your demo callback is fully functional. Now make it do something fancy";
}

function whiterabbit_blog_route_callback()
{
    return "Congrats! Your demo callback is fully functional. Now make it do something fancy";
}


remove_action('welcome_panel', 'wp_welcome_panel'); //remove default welcome panel
add_action('welcome_panel', 'whiterabbit_welcome_panel'); //add white rabbit welcome pannel content
add_action('wp_dashboard_setup', 'whiterabbit_dashboard_widget'); // add wr widget dashboard management



//Comment to allow uninstall plugin
//add_filter('plugin_action_links', 'whiterabbit_remove_deactivation', 10, 4); //remove deactivate action to wr plugin
//add_filter('bulk_actions-plugins', 'whiterabbit_bulk_actions'); //remove deactivate and delete plugins

function whiterabbit_remove_deactivation($actions, $plugin_file)
{

    static $plugin;

    if (!isset($plugin))
        $plugin = plugin_basename(__FILE__);
    if ($plugin == $plugin_file) {

        unset($actions['deactivate']);
    }
    return $actions;

}

function whiterabbit_bulk_actions($actions)
{

    unset($actions['deactivate-selected']);
    unset($actions['delete-selected']);
    return $actions;
}

function whiterabbit_welcome_panel()
{

    wp_register_style('admin-dashboard', plugin_dir_url(__FILE__) . 'css/admin-dashboard.css');
    wp_enqueue_style('admin-dashboard');

    ?>
    <div class="welcome-panel-content">
        <?= '<img class="wr-logo" src="' . esc_url(plugins_url('images/white-rabbit-logo100x66.jpg', __FILE__)) . '" > '; ?>
        <h2><?php esc_html_e('Welcome to White Rabbit WordPress!','white-rabbit-suite'); ?></h2>
        <p class="about-description"><?php esc_html_e("We've assembled some links to get you started:",'white-rabbit-suite'); ?></p>
        <div class="welcome-panel-column-container">
            <div class="welcome-panel-column">
                <?php if (current_user_can('install_themes') || (current_user_can('switch_themes') && count(wp_get_themes(array('allowed' => true))) > 1)) : ?>
                    <h3><?php esc_html_e('Get Started','white-rabbit-suite'); ?></h3>
                    <a class="button button-primary button-hero"
                       href="<?= admin_url('themes.php') ?>"><?php esc_html_e('Add New Theme','white-rabbit-suite'); ?></a>
                <?php endif; ?>
                <ul>
                    <li><?php printf('<a href="%s" class="welcome-icon welcome-view-site">' . esc_html__('View your site','white-rabbit-suite') . '</a>', esc_url(home_url('/'))); ?></li>
                </ul>

            </div>
            <div class="welcome-panel-column">
                <h3><?php esc_html_e('Next Steps','white-rabbit-suite'); ?></h3>
                <ul>
                    <?php if ('page' == get_option('show_on_front') && !get_option('page_for_posts')) : ?>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-edit-page">' . esc_html__('Edit your front page','white-rabbit-suite') . '</a>', get_edit_post_link(get_option('page_on_front'))); ?></li>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-add-page">' . esc_html__('Add additional pages','white-rabbit-suite') . '</a>', esc_url(admin_url('post-new.php?post_type=page'))); ?></li>
                    <?php elseif ('page' == get_option('show_on_front')) : ?>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-edit-page">' . esc_html__('Edit your front page','white-rabbit-suite') . '</a>', get_edit_post_link(get_option('page_on_front'))); ?></li>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-add-page">' . esc_html__('Add additional pages','white-rabbit-suite') . '</a>', esc_url(admin_url('post-new.php?post_type=page'))); ?></li>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-write-blog">' . esc_html__('Add a blog post','white-rabbit-suite') . '</a>', esc_url(admin_url('post-new.php'))); ?></li>
                    <?php else : ?>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-add-page">' . esc_html__('Add an About page','white-rabbit-suite') . '</a>', esc_url(admin_url('post-new.php?post_type=page'))); ?></li>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-write-blog">' . esc_html__('Write your first blog post','white-rabbit-suite') . '</a>', esc_url(admin_url('post-new.php'))); ?></li>
                    <?php endif; ?>
                    <?php if (current_theme_supports('menus')) : ?>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-widgets-menus">' . esc_html__('Manage menus','white-rabbit-suite') . '</a>', esc_url(admin_url('nav-menus.php'))); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="welcome-panel-column welcome-panel-last">
                <h3><?php esc_html_e('More Actions','white-rabbit-suite'); ?></h3>
                <ul>

                    <?php if (current_user_can('manage_options')) : ?>
                        <li><?php printf('<a href="%s" class="welcome-icon welcome-customize load-customize hide-if-no-customize">' . esc_html__('Customize Your Site','white-rabbit-suite') . '</a>', esc_url(wp_customize_url())); ?></li>
                    <?php endif; ?>


                    <a class="button button-primary button-hero hide-if-customize"
                       href="<?php echo admin_url('themes.php'); ?>"><?php esc_html_e('Customize Your Site','white-rabbit-suite'); ?></a>


                    <?php if (current_theme_supports('widgets') || current_theme_supports('menus')) : ?>
                        <li>
                            <div class="welcome-icon welcome-widgets-menus"><?php
                                if (current_theme_supports('widgets')) {
                                    echo '<a href="' . esc_url(admin_url('widgets.php')) . '">' . esc_html__('Manage widgets','white-rabbit-suite') . '</a>';
                                }
                                ?></div>
                        </li>
                    <?php endif; ?>
                    <li><?php printf('<a target="_blank" href="%s" class="welcome-icon welcome-go-back">' . esc_html__('Back to Suite','white-rabbit-suite') . '</a>', esc_url(__('https://www.whiterabbitsuite.com/create-or-connect-a-wordpress-site/','white-rabbit-suite'))); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

function whiterabbit_dashboard_widget()
{

    //remove default widget on dashboard
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');

    //add white rabbit widget to dashboard
    wp_add_dashboard_widget('whiterabbit_dashboard_cookie_notice_widget', esc_html__('White Rabbit Cookie Notice','white-rabbit-suite'), 'whiterabbit_dashboard_cookie_notice_widget');
    wp_add_dashboard_widget('whiterabbit_dashboard_sitemap_widget', esc_html__('White Rabbit Site Map','white-rabbit-suite'), 'whiterabbit_dashboard_sitemap_widget');

}

function whiterabbit_dashboard_cookie_notice_widget()
{

    $plugin = 'cookie-notice/cookie-notice.php';

    //check plugin file exist.
    $validate = validate_plugin($plugin);
    if ($validate != false) {
        echo $validate->get_error_message();
        return;
    }

    if (is_plugin_active($plugin)) {
        if (isset($_GET['action']) && $_GET['action'] === 'active_cookie-notice_success') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php esc_html_e("Plugin Activated",'white-rabbit-suite'); ?></strong>
                    <?php printf('<a href="%s" class="welcome-icon welcome-edit-page">' . esc_html__('Configure it','white-rabbit-suite') . '</a>', esc_url(admin_url('options-general.php?page=cookie-notice'))) ?>
                </p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><?php esc_html_e("Dismiss this notice.",'white-rabbit-suite'); ?></span>
                </button>
            </div>
            <?php
        }
        echo esc_html__("Activated, configure it",'white-rabbit-suite') . "<br>";
        printf('<a href="%s" class="welcome-icon welcome-edit-page">' . esc_html__('Cookie Notice','white-rabbit-suite') . '</a>', esc_url(admin_url('options-general.php?page=cookie-notice')));
        return;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'active_cookie-notice') {
        $result = activate_plugin($plugin, $redirect = admin_url('?action=active_cookie-notice_success'));
        if (is_wp_error($result)) {
            echo $result->get_error_message();
            return;
        }
    } else {
        echo "<h3>" . esc_html__("Not activated",'white-rabbit-suite') . "</h3>";
        printf('<a href="%s" class="button button-primary">' . esc_html__('Activate now','white-rabbit-suite') . '</a>', esc_url(admin_url('?action=active_cookie-notice')));

    }
}

function whiterabbit_http_api_curl($handle) {
    curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
}


function whiterabbit_dashboard_sitemap_widget()
{
    if(file_exists(ABSPATH . "/sitemap.xml")) {
        ?>
        <h3><?php esc_html_e('Your ', 'white-rabbit-suite'); ?><a
                    href="<?php echo esc_url(get_site_url() . "/sitemap.xml"); ?>" target="_blank"
                    target="Studio45 Sitemap"><?php esc_html_e('sitemap', 'white-rabbit-suite'); ?></a></h3>
        <?php
    }else{
        ?>
        <?php esc_html_e('White Rabbit\'s sitemap does not exist.', 'white-rabbit-suite'); ?>
    <?php
    }
}

function whiterabbit_wpforms_settings_section($sections, $form_data)
{
    $sections['whiterabbit'] = __( 'Whiterabbit', 'white-rabbit-suite' );
    return $sections;
}

function whiterabbit_wpforms_settings_section_content($instance)
{
    echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-whiterabbit">';
    echo '<div class="wpforms-panel-content-section-title">' . __( 'Whiterabbit', 'white-rabbit-suite' ) . ' - '. __( 'Fields Mapping', 'white-rabbit-suite' ) .' </div>';
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_firstname',
        $instance->form_data,
        __( 'First Name', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text', 'name' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_lastname',
        $instance->form_data,
        __( 'Last Name', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text', 'name' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_email',
        $instance->form_data,
        __( 'Email Address', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'email' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_message',
        $instance->form_data,
        __( 'Message Text', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'textarea' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_telephone1',
        $instance->form_data,
        __( 'Telephone 1', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'phone' , 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_telephone2',
        $instance->form_data,
        __( 'Telephone 2', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'phone' , 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_mobilephone1',
        $instance->form_data,
        __( 'Mobilephone 1', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'phone' , 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_mobilephone2',
        $instance->form_data,
        __( 'Mobilephone 2', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'phone' , 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_companyname',
        $instance->form_data,
        __( 'Company Name', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_email2',
        $instance->form_data,
        __( 'Secondary Email', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'email' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_birthdaydate',
        $instance->form_data,
        __( 'Birthday Date', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'date-time' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_birthplace',
        $instance->form_data,
        __( 'Birthplace', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_gender',
        $instance->form_data,
        __( 'Gender', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'select' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_fiscalcode',
        $instance->form_data,
        __( 'Fiscal Code', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_vatcode',
        $instance->form_data,
        __( 'Vat Code', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_address',
        $instance->form_data,
        __( 'Address', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'address', 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_city',
        $instance->form_data,
        __( 'City', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'address', 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_province',
        $instance->form_data,
        __( 'Province/State', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'address' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_nation',
        $instance->form_data,
        __( 'Nation/Country', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'address' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_postalcode',
        $instance->form_data,
        __( 'Postal Code', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'address', 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_zone',
        $instance->form_data,
        __( 'Zone', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'text' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_tag',
        $instance->form_data,
        __( 'Tag', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'hidden' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_note',
        $instance->form_data,
        __( 'Note', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'textarea' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );

    /*GDPR PURPOSES*/
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_marketing',
        $instance->form_data,
        __( 'GDPR Marketing', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_profiling',
        $instance->form_data,
        __( 'GDPR Profiling', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_thirdparties',
        $instance->form_data,
        __( 'GDPR Thirdparties', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_outsideeu',
        $instance->form_data,
        __( 'GDPR Outside EU', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_collection',
        $instance->form_data,
        __( 'GDPR Collection', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_other1',
        $instance->form_data,
        __( 'GDPR Other 1', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_other2',
        $instance->form_data,
        __( 'GDPR Other 2', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_other3',
        $instance->form_data,
        __( 'GDPR Other 3', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'checkbox', 'gdpr-checkbox' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    /* FINE GDPR PURPOSES */

    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_website',
        $instance->form_data,
        __( 'Website URL', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_linkedin',
        $instance->form_data,
        __( 'Linkedin URL Profile', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_twitter',
        $instance->form_data,
        __( 'Twitter URL Profile', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_facebook',
        $instance->form_data,
        __( 'Facebook URL Profile', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_instagram',
        $instance->form_data,
        __( 'Instagram URL Profile', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );
    wpforms_panel_field(
        'select',
        'settings',
        'whiterabbit_field_skype',
        $instance->form_data,
        __( 'Skype URL Profile', 'white-rabbit-suite' ),
        array(
            'field_map'   => array( 'url' ),
            'placeholder' => __( '-- Select Field --', 'white-rabbit-suite' ),
        )
    );

    echo '</div>';
}

function whiterabbit_track_email(){
    ?>
    <script type="text/javascript">
        function fnv1a64(r){var t,n=[];for(t=0;t<256;t++)n[t]=(t>>4&15).toString(16)+(15&t).toString(16);var o=r.length,a=0,f=8997,e=0,g=33826,i=0,v=40164,c=0,h=52210;for(t=0;t<o;)e=435*g,i=435*v,c=435*h,i+=(f^=r.charCodeAt(t++))<<8,c+=g<<8,f=65535&(a=435*f),g=65535&(e+=a>>>16),h=c+((i+=e>>>16)>>>16)&65535,v=65535&i;return n[h>>8]+n[255&h]+n[v>>8]+n[255&v]+n[g>>8]+n[255&g]+n[f>>8]+n[255&f]}

        jQuery(function($){
            var elementsArray = $('form[id^="wpforms-form-"]');
            elementsArray.each(function(elem) {
                $(this).on("submit", function(e) {
                    var email = $(this).find('.wpforms-field-container input[type="email"]:first').val();
                    if(email !== "" && typeof _paq !== 'undefined'){
                        _paq.push(['setVisitorId', fnv1a64(email)]);
                        _paq.push(['setUserId', email]);
                        _paq.push(['trackPageView']);
                        _paq.push(['enableLinkTracking']);
                    }
                });
            });
        });
    </script>
<?php
}