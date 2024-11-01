<?php

/**
 * Created by PhpStorm.
 * User: Mugnano Fabio
 * Date: 08/04/2016
 * Time: 21:58
 */

use Firebase\JWT\JWT;

if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
    @set_time_limit(0);
}
ini_set('max_execution_time', 300);

if (!class_exists('\Firebase\JWT\JWT')) {
    require_once("lib/JWT.php");
    require_once("lib/SignatureInvalidException.php");
}

if (!class_exists('wrDatabase')) {
    require_once(WHITERABBIT_PATH . "connector/database.php");
}

class wrConnector
{

    protected $_enabled = false;
    protected $_wrApiPath = "";
    protected $_wrSiteToken = "";
    protected $_wrTicket = false;
    protected $_encrypt_method = "AES-256-CBC";
    protected $_secret_key = "A34wTþ~2u7äo6a6g7";
    protected $_secret_iv = "PkCŽ4l1x0o3hrv1";

    protected $_overrideConfig = array();


    const WP_WR_PATH = "/?wr_action=";
    const WR_OLD_API_CONNECT_PATH = "/api_connector/api_connect/";
    const WR_API_CONNECT_PATH = "/connector/connect/token.json";
    const WR_SET_SUITE_GDPR_PATH = "/connector/gdpr/index.json";
    const WR_GET_SUITE_GDPR_PATH = "/api/gdpr/purpose.json";
    const WR_API_GET_INFO = "/api_connector/api_getinfo/";
    const WR_API_SET_EVENT = "/api_connector/set_event/";
    const WR_ENABLE_DEBUG = true;
    const WR_GENERAL_PURPOSE_GDPR = ["marketing", "profiling", "thirdparties", "outsideeu", "collection", "other1", "other2", "other3"];

    const  WR_LOG_PATH = WHITERABBIT_PATH . 'logs/debug.log';

    public function __construct($overrideConfig = array())
    {

        $this->_enabled = get_option('wr_general_enabled');
        $this->_wrApiPath = get_option('wr_general_apipath');
        $this->_wrSiteToken = get_option('wr_general_site_token');
        $this->_wrTicket = get_option('wr_general_ticket');
        if (!empty($overrideConfig)) {
            $this->_overrideConfig = $overrideConfig;
        }

    }


    public function Oldconnect()
    {
        $enabled = $this->_enabled;
        $apipath = $this->_wrApiPath;
        $sitetoken = $this->_wrSiteToken;
        $consumerkey = null;
        $consumersecret = null;

        $version = $this->getWooCommerceAPIVersion();

        if ($version) {
            $consumerkey = get_option('wr_general_consumer_key');
            $consumersecret = get_option('wr_general_consumer_secret');

            if (empty($consumerkey) || empty($consumersecret)) {
                $results = array(
                    "result" => false,
                    "token" => "",
                    "error" => true,
                    "error_message" => "You must insert API REST keys for Woocommerce Integrations with this user"
                );

                return $results;
            }
        }

        //if error change with get_home_url()
        $call_url = get_site_url();

        $data = array(
            "connector" => "WordpressConnector",
            "uri" => $call_url,
            "name" => $call_url,
            "apipath" => $call_url . self::WP_WR_PATH,
            "siteToken" => $sitetoken,
            "version" => ($version) ? $version : null,
            "consumerkey" => $consumerkey,
            "consumersecret" => $consumersecret
        );

        $data_string = json_encode($data);

        $fields_string = "";
        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $response = $this->serverPost($apipath . self::WR_OLD_API_CONNECT_PATH, $fields_string);
        $response_data = json_decode($response);

        $results = array(
            "result" => isset($response_data->error) ? !$response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? $response_data->error : "",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response
        );

        return $results;

    }


    public function connect()
    {
        $enabled = $this->_enabled;
        $apipath = $this->_wrApiPath;
        $siteToken = $this->_wrSiteToken;
        $ticket = $this->_wrTicket;
        //if error change with get_home_url()
        $call_url = get_site_url();

        // echo get_home_url();
        // die();
        $data = array(
            "connector" => "WordpressConnector",
            "uri" => $call_url,
            "name" => $call_url,
            "apipath" => $call_url . self::WP_WR_PATH,
            "token" => $siteToken,
        );

        $data_string = json_encode($data);

        $fields_string = "";
        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');


        $response = $this->serverPost($apipath . self::WR_API_CONNECT_PATH, $fields_string);

        $response_data = json_decode($response);


        $results = array(
            //"result" => !$response_data->error,
            "result" => isset($response_data->token) ? "true" : "false",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->message) ? "true" : "false",
            "error_message" => isset($response_data->message) ? $response_data->message : "",
        );

        return $results;

    }


    public function whiterabbit_crm_update_order($order_id, $old_status, $new_status, $connect = Null)
    {
        $order = wc_get_order($order_id);
        $productActivity = array();
        $shipping = array();
        $tags = array();
        $shipping['tax'] = $order->get_shipping_tax();
        $shipping['total'] = $order->get_shipping_total();
        $shipping['method'] = $order->get_shipping_method();

        if ($order->get_shipping_first_name() == "") {
            $shipping['firstname'] = $order->get_billing_first_name();
            $shipping['lastname'] = $order->get_billing_last_name();
            $shipping['address'] = $order->get_billing_address_1();
            $shipping['city'] = $order->get_billing_city();
            $shipping['province'] = $order->get_billing_state();
            $shipping['postalcode'] = $order->get_billing_postcode();
            $shipping['nation'] = $order->get_billing_country();
            $shipping['phone'] = $order->get_billing_phone();
        } else {
            $shipping['firstname'] = $order->get_shipping_first_name();
            $shipping['lastname'] = $order->get_shipping_last_name();
            $shipping['address'] = $order->get_shipping_address_1();
            $shipping['city'] = $order->get_shipping_city();
            $shipping['province'] = $order->get_billing_state();
            $shipping['postalcode'] = $order->get_shipping_postcode();
            $shipping['nation'] = $order->get_shipping_country();
            $shipping['phone'] = "";
        }

        $coupons = $order->get_coupon_codes();
        foreach ($coupons as $coupon_code) {
            $tags[] = $coupon_code;
        }

        $order_items = $order->get_items();

        //if ($new_status == "processing" || $new_status == "completed") {
        foreach ($order_items as $key => $item) {
            $product_id = $item['product_id'];
            //WooCommerce 2.6.0
            //$prod = wc_get_product( $product_id );
            ////WooCommerce 3.0.0

            $prod = $item->get_product();

            if ($prod == false) {
                continue;
            }
            $discount = (!empty($prod->get_regular_price())) ? $prod->get_regular_price() - $prod->get_price() : "";

            if ($discount == "0") {
                $discount = "";
            }

            if ($product_id == 0 || $product_id == "") {
                $product_id = "Prod Cancelled";
            }

            //$price_excl_tax = wc_get_price_excluding_tax($prod); // price without VAT
            //$price_incl_tax = wc_get_price_including_tax($prod);  // price with VAT
            $tax_amount = $item['line_tax'] / $item['qty']; // VAT amount

            $productActivity[$key]['product_id'] = $product_id;
            $productActivity[$key]['qty'] = $item['qty'];
            $productActivity[$key]['name'] = $item['name'];
            $productActivity[$key]['price'] = $order->get_item_total($item, true);
            $productActivity[$key]['discount'] = $discount;
            /*new*/
            $productActivity[$key]['sku'] = $prod->get_sku();
            $productActivity[$key]['description'] = wp_strip_all_tags($prod->get_short_description());
            $productActivity[$key]['tax'] = $tax_amount;
            //$productActivity[$key]['category'] = wc_get_product_category_list($product_id);
            $product_cats = wp_get_post_terms($product_id, 'product_cat');
            $product_cats_name = array();
            foreach ($product_cats as $cat) {
                $product_cats_name[] = $cat->name;
            }
            $productActivity[$key]['category'] = implode(",", $product_cats_name);
            /*new*/

        }

        //}

        $email = $order->get_billing_email();
        // $order->update_status('completed');

        $userId = $order->get_user_id();

        if ($userId == "" || $userId == null) {
            $userId = 0;
        }
        $author_obj = get_user_by('id', $userId);
        if (isset($author_obj->data->user_email)) {
            $email = sanitize_email($author_obj->data->user_email);
        }

        $event_data = array("orderIdExt" => $order_id,
            "orderNum" => $order_id,
            "orderDate" => $order->get_date_modified()->format(\DateTime::ATOM),
            "sourceId" => site_url(),
            "email" => strtolower($email),
            "tags" => serialize($tags),
            "site_name" => site_url(),
            "orderTotal" => $order->get_total(),
            "orderState" => $new_status,
            /*new*/
            "currency" => $order->get_currency(),
            "tax_total" => $order->get_total_tax(),
            "subtotal" => $order->get_subtotal(),
            "cart_discount" => $order->get_total_discount(),
            "payment_method" => $order->get_payment_method_title(),
            "shipping" => serialize($shipping),
            "timezone" => WHITERABBIT_TIMEZONE,
            /*new*/
            "orderNote" => "ORDER STATUS CHANGE. Old status : " . $old_status . ".New status : " . $new_status,
            "productActivity" => serialize($productActivity),
        );

        if ($connect) {
            $result = $connect;
        } else {
            $result = $this->connect();
        }

        $data = array(
            "connector" => "WordpressConnector",
            "channel" => "WordpressEcommerceConnector",
            "operation" => "write", // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            "data" => json_encode($event_data), // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            "token" => $result['token']

        );

        $data_string = http_build_query($data);


        $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);
        $response_data = json_decode($response);

        if ($response_data->error == false) {
            $this->whiterabbit_insert_order_user($order_id, $userId);
        }

        return $response_data;
    }

    public function whiterabbit_crm_insert_cart_abandoned($cart_data)
    {
        $productActivity = array();

        $cart_items = $cart_data['products'];

        foreach ($cart_items as $key => $item) {
            $product_id = $item['product_id'];
            $prod = wc_get_product($product_id);

            if ($prod == false) {
                continue;
            }
            $discount = (!empty($prod->get_regular_price())) ? $prod->get_regular_price() - $prod->get_price() : "";

            if ($discount == "0") {
                $discount = "";
            }

            if ($product_id == 0 || $product_id == "") {
                $product_id = "Prod Cancelled";
            }

            $productActivity[$key]['product_id'] = $product_id;
            $productActivity[$key]['qty'] = $item['qty'];
            $productActivity[$key]['name'] = $prod->get_name();
            $productActivity[$key]['price'] = $prod->get_price();
            $productActivity[$key]['discount'] = $discount;
            $productActivity[$key]['sku'] = $prod->get_sku();
            $productActivity[$key]['description'] = wp_strip_all_tags($prod->get_short_description());
            $productActivity[$key]['tax'] = $item['tax'];
            $product_cats = wp_get_post_terms($product_id, 'product_cat');
            $product_cats_name = array();
            foreach ($product_cats as $cat) {
                $product_cats_name[] = $cat->name;
            }
            $productActivity[$key]['category'] = implode(",", $product_cats_name);
        }

        $author_obj = get_user_by('id', $cart_data['user_id']);
        if (isset($author_obj->data->user_email)) {
            $email = sanitize_email($author_obj->data->user_email);
        }

        if (empty($email)) {
            return;
        }

        $event_data = array(
            "id" => $cart_data['cart_id'],
            "cart_date" => $cart_data['cart_date'],
            "sourceId" => site_url(),
            "email" => strtolower($email),
            "site_name" => site_url(),
            "timezone" => WHITERABBIT_TIMEZONE,
            "total_price" => $cart_data['cart_total'],
            "currency" => $cart_data['currency'],
            "total_tax" => $cart_data['cart_tax'],
            "cart_close" => ($cart_data['cart_close'] == '1') ? true : false,
            //"total_discounts" => $order->get_total_discount(),
            //"note" => $order->get_total_discount(),
            "productActivity" => serialize($productActivity),
        );

        $result = $this->connect();

        $data = array(
            "connector" => "WordpressConnector",
            "channel" => "WordpressCartConnector",
            "operation" => "write", // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            "data" => json_encode($event_data), // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
            "token" => $result['token']

        );

        $data_string = http_build_query($data);

        $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);
        $response_data = json_decode($response);

        $results = array(
            //"result" => !$response_data->error,
            "result" => isset($response_data->error) ? !$response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response,
        );

        return $results;
    }


    public function whiterabbit_crm_insert_user_newsletter($wpcf7_data)
    {
        $wrTicket = true;
        $wrtag = null;
        $province = null;
        $nation = null;
        $city = null;
        if ($this->_wrTicket == "" || $this->_wrTicket == "off") {
            $wrTicket = false;
        }

        $submission = WPCF7_Submission::get_instance();

        if ($submission) {
            $posted_data = $submission->get_posted_data();
        }

        $result = $this->connect();

        $title = isset($posted_data["title"]) ? $posted_data["title"] : null;
        $email = isset($posted_data["email"]) ? $posted_data["email"] : null;

        if (empty($email)) {
            $email = isset($posted_data["your-email"]) ? $posted_data["your-email"] : null;
        }

        if (empty($email)) {
            $results = array(
                //"result" => !$response_data->error,
                "result" => false,
                "token" => "",
                "error" => "true",
                "error_message" => "No Email"
            );
            return $results;
        }

        if (!empty($posted_data["wrtag"])) {
            if (is_array($posted_data['wrtag'])) {
                $wrtag = $posted_data['wrtag'][0];
            } else {
                $wrtag = $posted_data["wrtag"];
            }
        }

        if (!empty($posted_data["province"])) {
            if (is_array($posted_data['province'])) {
                $province = $posted_data['province'][0];
            } else {
                $province = $posted_data["province"];
            }
        }

        if (!empty($posted_data["nation"])) {
            if (is_array($posted_data['nation'])) {
                $nation = $posted_data['nation'][0];
            } else {
                $nation = $posted_data["nation"];
            }
        }

        if (!empty($posted_data["city"])) {
            if (is_array($posted_data['city'])) {
                $city = $posted_data['city'][0];
            } else {
                $city = $posted_data["city"];
            }
        }

        $custom_var = array();
        $prefix = 'wr_';
        $pattern_cv = 'custom_variables';

        foreach ($posted_data as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $new_key = str_replace($prefix, $pattern_cv, $key);
                if (!empty($value)) {
                    if (is_array($value)) {
                        if (!empty($value[0])) {
                            if (count($value) > 1) {
                                $custom_var[$new_key] = array_values($value);
                            } else {
                                $custom_var[$new_key] = $value[0];
                            }
                        }
                    } else {
                        $custom_var[$new_key] = $value;
                    }
                }
            }
        }

        $name = isset($posted_data["firstname"]) ? $posted_data["firstname"] : null;
        $surname = isset($posted_data["lastname"]) ? $posted_data["lastname"] : null;
        $message = isset($posted_data["message"]) ? $posted_data["message"] : null;
        $email2 = isset($posted_data["email2"]) ? trim(strtolower($posted_data["email2"])) : null;
        $companyname = isset($posted_data["companyname"]) ? $posted_data["companyname"] : null;
        $telephone1 = isset($posted_data["telephone1"]) ? $posted_data["telephone1"] : null;
        $telephone2 = isset($posted_data["telephone2"]) ? $posted_data["telephone2"] : null;
        $mobilephone1 = isset($posted_data["mobilephone1"]) ? $posted_data["mobilephone1"] : null;
        $mobilephone2 = isset($posted_data["mobilephone2"]) ? $posted_data["mobilephone2"] : null;
        $address = isset($posted_data["address"]) ? $posted_data["address"] : null;
        $province = (isset($province) && isset($nation)) ? $province : null;
        $nation = isset($nation) ? $nation : null;
        $zone = isset($posted_data["zone"]) ? $posted_data["zone"] : null;
        $city = isset($city) ? $city : null;
        $postalcode = isset($posted_data["postalcode"]) ? $posted_data["postalcode"] : null;
        $vatcode = isset($posted_data["vatcode"]) ? $posted_data["vatcode"] : null;
        $fiscalcode = isset($posted_data["fiscalcode"]) ? $posted_data["fiscalcode"] : null;
        $gender = isset($posted_data["gender"]) ? $this->suiteWrGender($posted_data["gender"]) : null;
        $birthdaydate = isset($posted_data["birthdaydate"]) ? $posted_data["birthdaydate"] : null;
        $birthplace = isset($posted_data["birthplace"]) ? $posted_data["birthplace"] : null;
        $website = isset($posted_data["website"]) ? $posted_data["website"] : null;
        $linkedin = isset($posted_data["linkedin"]) ? $posted_data["linkedin"] : null;
        $twitter = isset($posted_data["twitter"]) ? $posted_data["twitter"] : null;
        $facebook = isset($posted_data["facebook"]) ? $posted_data["facebook"] : null;
        $instagram = isset($posted_data["instagram"]) ? $posted_data["instagram"] : null;
        $skype = isset($posted_data["skype"]) ? $posted_data["skype"] : null;
        $tag = $wrtag;
        $note = isset($posted_data["note"]) ? $posted_data["note"] : null;

        /* if (empty($message) || $wrTicket == false) {
             $call = "WordpressNewsletterConnector";
         } else {
             $call = "WordpressContactConnector";
             if ($title == "") {
                 $title = "Contact Form ";
             }
             $title .= " from site " . site_url() . " by " . $email;
         }

        if ($title == "") {
                $title = "Contact Form ";
            }
            $title .= " from site " . site_url() . " by " . $email;

        */

        $event_data = array();
        $event_data = array_merge($event_data, $custom_var);

        if (empty($message) || $wrTicket == false) {
            $event_data['ticket'] = 0;
        } else {
            $event_data['ticket'] = 1;
            $event_data['message'] = $message;
            if ($title == "") {
                $title = "Contact Form ";
            }
            $title .= " from site " . site_url() . " by " . $email;

            $event_data['title'] = $title;
        }

        if (!empty($birthdaydate)) {
            $birthdaydate = (date('Y-m-d', strtotime($birthdaydate)) === $birthdaydate) ? $birthdaydate : null;
        }

        $event_data['email'] = trim(strtolower($email));
        $event_data['email2'] = $email2;
        $event_data['companyname'] = $companyname;
        $event_data['name'] = $name;
        $event_data['surname'] = $surname;
        $event_data['telephone1'] = $telephone1;
        $event_data['telephone2'] = $telephone2;
        $event_data['mobilephone1'] = $mobilephone1;
        $event_data['mobile2'] = $mobilephone2;
        $event_data['address'] = $address;
        $event_data['province'] = $province;
        $event_data['nation'] = $nation;
        $event_data['zone'] = $zone;
        $event_data['city'] = $city;
        $event_data['postalcode'] = $postalcode;
        $event_data['vatCode'] = $vatcode;
        $event_data['fiscalcode'] = $fiscalcode;
        $event_data['site_name'] = site_url();
        $event_data['url'] = $_SERVER['REQUEST_URI'];
        $event_data['referer'] = $_SERVER['HTTP_REFERER'];
        $event_data['date_add'] = get_date_from_gmt(date('Y-m-d H:i:s'));
        $event_data['timezone'] = WHITERABBIT_TIMEZONE;
        $event_data['gender'] = $gender;
        $event_data['birthdaydate'] = $birthdaydate;
        $event_data['birthplace'] = $birthplace;
        $event_data['website'] = $website;
        $event_data['linkedin'] = $linkedin;
        $event_data['twitter'] = $twitter;
        $event_data['facebook'] = $facebook;
        $event_data['instagram'] = $instagram;
        $event_data['skype'] = $skype;
        $event_data['tag'] = $tag;
        $event_data['note'] = $note;


        $data = array(
            "connector" => "WordpressConnector",
            "channel" => "WordpressContactConnector",
            "operation" => "site_submitform",
            "data" => json_encode($event_data),
            "token" => $result['token'],
        );

        $data_string = http_build_query($data);

        $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);
        $response_data = json_decode($response);

        $results = array(
            //"result" => !$response_data->error,
            "result" => isset($response_data->error) ? !$response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response,
        );
        $this->whiterabbit_crm_insert_user_gdpr($posted_data);
        return $results;
    }

    public function whiterabbit_crm_insert_user_newsletter_form($fields, $entry, $form_data, $entry_id){

        $wrTicket = true;
        $wrtag = null;
        $full_address = null;
        if ($this->_wrTicket == "" || $this->_wrTicket == "off") {
            $wrTicket = false;
        }

        $result = $this->connect();

        $email = !empty($fields[$form_data['settings']['whiterabbit_field_email']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_email']]['value'] : null;

        if (empty($email)) {
            $results = array(
                //"result" => !$response_data->error,
                "result" => false,
                "token" => "",
                "error" => "true",
                "error_message" => "No Email"
            );
            return $results;
        }

        if (!empty($fields[$form_data['settings']['whiterabbit_field_tag']]['value'])) {
            $wrtag = $fields[$form_data['settings']['whiterabbit_field_tag']]['value'];
        }

        if (!empty($fields[$form_data['settings']['whiterabbit_field_address']]['address1'])) {
            $full_address = $fields[$form_data['settings']['whiterabbit_field_address']]['address1'];
        }

        if (!empty($fields[$form_data['settings']['whiterabbit_field_address']]['address2'])) {
            if ($full_address != null) {
                $full_address .= " ";
            }

            $full_address .= $fields[$form_data['settings']['whiterabbit_field_address']]['address2'];
        }

        if ($full_address == null && !empty($fields[$form_data['settings']['whiterabbit_field_address']]['value'])) {
            $full_address = $fields[$form_data['settings']['whiterabbit_field_address']]['value'];
        }

        $name = !empty($fields[$form_data['settings']['whiterabbit_field_firstname']]['first']) ? $fields[$form_data['settings']['whiterabbit_field_firstname']]['first'] : (!empty($fields[$form_data['settings']['whiterabbit_field_firstname']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_firstname']]['value'] : null);
        $surname = !empty($fields[$form_data['settings']['whiterabbit_field_lastname']]['last']) ? $fields[$form_data['settings']['whiterabbit_field_lastname']]['last'] : (!empty($fields[$form_data['settings']['whiterabbit_field_lastname']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_lastname']]['value'] : null);
        $message = !empty($fields[$form_data['settings']['whiterabbit_field_message']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_message']]['value'] : null;
        $email2 = !empty($fields[$form_data['settings']['whiterabbit_field_email2']]['value']) ? trim(strtolower($fields[$form_data['settings']['whiterabbit_field_email2']]['value'])) : null;
        $companyname = !empty($fields[$form_data['settings']['whiterabbit_field_companyname']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_companyname']]['value'] : null;
        $telephone1 = !empty($fields[$form_data['settings']['whiterabbit_field_telephone1']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_telephone1']]['value'] : null;
        $telephone2 = !empty($fields[$form_data['settings']['whiterabbit_field_telephone2']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_telephone2']]['value'] : null;
        $mobilephone1 = !empty($fields[$form_data['settings']['whiterabbit_field_mobilephone1']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_mobilephone1']]['value'] : null;
        $mobilephone2 = !empty($fields[$form_data['settings']['whiterabbit_field_mobilephone2']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_mobilephone2']]['value'] : null;
        $address = $full_address;
        $province = (!empty($fields[$form_data['settings']['whiterabbit_field_province']]['state']) && !empty($fields[$form_data['settings']['whiterabbit_field_nation']]['country'])) ? $fields[$form_data['settings']['whiterabbit_field_province']]['state'] : null;
        $nation = !empty($fields[$form_data['settings']['whiterabbit_field_nation']]['country']) ? $fields[$form_data['settings']['whiterabbit_field_nation']]['country'] : null;
        $zone = !empty($fields[$form_data['settings']['whiterabbit_field_zone']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_zone']]['value'] : null;
        $city = !empty($fields[$form_data['settings']['whiterabbit_field_city']]['city']) ? $fields[$form_data['settings']['whiterabbit_field_city']]['city'] : (!empty($fields[$form_data['settings']['whiterabbit_field_city']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_city']]['value'] : null);
        $postalcode = !empty($fields[$form_data['settings']['whiterabbit_field_postalcode']]['postal']) ? $fields[$form_data['settings']['whiterabbit_field_postalcode']]['postal'] : (!empty($fields[$form_data['settings']['whiterabbit_field_postalcode']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_postalcode']]['value'] : null);
        $vatcode = !empty($fields[$form_data['settings']['whiterabbit_field_vatcode']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_vatcode']]['value'] : null;
        $fiscalcode = !empty($fields[$form_data['settings']['whiterabbit_field_fiscalcode']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_fiscalcode']]['value'] : null;
        $gender = !empty($fields[$form_data['settings']['whiterabbit_field_gender']]['value']) ? $this->suiteWrGender($fields[$form_data['settings']['whiterabbit_field_gender']]['value']) : null;
        $birthdaydate = !empty($fields[$form_data['settings']['whiterabbit_field_birthdaydate']]['date']) ? $fields[$form_data['settings']['whiterabbit_field_birthdaydate']]['date'] : null;
        $birthplace = !empty($fields[$form_data['settings']['whiterabbit_field_birthplace']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_birthplace']]['value'] : null;
        $website = !empty($fields[$form_data['settings']['whiterabbit_field_website']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_website']]['value'] : null;
        $linkedin = !empty($fields[$form_data['settings']['whiterabbit_field_linkedin']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_linkedin']]['value'] : null;
        $twitter = !empty($fields[$form_data['settings']['whiterabbit_field_twitter']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_twitter']]['value'] : null;
        $facebook = !empty($fields[$form_data['settings']['whiterabbit_field_facebook']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_facebook']]['value'] : null;
        $instagram = !empty($fields[$form_data['settings']['whiterabbit_field_instagram']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_instagram']]['value'] : null;
        $skype = !empty($fields[$form_data['settings']['whiterabbit_field_skype']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_skype']]['value'] : null;
        $tag = $wrtag;
        $note = !empty($fields[$form_data['settings']['whiterabbit_field_note']]['value']) ? $fields[$form_data['settings']['whiterabbit_field_note']]['value'] : null;

        $event_data = array();

        if (empty($message) || $wrTicket == false) {
            $event_data['ticket'] = 0;
        } else {
            $event_data['ticket'] = 1;
            $event_data['message'] = $message;
            if (empty($title)) {
                $title = "Contact Form ";
            }
            $title .= " from site " . site_url() . " by " . $email;

            $event_data['title'] = $title;
        }

        if (!empty($birthdaydate)) {
            $birthdaydate = (date('Y-m-d', strtotime($birthdaydate)) === $birthdaydate) ? $birthdaydate : null;
        }

        $event_data['email'] = trim(strtolower($email));
        $event_data['email2'] = $email2;
        $event_data['companyname'] = $companyname;
        $event_data['name'] = $name;
        $event_data['surname'] = $surname;
        $event_data['telephone1'] = $telephone1;
        $event_data['telephone2'] = $telephone2;
        $event_data['mobilephone1'] = $mobilephone1;
        $event_data['mobile2'] = $mobilephone2;
        $event_data['address'] = $address;
        $event_data['province'] = $province;
        $event_data['nation'] = $nation;
        $event_data['zone'] = $zone;
        $event_data['city'] = $city;
        $event_data['postalcode'] = $postalcode;
        $event_data['vatCode'] = $vatcode;
        $event_data['fiscalcode'] = $fiscalcode;
        $event_data['site_name'] = site_url();
        $event_data['url'] = $_SERVER['REQUEST_URI'];
        $event_data['referer'] = $_SERVER['HTTP_REFERER'];
        $event_data['date_add'] = get_date_from_gmt(date('Y-m-d H:i:s'));
        $event_data['timezone'] = WHITERABBIT_TIMEZONE;
        $event_data['gender'] = $gender;
        $event_data['birthdaydate'] = $birthdaydate;
        $event_data['birthplace'] = $birthplace;
        $event_data['website'] = $website;
        $event_data['linkedin'] = $linkedin;
        $event_data['twitter'] = $twitter;
        $event_data['facebook'] = $facebook;
        $event_data['instagram'] = $instagram;
        $event_data['skype'] = $skype;
        $event_data['tag'] = $tag;
        $event_data['note'] = $note;


        $data = array(
            "connector" => "WordpressConnector",
            "channel" => "WordpressContactConnector",
            "operation" => "site_submitform",
            "data" => json_encode($event_data),
            "token" => $result['token'],
        );

        $data_string = http_build_query($data);

        $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);
        $response_data = json_decode($response);

        $results = array(
            //"result" => !$response_data->error,
            "result" => isset($response_data->error) ? !$response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response,
        );

        $this->whiterabbit_crm_insert_form_gdpr($fields, $form_data);
        return $results;
    }

    public function whiterabbit_crm_insert_form_gdpr($fields, $form_data)
    {
        $token = get_option('wr_plugin_wordpress_suite_operation_token');

        $email = trim(strtolower($fields[$form_data['settings']['whiterabbit_field_email']]['value']));

        $companyname = !empty($fields[$form_data['settings']['whiterabbit_field_companyname']]['value']) ? trim(strtolower($fields[$form_data['settings']['whiterabbit_field_companyname']]['value'])) : null;

        $date = (!empty($companyname)) ? get_date_from_gmt(date('Y-m-d H:i:s', strtotime('+5 minutes'))) : get_date_from_gmt(date('Y-m-d H:i:s'));

        if (empty($email)) {
            $results = array(
                //"result" => !$response_data->error,
                "result" => false,
                "token" => "",
                "error" => "true",
                "error_message" => "No Email"
            );
            return $results;
        }

        $headers = array(
            "Content-Type" => " application/json",
            "Authorization" => "Bearer " . $token,
            "Accept:application/json",
        );

        if (get_option('wr_general_enable_gdpr_marketing') == 'on' && empty($form_data['settings']['whiterabbit_field_marketing'])) {

            $data = array(
                "email" => $email,
                "gdpr_marketing" => array(
                    "value" => 1,
                    "date" => $date,
                    "detail" => json_encode($this->serverDetail($_SERVER)),
                ),
                "timezone" => WHITERABBIT_TIMEZONE
            );

            $data_string = json_encode($data);

            $response = $this->serverPost($this->_wrApiPath . self::WR_SET_SUITE_GDPR_PATH, $data_string, $headers);
            $response_data = json_decode($response);

            $results = array(
                //"result" => !$response_data->error,
                "result" => isset($response_data->error) ? !$response_data->error : "true",
                "token" => isset($response_data->token) ? $response_data->token : "",
                "error" => isset($response_data->error) ? "true" : "false",
                "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
                "error_text" => $response,
            );
            return $results;
        }

        $data['email'] = $email;

        if (!empty($form_data['settings']['whiterabbit_field_marketing'])) {
            $data['gdpr_marketing']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_marketing']]['value']) ? 1 : 0;
            $data['gdpr_marketing']['date'] = $date;
            $data['gdpr_marketing']['detail'] = $this->serverDetail($_SERVER);

        }


        if (!empty($form_data['settings']['whiterabbit_field_profiling'])) {
            $data['gdpr_profiling']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_profiling']]['value']) ? 1 : 0;
            $data['gdpr_profiling']['date'] = $date;
            $data['gdpr_profiling']['detail'] = $this->serverDetail($_SERVER);
        }


        if (!empty($form_data['settings']['whiterabbit_field_thirdparties'])) {
            $data['gdpr_thirdparties']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_thirdparties']]['value']) ? 1 : 0;
            $data['gdpr_thirdparties']['date'] = $date;
            $data['gdpr_thirdparties']['detail'] = $this->serverDetail($_SERVER);
        }


        if (!empty($form_data['settings']['whiterabbit_field_outsideeu'])) {
            $data['gdpr_outsideeu']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_outsideeu']]['value']) ? 1 : 0;
            $data['gdpr_outsideeu']['date'] = $date;
            $data['gdpr_outsideeu']['detail'] = $this->serverDetail($_SERVER);
        }

        if (!empty($form_data['settings']['whiterabbit_field_collection'])) {
            $data['gdpr_collection']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_collection']]['value']) ? 1 : 0;
            $data['gdpr_collection']['date'] = $date;
            $data['gdpr_collection']['detail'] = $this->serverDetail($_SERVER);

        }

        if (!empty($form_data['settings']['whiterabbit_field_other1'])) {
            $data['gdpr_other1']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_other1']]['value']) ? 1 : 0;
            $data['gdpr_other1']['date'] = $date;
            $data['gdpr_other1']['detail'] = $this->serverDetail($_SERVER);
        }

        if (!empty($form_data['settings']['whiterabbit_field_other2'])) {
            $data['gdpr_other2']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_other2']]['value']) ? 1 : 0;
            $data['gdpr_other2']['date'] = $date;
            $data['gdpr_other2']['detail'] = $this->serverDetail($_SERVER);
        }

        if (!empty($form_data['settings']['whiterabbit_field_other3'])) {
            $data['gdpr_other3']['value'] = !empty($fields[$form_data['settings']['whiterabbit_field_other3']]['value']) ? 1 : 0;
            $data['gdpr_other3']['date'] = $date;
            $data['gdpr_other3']['detail'] = $this->serverDetail($_SERVER);
        }

        $data['timezone'] = WHITERABBIT_TIMEZONE;


        $data_string = json_encode($data);


        $response = $this->serverPost($this->_wrApiPath . self::WR_SET_SUITE_GDPR_PATH, $data_string, $headers);
        $response_data = json_decode($response);

        $results = array(
            "result" => isset($response_data->error) ? $response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response,
        );

        return $results;
    }


    public function whiterabbit_crm_insert_user_gdpr($posted_data)
    {
        $token = get_option('wr_plugin_wordpress_suite_operation_token');

        $email = trim(strtolower($posted_data["email"]));

        $companyname = isset($posted_data["companyname"]) ? trim(strtolower($posted_data["companyname"])) : null;

        $date = (!empty($companyname)) ? get_date_from_gmt(date('Y-m-d H:i:s', strtotime('+5 minutes'))) : get_date_from_gmt(date('Y-m-d H:i:s'));

        if (empty($email)) {
            $email = isset($posted_data["your-email"]) ? trim(strtolower($posted_data["your-email"])) : null;
        }

        if (empty($email)) {
            $results = array(
                //"result" => !$response_data->error,
                "result" => false,
                "token" => "",
                "error" => "true",
                "error_message" => "No Email"
            );
            return $results;
        }

        if (!empty($posted_data['date'])) {
            $date = $posted_data['date'];
        }

        $headers = array(
            "Content-Type" => " application/json",
            "Authorization" => "Bearer " . $token,
            "Accept:application/json",
        );

        if (get_option('wr_general_enable_gdpr_marketing') == 'on' && empty($posted_data["marketing"])) {

            $data = array(
                "email" => $email,
                "gdpr_marketing" => array(
                    "value" => 1,
                    "date" => $date,
                    "detail" => json_encode($this->serverDetail($_SERVER)),
                ),
                'timezone' => WHITERABBIT_TIMEZONE
            );

            $data_string = json_encode($data);

            $response = $this->serverPost($this->_wrApiPath . self::WR_SET_SUITE_GDPR_PATH, $data_string, $headers);
            $response_data = json_decode($response);

            $results = array(
                //"result" => !$response_data->error,
                "result" => isset($response_data->error) ? !$response_data->error : "true",
                "token" => isset($response_data->token) ? $response_data->token : "",
                "error" => isset($response_data->error) ? "true" : "false",
                "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
                "error_text" => $response,
            );
            return $results;
        }

        $data['email'] = $email;

        if (empty(array_intersect_key($posted_data, array_flip(self::WR_GENERAL_PURPOSE_GDPR)))) {
            return false;
        }

        foreach (self::WR_GENERAL_PURPOSE_GDPR as $purpose) {
            if (!empty($posted_data[$purpose])) {
                if (is_array($posted_data[$purpose])) {
                    $choice = $posted_data[$purpose][0];
                } else {
                    $choice = $posted_data[$purpose];
                }
                $gdpr_purpose_key = 'gdpr_' . $purpose;
                $data[$gdpr_purpose_key]['value'] = $choice != "" ? 1 : 0;
                $data[$gdpr_purpose_key]['date'] = $date;
                $data[$gdpr_purpose_key]['detail'] = $this->serverDetail($_SERVER);
            }
        }

        $data['timezone'] = WHITERABBIT_TIMEZONE;


        $data_string = json_encode($data);


        $response = $this->serverPost($this->_wrApiPath . self::WR_SET_SUITE_GDPR_PATH, $data_string, $headers);
        $response_data = json_decode($response);

        $results = array(
            "result" => isset($response_data->error) ? $response_data->error : "true",
            "token" => isset($response_data->token) ? $response_data->token : "",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response,
        );

        return $results;
    }

    public function whiterabbit_crm_insert_user($userId, $connect = Null)
    {
        $response_data = array();
        if (!empty($userId)) {
            $user_meta = get_user_meta($userId);
            $author_obj = get_user_by('id', $userId);
            $surname = !(empty($user_meta['last_name'][0])) ? $user_meta['last_name'][0] : null;
            $name = !(empty($user_meta['first_name'][0])) ? $user_meta['first_name'][0] : null;

            /*   if ($author_obj->roles[0] != "customer") {
                   return;
               }*/

            if ($connect) {
                $result = $connect;
            } else {
                $result = $this->connect();
            }

            if (isset($_POST['email'])) {
                $email = sanitize_email($_POST['email']);
                $email_array = explode("@", sanitize_email($_POST['email']));
            } else {
                if (isset($_POST['billing_email'])) {
                    $email = sanitize_email($_POST['billing_email']);
                    $email_array = explode("@", sanitize_email($_POST['billing_email']));
                } else {
                    if (isset($author_obj->user_email)) {
                        $email = sanitize_email($author_obj->user_email);
                        $email_array = explode("@", sanitize_email($email));
                    }

                }
            }


            $gender = null;
            $birthdaydate = null;

            if ($user_meta['billing_gender'] != null) {
                $gender = $this->suiteWrGender($user_meta['billing_gender']);
            }

            if ($user_meta['billing_datebirth'] != null) {
                $temp = explode("/", $user_meta['billing_datebirth']);
                $birthdaydate = $temp[2] . "-" . $temp[1] . "-" . $temp[0];
            }

            $gdpr_choices = null;
            //meta from Woocommerce for GDPR Purpose
            foreach (self::WR_GENERAL_PURPOSE_GDPR as $purpose) {
                if (!empty($_POST[$purpose])) {
                    $gdpr_choices[$purpose] = $_POST[$purpose];
                }
            }

            //format data with timezone from UTC to local-site
            $data_registration = null;
            if (isset($author_obj->data->user_registered)) {
                $data_user_registration = new DateTime($author_obj->data->user_registered, new DateTimeZone('UTC'));
                $data_user_registration->setTimezone(new DateTimeZone(WHITERABBIT_TIMEZONE));
                $data_registration = $data_user_registration->format('Y-m-d H:i:s');
            }

            $event_data = array("email" => strtolower($email),
                "name" => $name,
                "surname" => $surname,
                "newsletter_subscription_date" => date('Y-m-d H:i:s'),
                "newsletter_subscription_ip" => $_SERVER['SERVER_ADDR'],
                //"operation" => "adduser",
                "date" => (isset($data_registration)) ? $data_registration : null,
                "site_name" => site_url(),
                "timezone" => WHITERABBIT_TIMEZONE,
                "crm_push_async" => ($connect != Null ? true : false),
                "gender" => $gender,
                "birthdaydate" => $birthdaydate,
            );

            $data = array(
                "connector" => "WordpressConnector",
                "channel" => "WordpressEcommerceConnector",
                "operation" => "add_user", // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
                "data" => json_encode($event_data), // Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB),
                "token" => $result['token'],
            );

            $data_string = http_build_query($data);
            /*$data_string = json_encode($data);

            $fields_string = "";
            foreach ($data as $key => $value) {
                $fields_string .= $key . '=' . $value . '&';
            }*/

            $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);
            $response_data = json_decode($response);

            $gdpr = array();
            $gdpr['email'] = strtolower($email);
            $gdpr['date'] = (isset($data_registration)) ? $data_registration : get_date_from_gmt(date('Y-m-d H:i:s'));
            $gdpr = (!empty($gdpr_choices)) ? array_merge($gdpr, $gdpr_choices) : $gdpr;

            $this->whiterabbit_crm_insert_user_gdpr($gdpr);

            if ($response_data->error == false) {
                $this->whiterabbit_insert_order_user(0, $userId);
            }
            return $response_data;
        }
        return $response_data;
    }


    public function getSuiteGDPR($token, $customerID)
    {
        $url = $this->_wrApiPath . self::WR_GET_SUITE_GDPR_PATH;
        $headers = array(
            "Content-Type" => " application/json",
            "Authorization" => "Bearer " . $token,
            "Accept:application/json",
        );
        $data = array(
            "customerID" => $customerID
        );

        $fields_string = json_encode($data);


        $response = $this->serverPost($url, $fields_string, $headers);

        $response_data = json_decode($response);

        if ($response_data == null) {
            $results['error'] = "true";
            return $results;
        }

        if (isset($response_data->error)) {
            if ($response_data->error == true) {
                $results['error'] = "true";
                return $results;
            }

        }

        $results['error'] = "false";
        $results['result'] = $response_data;

        return $results;

    }

    public function whiterabbit_crm_insert_user_from_order($order_id, $connect = Null)
    {
        $results = array();

        $order = new WC_Order($order_id);

        $userId = $order->get_user_id();
        $author_obj = get_user_by('id', $userId);

        $user_meta = get_user_meta($userId);
        /*
           if ($author_obj->roles[0] != "customer") {
            return;
        }
        */
        if ($connect) {
            $result = $connect;
        } else {
            $result = $this->connect();
        }

        if (isset($_POST['email'])) {
            $email = sanitize_email($_POST['email']);
            $email_array = explode("@", sanitize_email($_POST['email']));
        } else {
            if (isset($_POST['billing_email'])) {
                $email = sanitize_email($_POST['billing_email']);
                $email_array = explode("@", sanitize_email($_POST['billing_email']));
            } else {
                if (isset($author_obj->data->user_email)) {
                    $email = sanitize_email($author_obj->data->user_email);
                    $email_array = explode("@", sanitize_email($email));
                }
            }
        }

        if (isset($user_meta)) {
            if (!empty($user_meta['billing_phone'][0])) {
                $telephone = $user_meta['billing_phone'][0];
            }
        }

        $telephone = "";
        if (!empty($order->get_billing_phone())) {
            $telephone = $order->get_billing_phone();
        }

        if (empty($email)) {
            $email = $order->get_billing_email();
        }

        $gender = null;
        $birthdaydate = null;

        $gender = null;
        $birthdaydate = null;

        //meta from Woocommerce using Flexible Checkout Fields
        if (!empty($order->get_meta('_billing_gender'))) {
            $gender = $this->suiteWrGender($order->get_meta('_billing_gender'));
        }
        if (!empty($order->get_meta('_billing_datebirth'))) {
            $birthdaydate = $order->get_meta('_billing_datebirth');
            $birthdaydate = (date('Y-m-d', strtotime($birthdaydate)) === $birthdaydate) ? $birthdaydate : null;
        }

        $gdpr_choices = null;
        //meta from Woocommerce for GDPR Purpose
        foreach (self::WR_GENERAL_PURPOSE_GDPR as $purpose) {
            if (!empty($_POST[$purpose])) {
                $gdpr_choices[$purpose] = $_POST[$purpose];
            }
        }

        //format data with timezone from UTC to local-site
        $data_registration = null;
        if (isset($author_obj->data->user_registered)) {
            $data_user_registration = new DateTime($author_obj->data->user_registered, new DateTimeZone('UTC'));
            $data_user_registration->setTimezone(new DateTimeZone(WHITERABBIT_TIMEZONE));
            $data_registration = $data_user_registration->format('Y-m-d H:i:s');
        }

        $event_data = array("email" => strtolower($email),
            "name" => ($user_meta != Null ? $user_meta['billing_first_name'][0] : $order->get_billing_first_name()),
            "surname" => ($user_meta != Null ? $user_meta['billing_last_name'][0] : $order->get_billing_last_name()),
            "address" => ($user_meta != Null ? $user_meta['billing_address_1'][0] : $order->get_billing_address_1()),
            "city" => ($user_meta != Null ? $user_meta['billing_city'][0] : $order->get_billing_city()),
            "nation" => ($user_meta != Null ? $user_meta['billing_country'][0] : $order->get_billing_country()),
            "province" => ($user_meta != Null ? $user_meta['billing_state'][0] : $order->get_billing_state()),
            "postalcode" => ($user_meta != Null ? $user_meta['billing_postcode'][0] : $order->get_billing_postcode()),
            // "telephone1" => ($user_meta != Null ? $user_meta['billing_phone'][0] : $order->data['billing']['phone']),
            "telephone1" => $telephone,
            "newsletter_subscription_date" => $order->get_date_created()->format('Y-m-d H:i:s'),
            "date" => (isset($data_registration)) ? $data_registration : $order->get_date_created()->format(\DateTime::ATOM),
            "newsletter_subscription_ip" => $_SERVER['SERVER_ADDR'],
            "site_name" => site_url(),
            "timezone" => WHITERABBIT_TIMEZONE,
            //"crm_push_async" => ($connect != Null ? true : false),
            "crm_push_async" => false,
            "gender" => $gender,
            "birthdaydate" => $birthdaydate,
        );

        $data = array(
            "connector" => "WordpressConnector",
            "channel" => "WordpressEcommerceConnector",
            "operation" => "add_user",
            "data" => json_encode($event_data),
            "token" => $result['token'],
        );

        $data_string = http_build_query($data);

        $response = $this->serverPost($this->_wrApiPath . self::WR_API_SET_EVENT, $data_string);

        $response_data = json_decode($response);
        $gdpr = array();
        //$gdpr['email'] = $email != Null ? $email : $order->data['billing']['email'];
        $gdpr['email'] = $email != Null ? $email : $order->get_billing_email();
        $gdpr['date'] = (isset($data_registration)) ? $data_registration : (!empty($order->get_date_created()->format('Y-m-d H:i:s')) ? $order->get_date_created()->format('Y-m-d H:i:s') : get_date_from_gmt(date('Y-m-d H:i:s')));
        $gdpr = (!empty($gdpr_choices)) ? array_merge($gdpr, $gdpr_choices) : $gdpr;

        $this->whiterabbit_crm_insert_user_gdpr($gdpr);

        if ($response_data->error == false) {
            $this->whiterabbit_insert_order_user(0, $userId);
        }


        return $results;

    }

    public function getSiteInfo($token = null)
    {
        $apipath = $this->_wrApiPath;
        // Mage::log("[WR Connector - SiteInfo] apiPath: $apipath");

        //if (!empty($this->_overrideConfig)) {
        //    $apipath = $this->_wrApiPath; // $this->_overrideConfig['server/apipath'];
        //}

        //Mage::log("[WR Connector - SiteInfo] Token: $wr_token / site_id: $wr_siteid");

        $data = array(
            "connector" => "WordPressConnector",
            "token" => $token
        );


        $data_string = json_encode($data);

        $fields_string = "";
        foreach ($data as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        $response = $this->serverPost($apipath . self::WR_API_GET_INFO, $fields_string);
        $response_data = json_decode($response);
        $results = array(
            //"result" => !$response_data->error,
            "result" => isset($response_data->error) ? !$response_data->error : "true",
            "error" => isset($response_data->error) ? "true" : "false",
            "error_message" => isset($response_data->error_message) ? $response_data->error_message : "",
            "error_text" => $response
        );
        if (!empty($response_data)) {

            foreach ($response_data as $key => $val) {
                // Mage::log("[WR Connector - SiteInfo] Data Received: $key = $val");
                if ($key != 'error' && $key != 'error_message') {
                    $results[$key] = $val;
                }
            }
        }


        return $results;

    }

    private function serverPost($url, $post, $headers = array())
    {
        // Mage::log("[WR Connector - Server Post] Url: " . $url);
        // Mage::log("[WR Connector - Server Post] Json: " . $post);
        $error['error'] = false;
        $args = array(
            'timeout' => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            //'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array(),
            'compress' => false,
            'decompress' => true,
            'sslverify' => false,
            'SSLVERSION' => 3,
            'stream' => false,
            'filename' => null,
            'body' => $post,
        );

        $response = wp_remote_post($url, $args);

        /*if($url=='https://suite.whiterabbitsuite.com/api_connector/set_event/'){
            echo "<pre>";
            var_dump($post);
            die;
        }*/
        /*var_dump($response);
        die;*/
        if (is_array($response)) {
            $header = $response['headers']; // array of http header lines
            $body = $response['body']; // use the content
        }

        // Mage::log("[WR Connector - Server Post] Results: " . $response);
        if (is_wp_error($response)) {
            $error['error'] = true;
            $error['error_message'] = $response->get_error_message();
        }

        if ($error['error'] == true) {
            return json_encode($error);
        }
        return $body;
    }


    public function whiterabbit_import_orders($connect)
    {

        $params = array();
        if ($this->wooCommerceVersionCheck() == false) {
            return;
        }

        /*$sql = " SELECT * FROM " . $wpdb->prefix . "users WHERE ID NOT IN
                (SELECT user_id FROM " . $wpdb->prefix . "whiterabbit_import_data)";
        $userId = $row->ID;

            $author_obj = get_user_by('id', $userId);
            $user_meta = get_user_meta($userId);

            if ($author_obj->roles[0] != "customer") {
                continue;
            }

        */
        $results = $this->whiterabbit_find_order_import();

        $userArray = array();
        foreach ($results as $id => $row) {
            try {

                $order_id = $row->order_id;
                $order = new WC_Order($order_id);
                try {
                    $userId = $order->get_user_id();

                } catch (\Exception $ex) {
                    echo $ex->getMessage();
                }


                if ($userId == "" || $userId == null) {
                    $userId = 0;
                }

                /*$author_obj = get_user_by('id', $userId);
                  if ($author_obj->roles[0] != "customer") {
                      continue;
                  }

                  if ($userId == 0) {
                      continue;
                  }*/

                $userArray[$userId][] = $order_id;
            } catch (\Exception $ex) {
                echo $ex->getMessage();
                continue;
            }
        }
        // ksort($userArray);


        foreach ($userArray as $user_id => $row) {
            foreach ($row as $n => $order_id) {

                if ($n == 0 || $user_id == 0) {
                    $order = new WC_Order($order_id);
                    $userId = $order->get_user_id();

                    if (count($this->whiterabbit_find_user($userId)) == 0 || $userId == 0) {
                        $this->whiterabbit_crm_insert_user_from_order($order_id, $connect);
                    }
                }

                $this->whiterabbit_crm_update_order($order_id, "Not Imported", $order->get_status(), $connect);
            }
        }

        $results = $this->whiterabbit_find_user_import();

        foreach ($results as $row) {
            $this->whiterabbit_crm_insert_user($row->ID, $connect);
        }
    }

    public function whiterabbit_save_abandoned_cart()
    {
        if ($this->wpCronCheck()) {
            if ($this->wr_is_bot() || (is_admin() && !is_ajax()) || current_user_can('manage_options')) {
                return;
            }

            //save cart for logged user
            if (is_user_logged_in()) {
                $check_insert = false;
                $cart_count = WC()->cart->get_cart_contents_count();
                $wr_abdc_data = $cart_count ? json_encode(array('cart' => WC()->session->cart, 'currency' => get_woocommerce_currency())) : '';

                $wr_abdc_id = WC()->session->get('whiterabbit_cart_record_id');
                $user_id = get_current_user_id();
                $db = new wrDatabase();

                if ($wr_abdc_id) {
                    $result = $db->get_wr_abdc_cart_record($wr_abdc_id);

                    if ($result) {
                        if ($wr_abdc_data) {
                            $db->wr_update_abd_cart_record(
                                array(
                                    'abandoned_cart_info' => $wr_abdc_data,
                                    'abandoned_cart_time' => WHITERABBIT_CURRENT_TIME,
                                    'user_id' => $user_id,
                                    'detail' => json_encode($_REQUEST)
                                ),
                                array('whiterabbit_abandoned_cart_id' => $wr_abdc_id));
                        } else {
                            $db->wr_remove_abd_cart_record($wr_abdc_id);
                        }
                    } else {
                        $check_insert = true;
                    }
                } else {
                    //woocommerce session expire after 2 days
                    if ($user_id) {
                        $result = $db->get_wr_abdc_cart_record_by_user($user_id);
                        if (isset($result->whiterabbit_abandoned_cart_id)) {
                            if ($wr_abdc_data) {
                                $db->wr_update_abd_cart_record(
                                    array(
                                        'abandoned_cart_info' => $wr_abdc_data,
                                        'abandoned_cart_time' => WHITERABBIT_CURRENT_TIME,
                                        'user_id' => $user_id,
                                        'detail' => json_encode($_REQUEST)
                                    ),
                                    array('whiterabbit_abandoned_cart_id' => $result->whiterabbit_abandoned_cart_id));

                                WC()->session->set('whiterabbit_cart_record_id', $result->whiterabbit_abandoned_cart_id);
                            } else {
                                $db->wr_remove_abd_cart_record($result->whiterabbit_abandoned_cart_id);
                            }
                        } else {
                            $check_insert = true;
                        }
                    } else {
                        $check_insert = true;
                    }
                }

                if ($check_insert && $wr_abdc_data) {
                    if ($user_id) {
                        $db->wr_remove_abd_cart_record_by_user_id($user_id);
                    }
                    $insert_id = $db->wr_insert_abd_cart_record(array(
                        'user_id' => $user_id,
                        'abandoned_cart_info' => $wr_abdc_data,
                        'abandoned_cart_time' => WHITERABBIT_CURRENT_TIME,
                        'detail' => json_encode($_REQUEST)
                    ));

                    WC()->session->set('whiterabbit_cart_record_id', $insert_id);
                }
            }
        }
    }

    public function whiterabbit_remove_abandoned_cart() {
        $id = WC()->session ? WC()->session->get('whiterabbit_cart_record_id') : '';
        $db = new wrDatabase();
        if (!empty($id)) {
            $db->wr_update_abd_cart_record(
                array(
                    'abandoned_cart_time' => WHITERABBIT_CURRENT_TIME,
                    'cart_closed' => '1'
                ),
                array('whiterabbit_abandoned_cart_id' => $id));

            WC()->session->__unset('whiterabbit_cart_record_id');
        } else {
            if (is_user_logged_in()) {
                $result = $db->get_wr_abdc_cart_record_by_user(get_current_user_id());
                if (isset($result->whiterabbit_abandoned_cart_id)) {
                    $db->wr_update_abd_cart_record(
                        array(
                            'abandoned_cart_time' => WHITERABBIT_CURRENT_TIME,
                            'cart_closed' => '1'
                        ),
                        array('whiterabbit_abandoned_cart_id' => $result->whiterabbit_abandoned_cart_id));
                }
            }
        }
    }

    public function wpCronCheck()
    {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)
            return false;

        return true;
    }

    public function wooCommerceVersionCheck($version = '3.0')
    {
        if (class_exists('WooCommerce')) {
            global $woocommerce;
            if (version_compare($woocommerce->version, $version, ">=")) {
                return true;
            }
        }
        return false;
    }

    public function getWooCommerceAPIVersion()
    {
        if (class_exists('WooCommerce')) {
            global $woocommerce;

            $version = (version_compare($woocommerce->version, '3.5', ">=")) ? "wc/v3" : "wc/v2";
            return $version;
        }
        return false;
    }


    public function whiterabbit_find_order_import()
    {
        global $wpdb;
        /*$sql = " SELECT * FROM " . $wpdb->prefix . "woocommerce_order_items WHERE order_id NOT IN
                (SELECT order_id FROM " . $wpdb->prefix . "whiterabbit_import_data) GROUP BY order_id";*/
        $sql = " SELECT ID as order_id FROM " . $wpdb->prefix . "posts WHERE post_type='shop_order' and post_status <>'trash'
                 AND ID NOT IN
                (SELECT order_id FROM " . $wpdb->prefix . "whiterabbit_import_data)";
        $results = $wpdb->get_results($sql);
        return $results;

    }

    public function whiterabbit_find_user_import()
    {
        global $wpdb;
        $sql = " SELECT * FROM " . $wpdb->prefix . "users WHERE ID NOT IN
                (SELECT user_id FROM " . $wpdb->prefix . "whiterabbit_import_data)";

        $results = $wpdb->get_results($sql);
        return $results;
    }


    public function whiterabbit_debug_find_import()
    {
        global $wpdb;
        $sql = "SELECT *  FROM " . $wpdb->prefix . "whiterabbit_import_data)";
        $results = $wpdb->get_results($sql);
        echo "<pre>";
        var_dump($results);
        die;
        return $results;
    }


    public function whiterabbit_find_user($user_id)
    {
        global $wpdb;
        $sql = " SELECT * FROM " . $wpdb->prefix . "whiterabbit_import_data WHERE user_id =$user_id AND order_id = 0 ";
        $results = $wpdb->get_results($sql);
        return $results;
    }


    /*public function whiterabbit_insert_user($user_id)
    {
        if ($user_id == 0) {
            return true;
        }
        global $wpdb;
        // $sql = " INSERT INTO " . $wpdb->prefix . "whiterabbit_import_data(user_id) VALUES($user_id)";

        $sql = "INSERT INTO " . $wpdb->prefix . "whiterabbit_import_data (user_id)
                SELECT $user_id
                FROM  DUAL
                WHERE NOT EXISTS (SELECT  user_id
                                  FROM  " . $wpdb->prefix . "whiterabbit_import_data
                                  WHERE user_id=$user_id AND order_id = 0 )
                LIMIT 1; ";

        $results = $wpdb->get_results($sql);
    }*/


    public function whiterabbit_insert_order_user($order_id, $user_id)
    {

        if ($order_id == 0 && $user_id == 0) {
            return true;
        }

        global $wpdb;
        /* $sql = " INSERT INTO " . $wpdb->prefix . "whiterabbit_import_data(user_id,order_id) VALUES($user_id,$order_id)";*/
        $sql = "INSERT INTO " . $wpdb->prefix . "whiterabbit_import_data (order_id, user_id) 
                SELECT $order_id, $user_id
                FROM  DUAL 
                WHERE NOT EXISTS (SELECT order_id, user_id
                                  FROM  " . $wpdb->prefix . "whiterabbit_import_data
                                  WHERE order_id=$order_id AND user_id=$user_id)
                LIMIT 1; ";
        $results = $wpdb->get_results($sql);

    }

    public function decodeToken($token)
    {
        try {
            $timestamp = time();

            $tks = explode('.', $token);
            if (count($tks) != 3) {
                throw new UnexpectedValueException('Wrong number of segments');
            }
            list($headb64, $bodyb64, $cryptob64) = $tks;

            $jwt = new JWT();

            if (null === $payload = $jwt->jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
                throw new UnexpectedValueException('Invalid claims encoding');
            }

            // Check if the nbf if it is defined. This is the time that the
            // token can actually be used. If it's not yet that time, abort.
            if (isset($payload->nbf) && $payload->nbf > ($timestamp + JWT::$leeway)) {
                throw new BeforeValidException(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
                );
            }

            // Check that this token has been created before 'now'. This prevents
            // using tokens that have been created for later use (and haven't
            // correctly used the nbf claim).
            if (isset($payload->iat) && $payload->iat > ($timestamp + JWT::$leeway)) {
                throw new BeforeValidException(
                    'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
                );
            }

            // Check if this token has expired.
            if (isset($payload->exp) && ($timestamp - JWT::$leeway) >= $payload->exp) {
                throw new ExpiredException('Expired token');
            }

            return $payload;
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }


    function wrErrorLog($text)
    {
        if (self::WR_ENABLE_DEBUG) {
            error_log($text);
        }

    }


    function wrdecrypt($string)
    {
        $output = false;
        $key = hash('sha256', $this->_secret_key);
        $iv = substr(hash('sha256', $this->_secret_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $this->_encrypt_method, $key, 0, $iv);
        return $output;
    }


    function wrencrypt($string)
    {
        $output = false;
        $key = hash('sha256', $this->_secret_key);
        $iv = substr(hash('sha256', $this->_secret_iv), 0, 16);
        $output = openssl_encrypt($string, $this->_encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);

        return $output;
    }


    function serverDetail($server)
    {
        $return = array();

        if (!empty($server['HTTP_HOST'])) {
            $return['HTTP_HOST'] = $server['HTTP_HOST'];
        }
        if (!empty($server['REQUEST_URI'])) {
            $return['REQUEST_URI'] = $server['REQUEST_URI'];
        }
        if (!empty($server['HTTP_USER_AGENT'])) {
            $return['HTTP_USER_AGENT'] = $server['HTTP_USER_AGENT'];
        }
        if (!empty($server['REMOTE_ADDR'])) {
            $return['REMOTE_ADDR'] = $server['REMOTE_ADDR'];
        }

        if (!empty($server['REQUEST_TIME'])) {
            $return['REQUEST_TIME'] = $server['REQUEST_TIME'];
        }

        return $return;

    }

    function suiteWrGender($gender)
    {
        $genderSuite = null;
        $gender = (is_array($gender)) ? $gender[0] : $gender;
        switch (strtolower($gender)) {
            case "m" :
            case "uomo" :
            case "man" :
            case "male" :
                $genderSuite = "male";
                break;
            case "f" :
            case "donna" :
            case "woman" :
            case "female" :
                $genderSuite = "female";
                break;
        }
        return $genderSuite;
    }

    function whiterabbit_new_address_scheme($schemes)
    {
        $schemes['italy'] = array(
            'label' => __('Italy', 'white-rabbit-suite'),
            'address1_label' => __('Address Line 1', 'white-rabbit-suite'),
            'address2_label' => __('Address Line 2', 'white-rabbit-suite'),
            'city_label' => __('City', 'white-rabbit-suite'),
            'postal_label' => __('Code Postal', 'white-rabbit-suite'),
            'country_label' => __('Country', 'white-rabbit-suite'),
            'countries' => array(
                'IT' => 'Italy',
            ),
            'state_label' => __('Province', 'white-rabbit-suite'),
            'states' => array(
                'AG' => 'Agrigento',
                'AL' => 'Alessandria',
                'AN' => 'Ancona',
                'AO' => 'Aosta',
                'AR' => 'Arezzo',
                'AP' => 'Ascoli Piceno',
                'AT' => 'Asti',
                'AV' => 'Avellino',
                'BA' => 'Bari',
                'BT' => 'Barletta-Andria-Trani',
                'BL' => 'Belluno',
                'BN' => 'Benevento',
                'BG' => 'Bergamo',
                'BI' => 'Biella',
                'BO' => 'Bologna',
                'BZ' => 'Bolzano',
                'BS' => 'Brescia',
                'BR' => 'Brindisi',
                'CA' => 'Cagliari',
                'CL' => 'Caltanissetta',
                'CB' => 'Campobasso',
                'CI' => 'Carbonia-Iglesias',
                'CE' => 'Caserta',
                'CT' => 'Catania',
                'CZ' => 'Catanzaro',
                'CH' => 'Chieti',
                'CO' => 'Como',
                'CS' => 'Cosenza',
                'CR' => 'Cremona',
                'KR' => 'Crotone',
                'CN' => 'Cuneo',
                'EN' => 'Enna',
                'FM' => 'Fermo',
                'FE' => 'Ferrara',
                'FI' => 'Firenze',
                'FG' => 'Foggia',
                'FC' => 'Forlì-Cesena',
                'FR' => 'Frosinone',
                'GE' => 'Genova',
                'GO' => 'Gorizia',
                'GR' => 'Grosseto',
                'IM' => 'Imperia',
                'IS' => 'Isernia',
                'SP' => 'La Spezia',
                'AQ' => 'L\'Aquila',
                'LT' => 'Latina',
                'LE' => 'Lecce',
                'LC' => 'Lecco',
                'LI' => 'Livorno',
                'LO' => 'Lodi',
                'LU' => 'Lucca',
                'MC' => 'Macerata',
                'MN' => 'Mantova',
                'MS' => 'Massa-Carrara',
                'MT' => 'Matera',
                'ME' => 'Messina',
                'MI' => 'Milano',
                'MO' => 'Modena',
                'MB' => 'Monza e della Brianza',
                'NA' => 'Napoli',
                'NO' => 'Novara',
                'NU' => 'Nuoro',
                'OT' => 'Olbia-Tempio',
                'OR' => 'Oristano',
                'PD' => 'Padova',
                'PA' => 'Palermo',
                'PR' => 'Parma',
                'PV' => 'Pavia',
                'PG' => 'Perugia',
                'PU' => 'Pesaro e Urbino',
                'PE' => 'Pescara',
                'PC' => 'Piacenza',
                'PI' => 'Pisa',
                'PT' => 'Pistoia',
                'PN' => 'Pordenone',
                'PZ' => 'Potenza',
                'PO' => 'Prato',
                'RG' => 'Ragusa',
                'RA' => 'Ravenna',
                'RC' => 'Reggio Calabria',
                'RE' => 'Reggio Emilia',
                'RI' => 'Rieti',
                'RN' => 'Rimini',
                'RM' => 'Roma',
                'RO' => 'Rovigo',
                'SA' => 'Salerno',
                'VS' => 'Medio Campidano',
                'SS' => 'Sassari',
                'SV' => 'Savona',
                'SI' => 'Siena',
                'SR' => 'Siracusa',
                'SO' => 'Sondrio',
                'TA' => 'Taranto',
                'TE' => 'Teramo',
                'TR' => 'Terni',
                'TO' => 'Torino',
                'OG' => 'Ogliastra',
                'TP' => 'Trapani',
                'TN' => 'Trento',
                'TV' => 'Treviso',
                'TS' => 'Trieste',
                'UD' => 'Udine',
                'VA' => 'Varese',
                'VE' => 'Venezia',
                'VB' => 'Verbano-Cusio-Ossola',
                'VC' => 'Vercelli',
                'VR' => 'Verona',
                'VV' => 'Vibo Valentia',
                'VI' => 'Vicenza',
                'VT' => 'Viterbo',
            ),
        );
        return $schemes;
    }

    public function wr_is_bot()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        $bots = array(
            'rambler',
            'googlebot',
            'aport',
            'yahoo',
            'msnbot',
            'turtle',
            'mail.ru',
            'omsktele',
            'yetibot',
            'picsearch',
            'sape.bot',
            'sape_context',
            'gigabot',
            'snapbot',
            'alexa.com',
            'megadownload.net',
            'askpeter.info',
            'igde.ru',
            'ask.com',
            'qwartabot',
            'yanga.co.uk',
            'scoutjet',
            'similarpages',
            'oozbot',
            'shrinktheweb.com',
            'aboutusbot',
            'followsite.com',
            'dataparksearch',
            'google-sitemaps',
            'appEngine-google',
            'feedfetcher-google',
            'liveinternet.ru',
            'xml-sitemaps.com',
            'agama',
            'metadatalabs.com',
            'h1.hrn.ru',
            'googlealert.com',
            'seo-rus.com',
            'yaDirectBot',
            'yandeG',
            'yandex',
            'yandexSomething',
            'Copyscape.com',
            'AdsBot-Google',
            'domaintools.com',
            'Nigma.ru',
            'bing.com',
            'dotnetdotcom',
            'AspiegelBot',
            'curl',
        );
        foreach ($bots as $bot) {
            if (isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false || preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }

        return false;
    }

    function whiterabbit_date_field_formats($formats)
    {

        // Item key is JS date character - see https://flatpickr.js.org/formatting/
        // Item value is in PHP format - see http://php.net/manual/en/function.date.php

        // Adds new format 2020-02-07
        $formats['Y-m-d'] = 'Y-m-d \(\Y-\m-\d\)';

        return $formats;
    }

    function whiterabbit_crm_update_order_bulk_action_edit_shop_order($redirect_to, $doaction, $post_ids)
    {

        // if an array with order IDs is not presented, exit the function
        if (!isset($post_ids) && !is_array($post_ids))
            return $redirect_to;

        foreach ($post_ids as $order_id) {
            $order = new WC_Order($order_id);

            $oldStatus = $order->get_meta('_old_status');
            $newStatus = $order->get_status();

            $this->whiterabbit_crm_update_order($order_id, $oldStatus, $newStatus);
        }

        //using add_query_arg() is not required, you can build your URL inline
        $redirect_to = add_query_arg('whiterabbit_bulk_change_order_status', count($post_ids), $redirect_to);

        return $redirect_to;

    }

    /** Print debug log line
     * @param $entry
     * @param $mode
     * @return false|int
     */
    public function wr_plugin_log($entry, $mode = 'a')
    {
        if (!get_option('wr_plugin_log_enabled')) {
            return;
        }

        if (is_array($entry)) {
            $entry = json_encode($entry);
        }
        $file = self::WR_LOG_PATH;

        $file = fopen($file, $mode);
        $bytes = fwrite($file, current_time('mysql') . " " . $entry . "\n");
        fclose($file);
        return true;
    }

}
