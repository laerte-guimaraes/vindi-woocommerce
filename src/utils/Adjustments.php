<?php
/**
 * Edition of products with reflection within Vindi Subscriptions
 */

class VindiAdjustments
{
    /**
     * @var VindiRoutes
     */
    private $routes;

    function __construct(VindiSettings $vindi_settings)
    {
        $this->routes = $vindi_settings->routes;

        add_action('wp_insert_post', array($this, 'product_updates_hook_handler'), 10, 3);
    }

    /**
     * When the user creates a subscription in Woocomerce, it is created in the Vindi.
     */
    function product_updates_hook_handler($post_id, $post, $update, $recreated = false)
    {
        if (strpos(get_post_status($post_id), 'draft') !== false || get_post_type($post_id) != 'product') {
            return;
        }

        if (!$recreated && get_post_status($post_id) != 'publish'
            || (!empty(get_post_meta($post_id, 'vindi_plan_id', true))
                && !empty(get_post_meta($post_id, 'vindi_product_id', true))
            )
        ) {
            $this->update($post_id);
        }
    }

    /**
     * @param int $post_id
     */
    function update($post_id)
    {
        $product = wc_get_product($post_id);

        if (!in_array($product->get_type(), array('variable-subscription', 'subscription'))) {
            return;
        }

        if ($product->get_type() == 'variable-subscription') {
            $variations = $product->get_available_variations();
            $variations_products = $variations_plans = [];

            foreach ($variations as $variation) {
                $variation_product = wc_get_product($variation['variation_id']);
                $vindi_plan_id = get_post_meta($variation['variation_id'], 'vindi_plan_id', true);
                $vindi_product_id = get_post_meta($variation['variation_id'], 'vindi_product_id', true);

                if (empty($vindi_plan_id)) {
                    continue;
                }

                $this->prepare_order_items($vindi_product_id, $vindi_plan_id, $variation_product);
            }
        } else if ($product->get_type() == 'subscription') {
            $vindi_plan_id = get_post_meta($post_id, 'vindi_plan_id', true);
            $vindi_product_id = get_post_meta($post_id, 'vindi_product_id', true);

            if(empty($vindi_plan_id)) {
                return;
            }

            $this->prepare_order_items($vindi_product_id, $vindi_plan_id, $product);
        }
    }

    /**
     * @param string $vindi_product_id
     * @param string $vindi_plan_id
     * @param WC_Product $product
     */
    public function prepare_order_items($vindi_product_id, $vindi_plan_id, $product)
    {
        $vindi_subscriptions = $this->routes->getSubscriptionsByPlanID($vindi_plan_id);

        foreach ($vindi_subscriptions as $vindi_subscription) {
            $data = $product->get_data();
            $orders = wc_get_orders(array(
                'meta_query'    => array(
                    array(
                        'key'   => 'vindi_subscription_id',
                        'value' => $vindi_subscription['id'],
                    )
                )
            ));

            foreach ($orders as $order) {
                $related_subscriptions = wcs_get_subscriptions_for_order(
                    $order->ID,
                    array(
                        'order_type' => array(
                            'parent', 'renewal'
                        )
                    )
                );

                if (1 == count($related_subscriptions)
                    && is_a($subscription = reset($related_subscriptions), 'WC_Subscription')
                    && $subscription->get_status() != 'cancelled'
                ) {
                    $changes = array(
                        'vindi_product_id' => $vindi_product_id,
                        'price'            => $data['price'] ? : 0
                    );

                    $this->synchronize_order_items($subscription, $changes, $vindi_subscription);
                }
            }

        }
    }

    /**
     * @param WC_Subscription $wc_subscription
     * @param array $changes
     * @param array $vindi_subscription
     */
    public function synchronize_order_items($wc_subscription, $changes, $vindi_subscription)
    {
        /*
        O filtro de status das assinaturas na Vindi foi removido
        por ser conflitante com a funcionalidade "Sincronismo de assinaturas".
        /Uma assinatura pendente de pagamento pode possuir o status `cancelado` na Vindi
        if (!array_key_exists('status', $vindi_subscription)
            || $vindi_subscription['status'] != 'active'
        ) {
            return;            
        }
        */

        $vindi_subscription_product_items = $this->vindi_subscription_product_items($vindi_subscription);
        $wc_product_items = $this->wc_product_items($wc_subscription, $changes);
        
        $this->check_product_items($vindi_subscription_product_items, $wc_product_items);
    }

    /**
     * @param array $vindi_subscription
     *
     * @return array
     */
    public function vindi_subscription_product_items($vindi_subscription)
    {
        $vindi_subscription_product_items = [];

        foreach ($vindi_subscription['product_items'] as $product_item) {
            $vindi_subscription_product_items[(string) $product_item['product']['id']] = array(
                'product_item_id' => $product_item['id'],
                'quantity' => $product_item['quantity'],
                'price' => $product_item['pricing_schema']['price']
            );
        }

        return $vindi_subscription_product_items;
    }

    /**
     * @param WC_Order $order
     * @param array $changes
     * 
     * @return array
     */
    public function wc_product_items($order, $changes)
    {
        $wc_product_items = [];

        foreach ($order->get_items() as $order_item) {
            $vindi_product_id = $order_item->get_product()->get_meta('vindi_product_id', true);

            if (!$vindi_product_id || $vindi_product_id != $changes['vindi_product_id']) {
                continue;
            }

            $wc_product_items[$vindi_product_id] = array(
                'quantity' => $order_item->get_quantity(),
                'price' => sprintf('%0.2f', round($changes['price'], 2))
            );

            $this->set_order_item_price($order_item, $changes['price']);
        }

        $order->calculate_totals();
        return $wc_product_items;
    }

    /**
     * @param WC_Order_Item $order_item
     * @param float $price
     *
     * @return array
     */
    public function set_order_item_price($order_item, $price)
    {
        $order_item->set_subtotal($price);
        $order_item->set_total($price * $order_item->get_quantity());
    }

    /**
     * @param array $vindi_subscription_product_items
     * @param array $wc_product_items
     */
    public function check_product_items($vindi_subscription_product_items, $wc_product_items)
    {
        foreach ($wc_product_items as $key => $value) {
            if (array_key_exists($key, $vindi_subscription_product_items)) {
                if ($value['quantity'] != $vindi_subscription_product_items[$key]['quantity']
                    || $value['price'] != $vindi_subscription_product_items[$key]['price']) {
                    $this->update_product_item($vindi_subscription_product_items[$key]['product_item_id'], $value);
                }
            }
        }
    }

    /**
     * @param string $product_item_id
     * @param array $params
     */
    public function update_product_item($product_item_id, $params)
    {
        $data = array(
            'quantity' => $params['quantity'],
            'pricing_schema' => array(
                'price' => $params['price'],
                'schema_type' => 'per_unit'
            )
        );

        $this->routes->updateSubscriptionProductItem($product_item_id, $data);
    }
}
