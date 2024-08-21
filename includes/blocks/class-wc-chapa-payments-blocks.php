<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Chapa Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Chapa_Blocks_Support extends AbstractPaymentMethodType
{

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Chapa
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $title = 'chapa';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_chapa_settings', []);
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[$this->title];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		$script_path       = '/assets/js/blocks.js';
		$script_asset_path = WC_Chapa_Payments::plugin_abspath() . 'assets/js/blocks.asset.php';
		$script_asset      = file_exists($script_asset_path)
			? require($script_asset_path)
			: [
				'dependencies' => [],
				'version'      => '1.2.0'
			];
		$script_url        = WC_Chapa_Payments::plugin_url() . $script_path;


		wp_enqueue_script(
			'wfc-chapa-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_localize_script(
			'wfc-chapa-payments-blocks',
			'chapa_data',
			$this->get_payment_method_data()
		);

		return ['wfc-chapa-payments-blocks'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		return [
			'title'       => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'order_button_text' => $this->gateway->order_button_text,
			'supports'    => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
		];
	}
}
