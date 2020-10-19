<?php
/**
 * Plugin Name: განვადება TBC
 * Description:  WooCommerce TBC განვადება
 * Author: E404R
 * Author URI: https://e404r.vip
 * Version: 1.0.0
 * Text Domain: wootbcin
 * Domain Path: /langs/
 */





if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}


if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


function load_core_text_domain() {
    load_plugin_textdomain( 'wootbcin', false, dirname( plugin_basename( __FILE__ ) ) . '/langs' );
}

add_action('after_setup_theme', 'load_core_text_domain');


function woo_tbc_installment_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_tbc_installment';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'woo_tbc_installment_add_to_gateways');


function tbc_gateway_icon( $gateways ) {
    if ( isset( $gateways['installment_tbc'] ) ) {
        $gateways['installment_tbc']->icon =  plugin_dir_url(__FILE__) . 'logo/tbc.png';
    }

    return $gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'tbc_gateway_icon' );


function woo_plugin_tbc_installment($links)
{
    
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=installment_tbc') . '">' . __('Configure', 'wootbcin') . '</a>'
    );
    
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woo_plugin_tbc_installment');


add_action('plugins_loaded', 'woo_init_tbc_installment', 11);

function woo_init_tbc_installment()
{
    
    class WC_Gateway_tbc_installment extends WC_Payment_Gateway
    {
        
        
        public function __construct()
        {
            
            $this->id                 = 'installment_tbc';
            $this->icon               = apply_filters('woocommerce_tbc_inst_icon', '');
            $this->order_button_text  = __('Buy in installments', 'wootbcin');
            $this->has_fields         = false;
            $this->method_title       = __('TBC installment', 'wootbcin');
            $this->method_description = __('Add a TBC Bank installment plan to your site', 'wootbcin');
            
      
            $this->init_form_fields();
            $this->init_settings();
            
           
            $this->title        = $this->get_option('title');
            $this->description  = $this->get_option('description');
            $this->instructions = $this->get_option('instructions', $this->description);
         

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
         
        }
        
        
      
        public function init_form_fields()
        {
            
            $this->form_fields = apply_filters('woo_tbc_installment_form_fields', array(
                
                'enabled' => array(
                    'title' => __('Turning ON / OFF', 'wootbcin'),
                    'type' => 'checkbox',
                    'label' => __('Inclusion of TBC installment', 'wootbcin'),
                    'default' => 'no'
                ),
                
                'title' => array(
                    'title' => __('Title', 'wootbcin'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wootbcin'),
                    'default' => __('Buy in installments', 'wootbcin'),
                    'desc_tip' => true
                ),
                
                'description' => array(
                    'title' => __('Description', 'wootbcin'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'wootbcin'),
                    'default' => __('Buy products with TBC installment.', 'wootbcin'),
                    'desc_tip' => true
                )
               
            ));
        }
        
        
      
        public function process_payment($order_id)
        {
            
            $order = wc_get_order($order_id);
            
            $shop_site= get_site_url();
            $items         = '';
            $itemAmount    = '';
            $totalAmount   = WC()->cart->subtotal;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product    = $cart_item['data'];
                $item_name  = $cart_item['data']->get_title();
                $product_id = $cart_item['product_id'];
                $quantity   = $cart_item['quantity'];
                $price      = WC()->cart->get_product_price($product);
                $subtotal   = WC()->cart->get_product_subtotal($product, $cart_item['quantity']);
                
                $items .= $item_name . ';';
                
                $itemAmount .= $item_name .'-' . $quantity . ';';
            }
            
            $tbc_api = 'https://tbccredit.ge/ganvadeba?utm_source=' . $shop_site. '&productName=' . $items . ' &productAmount=' . $itemAmount . '&totalAmount=' . $totalAmount . '';
            $tbc_api = preg_replace('/\s+/', '', $tbc_api);

            $order->update_status('on-hold', __('TBC installment', 'wootbcin'));
            
      
            $order->reduce_order_stock();
            

            WC()->cart->empty_cart();
            
            return array(
                'result' => 'success',
                'redirect' => $tbc_api
            );
        }
        
    } 
}