<?php

class ss_feefo_sales {

    function __construct() {
        add_action("wp_ajax_ss-feefo-sales", array($this, 'func_ss_feefo_sales'));
        add_action("wp_ajax_nopriv_ss-feefo-sales", array($this, 'func_ss_feefo_sales'));
    }

    /**
     * Function to create the contents of the exported CSV file
     */
    function ss_feefo_export_data() {
        global $woocommerce, $wpdb;

        $startdate = date('Y-m-d');
        $startdate = '2015-07-01';
        $enddate = '2015-09-30';
        
        if(isset($_POST['startdate'])){
            $startdate = $_POST['startdate'];
        }
        if(isset($_POST['enddate'])){
            $enddate = $_POST['enddate'];
        }
        
        $orders =$this->ss_feefo_export_query($startdate, $enddate);
        $columns = array(
            'name',
            'email',
            'date',
            'description',
            'order_ref',
            'product_search_code',
            'customer_ref',
            'amount',
            'currency',
            'product_link',
            'merchant_identifier',
            'tags'
        );

        $merchant_identifier = get_option('merchant_identifier');
        
        $ordersdata = array();
        foreach ($orders as $order) {
            // Get order object.
            $woo_order = wc_get_order($order->orders);
            $items = $woo_order->get_items(apply_filters('woocommerce_admin_order_item_types', 'line_item'));
            $otd = array();
            $otd['name'] = $woo_order->billing_first_name . ' ' . $woo_order->billing_last_name;

            if (empty($otd['name'])) {
                $otd['name'] = $woo_order->billing_full_name . ' ' . $woo_order->billing_last_name;
            }
            $otd['email'] = $woo_order->billing_email;
            $otd['date'] = date('d/m/Y', strtotime($woo_order->order_date));
            $otd['order_ref'] = str_replace("#", "", $woo_order->get_order_number());
            $userid = $woo_order->get_user_id();
            $customer_ref = $userid;
            $user = $woo_order->get_user();
            if ($user !== FALSE) {
                $customer_ref .= " " . $user->user_firstname . "-" . $user->user_lastname;
            } else {
                $customer_ref .= " " . $otd['name'];
            }
            $otd['customer_ref'] = $customer_ref;

            //$otd['Customer_mobile_phone_number'] = $woo_order->billing_phone;

            foreach ($items as $key => $value) {
                $product = $woo_order->get_product_from_item($value);
                $replace_pattern = array('&#8220;', '&#8221;');
                $otd['description'] = "<![CDATA[" . str_replace($replace_pattern, '""', $value['name']) . "]]>";
                $otd['product_link'] = $product->get_permalink();
                if ($product->get_sku()) {
                    $otd['product_search_code'] = $product->get_sku();
                } else {
                    $otd['product_search_code'] = '';
                }
                $otd['amount'] = $value['line_total'] + $value['line_tax'];
                $otd['merchant_identifier'] = $merchant_identifier;
                $otd['tags'] = ''; 
                $otd['currency'] = get_woocommerce_currency(); // "GBP";
                $ordersdata[] = $otd;
            }
        }
        $xml = '<items>';
        if (count($ordersdata) > 0) {
            foreach ($ordersdata as $order) {
                $xml .= '<item>';
                foreach ($columns as $column) {
                    $xml .= '<' . $column . '>' . $order[$column] . '</' . $column . '>' . "\n";
                }
                $xml .= "</item>\n";
            }
        }
        $xml .= '</items>';
        echo $xml;
    }

    function ss_feefo_export_query($startdate = NULL, $enddate = NULL) {
        global $wpdb;

        // Get an array of completed orders (id values)
        $orders_sql = "SELECT DISTINCT " . $wpdb->prefix . "postmeta.post_id AS orders FROM " . $wpdb->prefix . "postmeta LEFT JOIN " . $wpdb->prefix . "posts ON (" . $wpdb->prefix . "postmeta.post_id = " . $wpdb->prefix . "posts.ID) LEFT JOIN " . $wpdb->prefix . "term_relationships ON (" . $wpdb->prefix . "posts.ID = " . $wpdb->prefix . "term_relationships.object_id) LEFT JOIN " . $wpdb->prefix . "term_taxonomy ON (" . $wpdb->prefix . "term_relationships.term_taxonomy_id = " . $wpdb->prefix . "term_taxonomy.term_taxonomy_id) LEFT JOIN " . $wpdb->prefix . "terms ON (" . $wpdb->prefix . "term_taxonomy.term_id = " . $wpdb->prefix . "terms.term_id) WHERE " . $wpdb->prefix . "posts.post_status in ('wc-processing','wc-completed') ";
        $orders_sql .= " AND wp_posts.post_type='shop_order'";
        // Insert start date if provided
        if ($startdate != NULL) {
            $orders_sql .= " AND DATE_FORMAT(" . $wpdb->prefix . "posts.post_date, '%Y-%m-%d') >= '" . $startdate . "'";
        }
        // Insert end date if provided
        if ($enddate != NULL) {
            $orders_sql .= " AND DATE_FORMAT(" . $wpdb->prefix . "posts.post_date, '%Y-%m-%d') <= '" . $enddate . "'";
        }
        $orders_sql .= " ORDER BY orders DESC";
        //print_r($orders_sql);
        return $wpdb->get_results($orders_sql);
    }

    function func_ss_feefo_sales() {
        header('Content-type: application/xml');
        header('Content-Disposition: attachment; filename=ss_feefo_orders_export.xml');
        header('Pragma: no-cache');
        header('Expires: 0');
        $this->ss_feefo_export_data();
        exit();
    }

}
$ss_sales = new ss_feefo_sales();
?>
