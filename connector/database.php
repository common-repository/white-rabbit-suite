<?php
class wrDatabase {

    private $cart_record_tb;

    private $wpdb;

    //in minutes
    const COMPARE_TIME = 40;

    public function __construct() {
        global $wpdb;
        $this->wpdb             = $wpdb;
        $this->cart_record_tb   = $wpdb->prefix . "whiterabbit_abandoned_carts";
    }

    private $format = array(
        'user_id'             => '%d',
        'abandoned_cart_info' => '%s',
        'abandoned_cart_time' => '%s',
        'cart_closed'         => '%s',
        'detail'             => '%s'
    );

    public function wr_check_abd_cart_table() {
        $query = "SHOW TABLES LIKE %s";

        return $this->wpdb->get_var( $this->wpdb->prepare( $query, $this->cart_record_tb ) ) === $this->cart_record_tb;
    }

    // Query with abandoned cart record table

    public function wr_update_abd_cart_record( $data = array(), $where = array() ) {
        $data_fm = $where_fm = array();

        foreach ( $data as $item ) {
            if ( isset( $this->format[ $item ] ) ) {
                $data_fm[] = $this->format[ $item ];
            }
        }

        foreach ( $where as $item ) {
            if ( isset( $this->format[ $item ] ) ) {
                $where_fm[] = $this->format[ $item ];
            }
        }

        return $this->wpdb->update( $this->cart_record_tb, $data, $where, $data_fm, $where_fm );
    }

    public function wr_insert_abd_cart_record( $data = array() ) {
        $data_fm = array();

        foreach ( $data as $item ) {
            if ( isset( $this->format[ $item ] ) ) {
                $data_fm[] = $this->format[ $item ];
            }
        }

        $this->wpdb->insert( $this->cart_record_tb, $data, $data_fm );

        return $this->wpdb->insert_id;
    }

    public function get_wr_abdc_cart_record( $wr_abdc_id ) {
        $query = "SELECT * FROM {$this->cart_record_tb} WHERE whiterabbit_abandoned_cart_id = %s AND cart_closed = %s";

        return $this->wpdb->get_row( $this->wpdb->prepare( $query, $wr_abdc_id , '0') );
    }

    public function get_wr_abdc_cart_record_by_user( $user_id ) {
        $query = "SELECT * FROM {$this->cart_record_tb} WHERE user_id = %s AND cart_closed = %s";

        return $this->wpdb->get_row( $this->wpdb->prepare( $query, $user_id , '0') );
    }

    //Reports

    public function get_wr_abd_cart_report() {
        $compare_time_member = WHITERABBIT_CURRENT_TIME - self::COMPARE_TIME * MINUTE_IN_SECONDS;

        $query = "SELECT * FROM {$this->cart_record_tb} WHERE %d > abandoned_cart_time";

        return $this->wpdb->get_results( $this->wpdb->prepare( $query, $compare_time_member ) );
    }

    public function wr_remove_abd_cart_record( $id ) {
        return $this->wpdb->delete( $this->cart_record_tb, array( 'whiterabbit_abandoned_cart_id' => $id ), array( '%d' ) );
    }

    public function wr_remove_abd_cart_record_by_user_id( $id ) {
        $this->wpdb->delete( $this->cart_record_tb, array( 'user_id' => $id, 'cart_closed' => '0' ), array( '%d' , '%s' ) );
    }

    public function wr_remove_abd_cart_by_time( $time ) {
        $time  = WHITERABBIT_CURRENT_TIME - $time * DAY_IN_SECONDS;
        $query = "delete from {$this->cart_record_tb} where abandoned_cart_time < %d limit 500";
        $this->wpdb->query( $this->wpdb->prepare( $query, $time ) );
    }

}
