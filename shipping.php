<?php

class KKEP_Integration
{
    function wco_load()
    {
        $screen = get_current_screen();
        if (!isset($screen->post_type) || 'shop_order' != $screen->post_type) {
            return;
        }

        add_filter("manage_{$screen->id}_columns", array(new KKEP_Integration(), 'wco_add_columns'));
        add_action("manage_{$screen->post_type}_posts_custom_column", array(new KKEP_Integration(), 'wco_column_cb_data'), 10, 2);
    }

    function wco_add_columns($columns)
    {
        $order_total = $columns['order_total'];
        unset($columns['order_total']);

        $columns["kkep_tracking_number"] = __("KK Takip No", "themeprefix");

        $columns['order_total'] = $order_total;

        return $columns;
    }

    function wco_column_cb_data($colname, $orderId)
    {

        if ($colname == 'kkep_tracking_number') {
            $tracking_no = get_post_meta($orderId, '_kk_tracking_no', true);
            $tracking_firm = get_post_meta($orderId, '_kk_tracking_firm', true);

            if (!empty($tracking_no)) {
                echo $this->renderTrackingLink($tracking_no, $tracking_firm);
            } else {
                echo '<a href="https://panel.kargomkolay.com" target="_blank">Kargoyu Organize Et</a>';
            }
        }
    }

    /**
     * @param array $order_statuses
     */
    public function add_WC_my_account_tracking_column($orders_columns)
    {
        // $order_statuses["wc-shipped"] = "Kargolandı";
        $offset = array_search("order-status", array_keys($orders_columns)) + 1;

        $result = array_merge(
            array_slice($orders_columns, 0, $offset),
            array('order-tracking' => "Tracking No"),
            array_slice($orders_columns, $offset, null)
        );
        return $result;
    }

    /**
     * @param Automattic\WooCommerce\Admin\Overrides\Order $order
     */
    public function render_WC_my_account_tracking_column($order)
    {
        $tracking_no = $order->get_meta("_kk_tracking_no");
        $tracking_firm = $order->get_meta("_kk_tracking_firm");

        $tracking_link = $this->renderTrackingLink($tracking_no, $tracking_firm);
        if ($tracking_link) {
            echo $tracking_link;
        } else {
            echo '';
        }
    }

    /**
     * @param string $tracking_no
     * @param string $tracking_firm
     * @return string|boolean
     */
    private function renderTrackingLink($tracking_no, $tracking_firm)
    {
        if (empty($tracking_no)) {
            return false;
        }

        $link = "";
        switch ($tracking_firm) {
            case "UPS":
                $link = "https://www.ups.com/track?loc=en_US&tracknum=";
                break;
            case "TNT":
                $link = "https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=CON&cons=";
                break;
            case "DHL":
                $link = "https://www.dhl.com/en/express/tracking.html?AWB=";
                break;
            case "FEDEX":
                $link = "https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=";
                break;
        }

        $link = $link . $tracking_no;


        return '<a href="' . $link . '" target="_blank">' . $tracking_no . '</a>';
    }

    /**
     * Singleton class instance.
     * 
     * @return KKEP_Integration
     */
    public static function get_instance()
    {

        static $instance = null;

        if ($instance == null) {
            $instance = new self();
        }

        return $instance;
    }
}


add_action('load-edit.php', array(new KKEP_Integration(), 'wco_load'), 20);
add_filter('woocommerce_account_orders_columns', array(new KKEP_Integration(), 'add_WC_my_account_tracking_column'), 10, 3);
add_action('woocommerce_my_account_my_orders_column_order-tracking', array(new KKEP_Integration(), 'render_WC_my_account_tracking_column'));
