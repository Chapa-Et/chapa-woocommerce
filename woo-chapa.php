<?php
/*
	Plugin Name:       Chapa Payment Gateway Plugin for WooCommerce
	Plugin URI:       https://wordpress.org/plugins/chapa-payment-gateway-for-woocommerce
	Description:       Add Chapa payment gateway to your WooCommerce store and start accepting payments.
	Version:           1.0.3
	Author:            Chapa
	Author URI:        https://chapa.co/
	License:           GPL-2.0+
	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	Text Domain:       chapa-woocommerce
	Domain Path:       /languages
	WC requires at least: 3.0.0
	WC tested up to:   6.7.1
*/

if (! defined('ABSPATH')) {
    exit;
}

define('WAF_WC_CHAPA_MAIN_FILE', __FILE__);

define('WAF_WC_CHAPA_URL', untrailingslashit(plugins_url('/', __FILE__)));

define('WAF_WC_CHAPA_VERSION', '1.0.3');

class WC_CHAPA_Payments
{
    /**
     * @var WC_CHAPA_Payments The single instance of the class
     */

    public function __construct()
    {
        add_action( 'before_woocommerce_init', [$this,'waf_wc_chapa_before_init']);
        add_action('plugin_loaded', [$this, 'includes'], 0);
        add_filter('woocommerce_payment_gateways', array($this, 'waf_wc_add_chapa_gateway'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'waf_wc_chapa_plugin_action_links']);
        add_action('wp_enqueue_scripts', [$this, 'button_enqueue_scripts']);

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_gateway_chapa_woocommerce_block_support'));
    }

    public function button_enqueue_scripts()
    {
        if (is_checkout() || is_cart()) {
            wp_enqueue_script('chapa-button-checkout-script', plugin_dir_url(__FILE__) . 'src/button.js', array(), '1.0.0', true);
        }
    }
    /**
     * include the required files
     */
    public static function includes()
    {
        // check the class exists
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-waf-wc-chapa-gateway.php';
            add_filter('woocommerce_available_payment_gateways', [__CLASS__, 'conditionally_hide_waf_chapa_payment_gateways']);
        }
    }
    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath()
    {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Add Chapa Gateway to WC
     **/
    public static function waf_wc_add_chapa_gateway($gateways)
    {
        $gateways[] = 'WAF_WC_CHAPA_Gateway';
        return $gateways;
    }

    /**
     * Add Settings link to the plugin entry in the plugins menu
     **/
    public static function waf_wc_chapa_plugin_action_links($links)
    {

        $settings_link = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=chapa') . '" title="View Settings">Settings</a>'
        );
        return array_merge($settings_link, $links);
    }

    /**
     * @param $available_gateways
     * @return mixed
     * Hide Chapa Condition payment method if the currency is not one of the following: ETB, USD, GBP, EUR
     */
    public static function conditionally_hide_waf_chapa_payment_gateways($available_gateways)
    {
        // Not in backend (admin)
        if (is_admin()) {
            return $available_gateways;
        }
        $chapa_api = new WAF_WC_CHAPA_Gateway();
        $available_currencies = $chapa_api->get_supported_currencies();
        $currency = get_woocommerce_currency();
        if (array_search($currency, $available_currencies) === false) {
            unset($available_gateways['chapa']);
        }
        return $available_gateways;
    }

    /**
     * before woocommerce init
     */
    function waf_wc_chapa_before_init()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }


    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function woocommerce_gateway_chapa_woocommerce_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once 'includes/blocks/class-wc-chapa-payments-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new WC_Gateway_Chapa_Blocks_Support());
                },
                20
            );
        }
    }
}

new WC_CHAPA_Payments();
