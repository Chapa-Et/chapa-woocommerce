<?php
/*
	Plugin Name:			Chapa Payment Gateway Plugin for WooCommerce
	Plugin URI: 			https://chapa.co/
	Description:            Chapa Payment Gateway Plugin for WooCommerce
	Version:                1.0.1
	Author: 				Chapa
	License:        		GPL-2.0+
	License URI:    		http://www.gnu.org/licenses/gpl-2.0.txt
	WC requires at least:   3.0.0
	WC tested up to:        6.3.1
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAF_WC_CHAPA_MAIN_FILE', __FILE__ );

define( 'WAF_WC_CHAPA_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

define( 'WAF_WC_CHAPA_VERSION', '1.0.1' );

/**
 * Initialize Chapa WooCommerce payment gateway.
 */
function waf_wc_chapa_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	require_once dirname( __FILE__ ) . '/includes/class-waf-wc-chapa-gateway.php';
	add_filter( 'woocommerce_payment_gateways', 'waf_wc_add_chapa_gateway' );
    add_filter( 'woocommerce_available_payment_gateways', 'conditionally_hide_waf_chapa_payment_gateways' );

}
add_action( 'before_woocommerce_init', 'waf_wc_chapa_before_init');
add_action( 'plugins_loaded', 'waf_wc_chapa_init' );

/**
 * before woocommerce init
 */
function waf_wc_chapa_before_init() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}


/**
* Add Settings link to the plugin entry in the plugins menu
**/
function waf_wc_chapa_plugin_action_links( $links ) {

    $settings_link = array(
    	'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=waf_chapa' ) . '" title="View Settings">Settings</a>'
    );
    return array_merge( $settings_link, $links );

}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'waf_wc_chapa_plugin_action_links' );


/**
* Add Chapa Gateway to WC
**/
function waf_wc_add_chapa_gateway( $methods ) {
    $methods[] = 'Waf_WC_chapa_Gateway';
	return $methods;

}

/**
 * @param $available_gateways
 * @return mixed
 * Hide Chapa Condition payment method if the currency is not one of the following: ETB, USD, GBP, EUR
 */
function conditionally_hide_waf_chapa_payment_gateways( $available_gateways ) {
    // Not in backend (admin)
    if( is_admin() ){
        return $available_gateways;
    }
    $chapa_api = new Waf_WC_chapa_Gateway();
    $available_currencies = $chapa_api->get_supported_currencies();
    $currency = get_woocommerce_currency();
    if(array_search($currency, $available_currencies) === false){
        unset($available_gateways['waf_chapa']);
    }
    return $available_gateways;
}