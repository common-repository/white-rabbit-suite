<?php
/**
 * Created by PhpStorm.
 * User: Enterprise
 * Date: 08/04/2016
 * Time: 23:04
 */
if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
    @set_time_limit(0);
}
ini_set('max_execution_time', 300);
if (!class_exists('wrConnector')) {
    require_once(WHITERABBIT_PATH . "connector/connector.php");
}
wp_register_style('white_rabbit_dashicons', plugin_dir_url(__FILE__) . 'css/white-rabbit.css');
wp_enqueue_style('white_rabbit_dashicons');
wp_register_script('custom-script', "/js/whiterabbit.js");

function whiterabbit_register_options_group()
{
    register_setting("wr_general_options_group", "wr_general_enabled");
    register_setting("wr_general_options_group", "wr_general_debug");
    register_setting("wr_general_options_group", "wr_general_apipath");

    register_setting("wr_general_options_group", "wr_general_site_token");
    register_setting("wr_general_options_group", "wr_general_consumer_key");
    register_setting("wr_general_options_group", "wr_general_consumer_secret");
    register_setting("wr_general_options_group", "wr_general_enable_gdpr_marketing");
    register_setting("wr_general_options_group", "wr_general_ticket");
    register_setting("wr_general_options_group", "wr_general_abandoned_cart_expiry");
    register_setting("wr_general_options_group", "wr_general_import_run");
    register_setting("wr_general_options_group", "wr_plugin_wordpress_generate_sitemap");

    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_rabbit_id");
    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_site_id");
    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_wr_token");
    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_script_url");
    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_script_token");
    register_setting("wr_whiterabbit_options_group", "wr_whiterabbit_wp_token");
    register_setting("wr_whiterabbit_options_group", "wr_plugin_wordpress_message_log");

    register_setting("wr_whiterabbit_analytics_options_group", "wr_plugin_wordpress_analytics_piwik_enabled");
    register_setting("wr_whiterabbit_analytics_options_group", "wr_plugin_wordpress_analytics_piwik_debug_enabled");
    register_setting("wr_whiterabbit_analytics_options_group", "wr_plugin_wordpress_analytics_piwik_code");
    register_setting("wr_whiterabbit_analytics_options_group", "wr_plugin_wordpress_analytics_piwik_api_code");
    register_setting("wr_whiterabbit_analytics_options_group", "wr_plugin_wordpress_analytics_ecommerce_goal_id");
    register_setting("wr_whiterabbit_analytics_options_group", "wr_whiterabbit_analytics_newsletter_goal_id");

    register_setting("wr_post_defaults", "wr_post_default_author_id");
    register_setting("wr_post_defaults", "wr_post_default_category_id");

}

add_action("admin_init", "whiterabbit_register_options_group");
add_action('admin_enqueue_scripts', 'whiterabbit_admin_script_css');
//Settings link in plugins page backend
add_filter( 'plugin_action_links_' . WHITERABBIT_FILE_PATH, 'wr_settings_link');

function wr_settings_link( $links ) {

    // Build and escape the URL.
    $url = esc_url(add_query_arg(
        'page',
        'wr-mainmenu',
        get_admin_url() . 'admin.php'
    ));

    // Create the link and merge in existing links.
    $links = array_merge(array("<a href='$url'>" . __('Settings', 'white-rabbit-suite') . '</a>'), $links);

    return $links;
}

function whiterabbit_admin_script_css()
{
    wp_enqueue_script('my-script', plugins_url('/js/whiterabbit.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '2.0');
    wp_register_style('admin-dashboard', plugin_dir_url(__FILE__) . 'css/admin-dashboard.css');
    wp_enqueue_style('admin-dashboard');

}


// add_action("admin_init", "whiterabbit_whiterabbit_options_group");
// add_action("admin_init", "whiterabbit_whiterabbit_analytics_options_group");

function whiterabbit_admin_settings_tab($current = 'general')
{
    $tabs = array('general' => __('General Settings','white-rabbit-suite'), 'gdpr' => __('View Your Suite GDPR','white-rabbit-suite'), 'import' => __('Import Data','white-rabbit-suite'), 'wrcodes' => __('Whiterabbit Codes','white-rabbit-suite'), 'wranalytics' => __('Whiterabbit Analytics','white-rabbit-suite'), 'wrpostdefaults' => __('Post Defaults','white-rabbit-suite'), 'seo' => __('Advanced','white-rabbit-suite'));
    echo '<div id="icon-themes" class="icon32"><br></div>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab => $name) {
        $class = ($tab == $current) ? ' nav-tab-active' : '';
        if ($tab == "wrcodes" || $tab == "wranalytics" || $tab == "import") {
            $class = " hidden";
        }

        echo "<a class='nav-tab". esc_attr($class) ."' href='?page=wr-mainmenu&tab=".esc_attr($tab)."'>" . esc_html__($name,'white-rabbit-suite') . "</a>";

    }
    echo '</h2>';
}

function whiterabbit_settings_page()
{
    global $pagenow;
    $settings = get_option("wr_theme_settings");
    $connector = new wrConnector();
    $contOrder = count($connector->whiterabbit_find_order_import());
    $contUser = count($connector->whiterabbit_find_user_import());

    //update_option('wr_plugin_wordpress_data_import_run', false);

    $import = 1;

    $msgImport = "<b>".esc_html__("NO IMPORT DATA",'white-rabbit-suite')."</b>";

    if (get_option('wr_plugin_wordpress_data_import_run') == true) {
        $msgImport = "<div id=\"refresh\"><b>". esc_html__("Import Run,wait...",'white-rabbit-suite') ."</b><br>" . esc_html__("(This page will be reloaded every 30s. If after 120s. the procedure seems to be blocked, the number of orders and users to be imported does not decrease, click Refresh Button.If the number to be imported does not decrease, make a new connection to the suite and press the Import Data Button again)",'white-rabbit-suite') . "<br>
                        <a class=\"button-primary\" href='#' onclick=\"window.location.reload()\">" . esc_html__("Refresh",'white-rabbit-suite') . "</a></div>";
        $import = 0;
    }

    if ($contOrder == 0 && $contUser == 0) {
        update_option('wr_plugin_wordpress_data_import_run', false);
        $msgImport = "<b>".esc_html__("NO IMPORT DATA",'white-rabbit-suite')."</b>";
        $import = 0;
    }

    $woocomerce = $connector->wooCommerceVersionCheck();
    $woocomerce_version = $connector->getWooCommerceAPIVersion();
    $woocomerce_cron = $connector->wpCronCheck();

    if (isset($_GET['tab'])) whiterabbit_admin_settings_tab(sanitize_text_field($_GET['tab'])); else whiterabbit_admin_settings_tab('general');

    if (get_option('wr_plugin_wordpress_suite_operation_token') != "" && get_option('wr_plugin_wordpress_message_log') == null) {

        echo "<div class='notice notice-success'><p>" . esc_html__("Success Connect !",'white-rabbit-suite') . "</p></div>";

        if (get_option('wr_plugin_wordpress_data_import') == true) {
            echo "<div class='notice notice-success'><p>" . esc_html__("Import OK !",'white-rabbit-suite') . "</p></div>";
            update_option("wr_plugin_wordpress_data_import", false);
        }


    } else {

        echo "<div class='notice notice-error'><p>" . esc_html__("No Connect !",'white-rabbit-suite') . " " . "<b>" . get_option('wr_plugin_wordpress_message_log') . "</b></p></div>";
        $import = 0;
        $msgImport = "<b>" . esc_html__("NO CONNECT !",'white-rabbit-suite') . "</b>";
    }

    if (get_option('wr_plugin_wordpress_generate_sitemap') == true) {
        echo "<div class='notice notice-success'><p>" . esc_html__("Sitemap Generated Successfully!",'white-rabbit-suite') . "</p></div>";
        update_option("wr_plugin_wordpress_generate_sitemap", false);
    }

    if ( $woocomerce == true && $woocomerce_cron == false ) {
        $cron_command = site_url() . '/wp-cron.php';
        ?>
        <div class='notice notice-info'><p>
            <?php esc_html_e( "Your WP Cron is disabled. If you want to use WP Cron, open file wp-config.php and delete row define( \"DISABLE_WP_CRON\", true );.", 'white-rabbit-suite' ); ?>
        </p>
        <p>
            <?php esc_html_e( " If you want to use CronJob server, access your server and config with command:", 'white-rabbit-suite' ); ?>
        </p>
        <p>
            <input style="width:100%;" type="text" value="<?php echo( " * * * * * curl $cron_command" ) ?>" readonly>
        </p>
        </div>
        <?php
    }
    ?>
    <form method="post" action="options.php" name="options" id="options" autocomplete="off">
        <?php switch (@$_GET['tab']) {
            case 'gdpr':
                ?>
                <?php settings_fields('wr_whiterabbit_options_group'); ?>
                <table class="form-table" id="wr_whiterabbit_options_group">
                    <tbody>
                    <tr valign="top">
                        <td>
                            <label for="wr_plugin_wordpress_rabbit_id"><b> <?php esc_html_e("In White Rabbit Suite (Setting -> GDPR) you have set",'white-rabbit-suite'); ?></b></label>
                        </td>
                    </tr>

                    <tr valign="top">

                        <td scope="row">
                            "<?php
                            $gdpr = get_option('wr_plugin_wordpress_suite_gdpr_list');
                            $gdprStr = '';
                            foreach ($gdpr as $value) {
                                $gdprStr .= esc_html__($value,'white-rabbit-suite') . "<br>";
                            }
                            if ($gdpr == "") {
                                $gdprStr = esc_html__("No Setting !",'white-rabbit-suite');
                            }
                            echo $gdprStr . esc_html__("\"",'white-rabbit-suite');
                            ?>
                        </td>
                    </tr>

                    </tbody>
                </table>
                <?php
                break;


            case 'import':
                ?>
                <?php settings_fields('wr_whiterabbit_options_group'); ?>
                <table class="form-table" id="wr_whiterabbit_options_group">
                    <tbody>
                    <tr valign="top">
                        <td>
                            <label for="wr_plugin_wordpress_rabbit_id"><b><?php esc_html_e("USE THIS FUNCTION TO IMPORT ALL USERS AND ALL ORDERS PREVIOUS TO INSTALLING THE PLUGIN",'white-rabbit-suite'); ?></b></label>
                        </td>
                    </tr>

                    <tr valign="top">

                        <td scope="row">
                            <p>
                                <?php esc_html_e('You Can Import Into WhiteRabbit Suite : ','white-rabbit-suite'); ?>
                                <b><?php echo $contOrder . esc_html__(" Orders",'white-rabbit-suite'); ?></b>
                            </p>

                            <p>
                                <?php esc_html_e('You Can Import Into WhiteRabbit Suite : ','white-rabbit-suite'); ?>
                                <b><?php echo $contUser . esc_html__(" Users",'white-rabbit-suite'); ?></b>
                            </p>

                        </td>

                    </tr>

                    <!--<tr valign="top">
                        <td scope="row">
                            <p>
                                <?php /* if ($import == 1) {
                                if ($woocomerce == 1) {
                                ?>

                                <input type="submit" class="button-primary" id="import" name="import"
                                       value="<?php esc_html_e('Import Data','white-rabbit-suite') ?>"/>
                            <div id="loader"></div>
                            <?php } else { ?>
                                <p><b><?php esc_html_e("Import data not available",'white-rabbit-suite'); ?></b><?php esc_html_e(". You must update the Woocomerce plugin at least to
                                    version 3.0.",'white-rabbit-suite'); ?></p>
                            <?php }
                            } else {

                                echo $msgImport;
                            } */?>


                            </p>

                        </td>

                    </tr>-->

                    </tbody>
                </table>
                <?php
                break;
            case 'wrcodes':
                ?>
                <?php settings_fields('wr_whiterabbit_options_group'); ?>
                <table class="form-table" id="wr_whiterabbit_options_group">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_rabbit_id"><?php esc_html_e("Whiterabbit id",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_rabbit_id"
                                   value="<?php echo get_option('wr_plugin_wordpress_rabbit_id'); ?>"
                                   name="wr_plugin_wordpress_rabbit_id" size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_site_id"><?php esc_html_e("Site id",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_site_id"
                                   value="<?php echo get_option('wr_plugin_wordpress_site_id'); ?>"
                                   name="wr_plugin_wordpress_site_id" size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_wr_token"><?php esc_html_e("WR Token",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_wr_token"
                                   value="<?php echo get_option('wr_plugin_wordpress_wr_token'); ?>"
                                   name="wr_plugin_wordpress_wr_token" size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_whiterabbit_wp_token"><?php esc_html_e("WP Token",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_whiterabbit_wp_token"
                                   value="<?php echo get_option('wr_whiterabbit_wp_token'); ?>"
                                   name="wr_whiterabbit_wp_token" size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <!-- <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <p>
                                <input type="submit" class="button-primary" id="submit" name="submit"
                                       value="<?php /*esc_html_e('Save Changes','white-rabbit-suite') */
                    ?>"/>
                            </p>
                        </td>
                    </tr>-->
                    </tbody>
                </table>
                <?php
                break;
            case 'wranalytics':
                ?>
                <?php settings_fields('wr_whiterabbit_analytics_options_group'); ?>
                <table class="form-table" id="wr_whiterabbit_analytics_options_group">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_analytics_piwik_enabled"><?php esc_html_e("Analytics enabled",'white-rabbit-suite') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" disabled="disabled"
                                   id="wr_plugin_wordpress_analytics_piwik_enabled" <?php if (get_option('wr_plugin_wordpress_analytics_piwik_enabled')) {
                                echo "checked='checked'";
                            } ?> name="wr_plugin_wordpress_analytics_piwik_enabled"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_analytics_piwik_debug_enabled"><?php esc_html_e("Analytics debug",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" disabled="disabled"
                                   id="wr_plugin_wordpress_analytics_piwik_debug_enabled" <?php if (get_option('wr_plugin_wordpress_analytics_piwik_debug_enabled')) {
                                echo "checked='checked'";
                            } ?> name="wr_plugin_wordpress_analytics_piwik_debug_enabled"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_analytics_piwik_code"><?php esc_html_e("Analytics Code",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_analytics_piwik_code"
                                   value="<?php echo get_option('wr_plugin_wordpress_analytics_piwik_code'); ?>"
                                   name="wr_plugin_wordpress_analytics_piwik_code"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_analytics_piwik_api_code"><?php esc_html_e("Analitycs API",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_analytics_piwik_api_code"
                                   value="<?php echo get_option('wr_plugin_wordpress_analytics_piwik_api_code'); ?>"
                                   name="wr_plugin_wordpress_analytics_piwik_api_code"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_plugin_wordpress_analytics_ecommerce_goal_id"><?php esc_html_e("Analitycs Goal Ecommerce ID",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_plugin_wordpress_analytics_ecommerce_goal_id"
                                   value="<?php echo get_option('wr_plugin_wordpress_analytics_ecommerce_goal_id'); ?>"
                                   name="wr_plugin_wordpress_analytics_ecommerce_goal_id"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_whiterabbit_analytics_newsletter_goal_id"><?php esc_html_e("Analitycs Goal Newsletter ID",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" disabled id="wr_whiterabbit_analytics_newsletter_goal_id"
                                   value="<?php echo get_option('wr_whiterabbit_analytics_newsletter_goal_id'); ?>"
                                   name="wr_whiterabbit_analytics_newsletter_goal_id"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <!--  <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <p>
                                <input type="submit" class="button-primary" id="submit" name="submit"
                                       value="<?php /*esc_html_e('Save Changes','white-rabbit-suite') */
                    ?>"/>
                            </p>
                        </td>
                    </tr>-->
                    </tbody>
                </table>
                <?php
                break;
            case 'wrpostdefaults':
                ?>
                <?php settings_fields('wr_post_defaults'); ?>
                <table class="form-table" id="post-defaults">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_post_default_author_id"><?php esc_html_e("Default Author",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <?php wp_dropdown_users(array('id' => 'wr_post_default_author_id', 'name' => 'wr_post_default_author_id', 'selected' => get_option("wr_post_default_author_id"))); ?>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_post_default_category_id"><?php esc_html_e("Default Category",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <?php wp_dropdown_categories(array('id' => 'wr_post_default_category_id', 'name' => 'wr_post_default_category_id', 'selected' => get_option("wr_post_default_category_id"))); ?>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <!--<tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <p>
                                <input type="submit" class="button-primary" id="submit" name="submit"
                                       value="<?php /*esc_html_e('Save Changes','white-rabbit-suite') */
                    ?>"/>
                            </p>
                        </td>
                    </tr>-->
                    </tbody>
                </table>
                <?php
                break;
            case 'seo':
                ?>
                <?php settings_fields('wr_whiterabbit_options_group'); ?>
                <table class="form-table" id="wr_whiterabbit_options_group">
                    <tbody>
                    <tr valign="top">
                        <td>
                            <label for="wr_plugin_wordpress_rabbit_id"><b><?php esc_html_e("White Rabbit SEO",'white-rabbit-suite'); ?></b></label>
                        </td>
                    </tr>

                    <tr valign="top">

                        <td scope="row">
                            <p>
                                <?php esc_html_e('It generates the sitemap.xml and the robots.txt on your site\'s root folder','white-rabbit-suite'); ?>
                            </p>
                        </td>

                    </tr>

                    <tr valign="top">
                        <td scope="row">
                            <p>
                                <input type="submit" onclick="document.getElementById('seo').disabled = true;" class="button-primary" id="seo" name="seo"
                                       value="<?php esc_html_e('Generate Sitemap','white-rabbit-suite') ?>"/>
                                <div id="loader"></div>
                            </p>

                        </td>

                    </tr>

                    </tbody>
                </table>
                <?php
                break;
            default:
                ?>
                <?php settings_fields('wr_general_options_group'); ?>
                <table class="form-table" id="general_options_group">
                    <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_apipath"><?php esc_html_e("Enabled Plugin",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>

                            <input type="checkbox" id="wr_general_enabled" <?php if (get_option('wr_general_enabled')) {
                                echo "checked='checked'";
                            }
                            ?> name="wr_general_enabled"/>
                        </td>
                    </tr>

                    <!-- <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_debug">Enable Debug</label>
                        </th>
                        <td>
                            <input type="checkbox" id="wr_general_debug" <?php /*if (get_option('wr_general_debug')) {
                                echo "checked='checked'";
                            } */
                    ?> name="wr_general_debug"/>
                            <span class="description"></span>
                        </td>
                    </tr>-->

                    <tr valign="top" style="display:none;">
                        <th scope="row">
                            <label for="wr_general_apipath"><?php esc_html_e("Url Api White Rabbit Suite :",'white-rabbit-suite'); ?>
                                https://suite.whiterabbitsuite.com'</label>
                        </th>
                        <td>
                            <input type="text" readonly placeholder="https://suite.whiterabbitsuite.com"
                                   id="wr_general_apipath"
                                   value="<?php echo get_option('wr_general_apipath'); ?>" name="wr_general_apipath"
                                   size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>


                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_site_token"><?php esc_html_e("Token Site Login Suite",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wr_general_site_token"
                                   value="<?php echo get_option('wr_general_site_token'); ?>"
                                   name="wr_general_site_token"
                                   size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>

                    <?php if($woocomerce_version){ ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_consumer_key"><?php esc_html_e("Consumer Key Woocommerce",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wr_general_consumer_key"
                                   value="<?php echo get_option('wr_general_consumer_key'); ?>"
                                   name="wr_general_consumer_key"
                                   size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_site_token"><?php esc_html_e("Consumer Secret Woocommerce",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wr_general_consumer_secret"
                                   value="<?php echo get_option('wr_general_consumer_secret'); ?>"
                                   name="wr_general_consumer_secret"
                                   size="70"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_abandoned_cart_expiry"><?php esc_html_e("Time Interval in days for the Cancellation of Abandoned Carts",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wr_general_abandoned_cart_expiry"
                                   value="<?php echo get_option('wr_general_abandoned_cart_expiry'); ?>"
                                   name="wr_general_abandoned_cart_expiry"
                                   min="1" />
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_ticket"><?php esc_html_e("Enable Ticket Creation from Contact Forms",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="wr_general_ticket" <?php if (get_option('wr_general_ticket')) {
                                echo "checked='checked'";
                            }
                            ?> name="wr_general_ticket"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="wr_general_enable_gdpr_marketing"><?php esc_html_e("Enable Suite WhiteRabbit Marketing Consent",'white-rabbit-suite'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="wr_general_enable_gdpr_marketing" <?php if (get_option('wr_general_enable_gdpr_marketing')) {
                                echo "checked='checked'";
                            }
                            ?> name="wr_general_enable_gdpr_marketing"/>
                            <span class="description"></span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"></th>
                        <td>
                            <p>
                                <input type="submit" class="button-primary" id="submit" name="submit"
                                       value="<?php esc_html_e('Save Changes','white-rabbit-suite') ?>"/>

                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
                break;
        } ?>
    </form>

    <?php
}

function whiterabbit_landingpages_page()
{
    echo "";
}

function whiterabbit_add_admin_menu()
{
    $hook = add_menu_page(esc_html__("White Rabbit Options",'white-rabbit-suite'), "White Rabbit", "administrator", "wr-mainmenu", "whiterabbit_settings_page", "dashicons-white-rabbit");
    add_action('load-' . $hook, 'whiterabbit_option_save');

}

function whiterabbit_add_admin_submenu()
{
    //$hook = add_submenu_page("wr-mainmenu", "White Rabbit Settings", "Settings", "administrator", "wr-settings", "wr_settings_page");


    //elimino perchè non si gestiscono + le landing page come interne ai post

    /*$hook2 = add_submenu_page("wr-mainmenu", "White Rabbit Settings", "Landing pages", "administrator", "wr_landingpages", "wr_landingpages_page");
     add_action('load-' . $hook2, 'whiterabbit_option_save');*/

}

function whiterabbit_option_save()
{
    if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
        $wr_connect = new wrConnector();

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : "general";
        $result = $wr_connect->Oldconnect();

        if($result['error'] == true){
          update_option("wr_plugin_wordpress_message_log", esc_html__($result["error_message"],'white-rabbit-suite'));
          return;
        }

        update_option("wr_plugin_wordpress_wr_token", $result["token"]);

        //Generate sitemap
        if($tab == "seo"){
            //Get Sitemap
            $postsForSitemap = get_posts(array(
                'numberposts' => -1,
                'orderby' => 'modified',
                'post_type' => array('post', 'page', 'property', 'product'),
                'order' => 'DESC'));
            whiterabbit_get_sitemap("sitemap.xml", $postsForSitemap);

            //Get Robots
            whiterabbit_get_robots();

            update_option("wr_plugin_wordpress_generate_sitemap", true);

            return true;
        }

        /*
         * TODO: import with new token from connect()
        if ($tab == "import") {
            //  if ($result["result"] == true && $result["error"] == false) {
            if (!empty($result["token"])) {
                update_option("wr_plugin_wordpress_data_import_run", true);
                $wr_connect->whiterabbit_import_orders($result);
                update_option("wr_plugin_wordpress_data_import", true);
                return true;
            }else{
                update_option("wr_plugin_wordpress_message_log", esc_html__("Error Token",'white-rabbit-suite'));
                return false;
            }
        }*/
        $wr_general_enabled = get_option("wr_general_enabled", false);
        $wr_general_debug = get_option("wr_general_debug", false);
        $wr_general_apipath = get_option("wr_general_apipath", "");

        $wr_general_enable_gdpr_marketing = get_option("wr_general_enable_gdpr_marketing", "");
        $wr_general_site_token = get_option("wr_general_site_token", "");
        $wr_general_ticket = get_option("wr_general_ticket", false);


        if ($wr_general_enabled) {

            if ($tab == "general" || $tab == "" || $tab == "import") {

                $wr_connect = new wrConnector();

                $result = $wr_connect->connect();
                $token = $result["token"];

                if ($result["result"] == "true" && $result["token"] != "") {

                    update_option("wr_plugin_wordpress_suite_operation_token", $result["token"]);

                    $resultInfo = $wr_connect->getSiteInfo($token);

                    if ($resultInfo["result"] == "true" && $resultInfo["error"] == "false") {
                        update_option("wr_plugin_wordpress_analytics_piwik_enabled", true);
                        update_option("wr_plugin_wordpress_site_id", !empty($resultInfo["site_id"]) ? $resultInfo["site_id"] : '');
                        update_option("wr_plugin_wordpress_analytics_piwik_code", !empty($resultInfo["piwik_code"]) ? $resultInfo["piwik_code"] : '');
                        update_option("wr_whiterabbit_analytics_newsletter_goal_id", !empty($resultInfo["piwik_newsletter_goal_id"]) ? $resultInfo["piwik_newsletter_goal_id"] : '');
                        update_option("wr_plugin_wordpress_rabbit_id", !empty($resultInfo["rabbit_id"]) ? $resultInfo["rabbit_id"] : '');
                        update_option("wr_whiterabbit_piwik_url", !empty($resultInfo["piwik_url"]) ? $resultInfo["piwik_url"] : '');
                        update_option("wr_plugin_wordpress_script_token", !empty($resultInfo["script_token"]) ? $resultInfo["script_token"] : '');
                        update_option("wr_plugin_wordpress_script_url", !empty($resultInfo["script_url"]) ? $resultInfo["script_url"] : '');
                        update_option("wr_plugin_wordpress_message_log", Null);
                        update_option("wr_plugin_wordpress_data_import_run", false);
                        update_option("wr_plugin_wordpress_generate_sitemap", false);
                    }
                    $decode = $wr_connect->decodeToken($token);
                    $customerID = $decode->customerID;
                    $resultGdpr = $wr_connect->getSuiteGDPR($token, $customerID);
                    if ($resultGdpr == Null || $resultGdpr['error'] == 'true') {
                        update_option("wr_plugin_wordpress_suite_gdpr_list", [__("No Setting !",'white-rabbit-suite')]);
                    } else {
                        $strGdrp = array();
                        foreach ($resultGdpr['result'] as $gdpr) {
                            $strGdrp [] = $gdpr->label;
                        }
                        update_option("wr_plugin_wordpress_suite_gdpr_list", $strGdrp);
                    }
                } else {
                    update_option("wr_plugin_wordpress_site_id", Null);
                    update_option("wr_plugin_wordpress_analytics_piwik_code", Null);
                    update_option("wr_whiterabbit_analytics_newsletter_goal_id", Null);
                    update_option("wr_plugin_wordpress_rabbit_id", Null);
                    update_option("wr_plugin_wordpress_wr_token", Null);
                    update_option("wr_plugin_wordpress_suite_operation_token", Null);
                    update_option("wr_plugin_wordpress_suite_gdpr_list", array());
                    if ($result["error_message"] == Null) {
                        update_option("wr_plugin_wordpress_message_log", esc_html__("Your Url Api is probably incorrect or Token Site Login Suite is incorrect",'white-rabbit-suite'));
                    } else {
                        $wr_connect->wrErrorLog("Errore Messaggio: " . $result["error_message"]);
                        update_option("wr_plugin_wordpress_message_log", esc_html__($result["error_message"],'white-rabbit-suite'));
                    }
                }
            }
        }
    }
}


add_action("admin_menu", "whiterabbit_add_admin_menu");
add_action("admin_menu", "whiterabbit_add_admin_submenu");
add_action("option_save_after", 'whiterabbit_option_save');