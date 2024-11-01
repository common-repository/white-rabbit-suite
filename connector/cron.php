<?php
if (!class_exists('wrDatabase')) {
    require_once(WHITERABBIT_PATH . "connector/database.php");
}

class wrCron
{

    public function wr_remove_abandoned_cart() {
        $db = new wrDatabase();
        if(!empty($db->wr_check_abd_cart_table()))
            $db->wr_remove_abd_cart_by_time( get_option('wr_general_abandoned_cart_expiry') );
    }

    public function add_cron_schedule( $schedules ) {
        //for custom cron scheduled times
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display'  => __( 'One minute' ),
        );

        return $schedules;
    }

    public function wr_execute_cron() {
        $db = new wrDatabase();
        if(!empty($db->wr_check_abd_cart_table())) {
            $wr_connect = new wrConnector();
            if($wr_connect->wooCommerceVersionCheck()) {
                $abd_carts = $db->get_wr_abd_cart_report();

                foreach ($abd_carts as $item) {
                    $abd_data = array();
                    $total = $tax = 0;
                    $abd_data['cart_id'] = $item->whiterabbit_abandoned_cart_id;
                    $abd_data['user_id'] = $item->user_id;
                    $abd_data['cart_close'] = $item->cart_closed;
                    $cart_items = json_decode($item->abandoned_cart_info)->cart;
                    $abd_data['cart_date'] = date( 'Y-m-d H:i:s', $item->abandoned_cart_time );
                    $abd_data['currency'] = json_decode($item->abandoned_cart_info)->currency;

                    $productActivity = array();
                    foreach ($cart_items as $key => $product) {
                        $productActivity[$key]['product_id'] = $product->product_id;
                        $productActivity[$key]['qty'] = $product->quantity;
                        $productActivity[$key]['tax'] = $product->line_tax / $product->quantity;

                        $total += ($product->line_total);
                        $tax += ($product->line_tax);
                    }
                    $abd_data['cart_total'] = $total + $tax;
                    $abd_data['cart_tax'] = $tax;
                    $abd_data['products'] = $productActivity;

                    $result = $wr_connect->whiterabbit_crm_insert_cart_abandoned($abd_data);

                    if ($result["result"] == false && $result["error"] == true) {
                        $wr_connect->wrErrorLog("Errore Messaggio: " . $result["error_message"]);
                    }
                }
            }
        }
    }


}