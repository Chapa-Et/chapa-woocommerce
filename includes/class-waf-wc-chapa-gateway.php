<?php

if (!defined('ABSPATH')) {
    exit;
}
/**
 * Class WAF_WC_CHAPA_Gateway
 */
class WAF_WC_CHAPA_Gateway extends WC_Payment_Gateway
{

    /**
     * Checkout page title
     *
     * @var string
     */
    public $title;

    /**
     * Checkout page description
     *
     * @var string
     */
    public $description;

    /**
     * Is gateway enabled?
     *
     * @var bool
     */
    public $enabled;

    /**
     * API public key.
     *
     * @var string
     */
    public $merchant_key;



    /**
     * API public key.
     *
     * @var string
     */
    public $public_key;

    /**
     * API secret key.
     *
     * @var string
     */
    public $secret_key;

    /**
     * Invoice Prefix for the webiste
     * @var string
     */
    public $invoice_prefix;


    public $currency_api;

    public $default_url;

    public $verify_api;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id                 = 'chapa';
        $this->method_title       = 'Chapa';
        $this->order_button_text = __('Proceed to Chapa', 'woocommerce');
        $this->method_title      = __('Chapa', 'woocommerce');
        $this->method_description = sprintf('Start accepting money.. <a href="%1$s" target="_blank">Sign up</a> for a Chapa account, and <a href="%2$s" target="_blank">get your API keys</a>.', $this->default_url, $this->default_url.'/dashbpard/api');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        // Get setting values.
        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled     = $this->get_option('enabled');
        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->default_url = 'https://dashbpard.chapa.co';
        $this->currency_api = "https://api.chapa.co/v1/currency_supported";
        $this->verify_api = "https://api.chapa.co/v1/transaction/verify/";
        $this->invoice_prefix = $this->get_option('invoice_prefix');

        // Hooks.
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        // Payment listener/API hook.
        add_action('woocommerce_api_WAF_WC_CHAPA_gateway', array($this, 'verify_chapa_transaction'));
        // Webhook listener/API hook.
        add_action('woocommerce_api_chapa_success', array($this, 'process_success'));
        add_action('woocommerce_api_chapa_proceed', array($this, 'chapa_proceed'));
    }

    /**
     * @param bool $string
     * @return array|string
     * Get currently supported currencies from chapa endpoint
     */
    public function get_supported_currencies($string = false){
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->secret_key
            )
        );
	    $currency_request = wp_remote_get($this->currency_api, $args);
        $currency_array = array();
	    if ( ! is_wp_error( $currency_request ) && 200 == wp_remote_retrieve_response_code( $currency_request ) ){
            $currencies = json_decode(wp_remote_retrieve_body($currency_request));
            if($currencies->currency_code && $currencies->currency_name){
                foreach ($currencies->currency_code as $index => $item){
                    if($string === true){
                        $currency_array[] = $currencies->currency_name[$index];
                    }else{
                        $currency_array[$currencies->currency_code[$index]] = $currencies->currency_name[$index];
                    }
                }
            }
        }
        if($string === true){
            return implode(", ", $currency_array);
        }
	    return $currency_array;
    }
    /**
     * Check if chapa merchant details is filled
     */
    public function admin_notices()
    {

        if ('no' === $this->enabled) {
            return;
        }

        // Check required fields.
        if (!($this->public_key && $this->secret_key)) {
            echo '<div class="error"><p>' . sprintf('Please enter your Chapa API details <a href="%s">here</a> to be able to use the Chapa WooCommerce plugin.', admin_url('admin.php?page=wc-settings&tab=checkout&section=wafchapa')) . '</p></div>';
            return;
        }
    }

    /**
     * Check if chapa gateway is enabled.
     */
    public function is_available()
    {

        if ('yes' === $this->enabled) {

            if (!($this->public_key && $this->secret_key)) {

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Admin Panel Options
     */
    public function admin_options()
    {
?>

        <h3>Chapa</h3>
        <h4>We Support these currencies: <?php echo esc_attr($this->get_supported_currencies(true)); ?></h4>
        <?php
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled'         => array(
                'title'       => __('Enable/Disable', 'woo-chapa'),
                'label'       => __('Enable Chapa', 'woo-chapa'),
                'type'        => 'checkbox',
                'description' => __('Enable Chapa as a payment option on the checkout page.', 'woo-chapa'),
                'default'     => 'no',
                'desc_tip'    => false,
            ),
            'title'           => array(
                'title'       => __('Title', 'woo-chapa'),
                'type'        => 'text',
                'description' => __('This controls the payment method title which the user sees during checkout.', 'woo-chapa'),
                'desc_tip'    => false,
                'default'     => __('Chapa', 'woo-chapa'),
            ),
            'description'     => array(
                'title'       => __('Description', 'woo-chapa'),
                'type'        => 'textarea',
                'description' => __('This controls the payment method description which the user sees during checkout.', 'woo-chapa'),
                'desc_tip'    => false,
                'default'     => __('Pay using your telebirr, CBE Birr, ATM, Bank account, Mobile money, PayPal ,Debit and Credit card', 'woo-chapa'),
            ),
            'invoice_prefix' => array(
                'title'       => __('Invoice Prefix', 'woo-chapa'),
                'type'        => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your Chapa account for multiple stores ensure this prefix is unique as Chapa will not allow orders with the same invoice number.', 'woo-chapa'),
                'default'     => 'WC_',
                'desc_tip'    => false,
            ),
            'public_key' => array(
                'title'       => __('Public Key', 'woo-chapa'),
                'type'        => 'text',
                'description' => __('Required: Enter your Public Key here.', 'woo-chapa'),
                'default'     => '',
                'desc_tip'    => false,
            ),
            'secret_key' => array(
                'title'       => __('Secret Key', 'woo-chapa'),
                'type'        => 'text',
                'description' => __('Required: Enter your Secret Key here.', 'woo-chapa'),
                'default'     => '',
                'desc_tip'    => false,
            )
        );
    }

    /**
     * Payment form on checkout page
     */
    public function payment_fields()
    {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }

        if (!is_ssl()) {
            return;
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        // Remove cart
        $woocommerce->cart->empty_cart();
        $currency = $order->get_currency();
        $currency_array = $this->get_supported_currencies();
        $currency_code = array_search($currency, $currency_array);
        $secret_key = urlencode($this->secret_key);
        $public_key = urlencode($this->public_key);
        $tx_ref = urlencode($this->invoice_prefix . $order_id);
        $amount = urlencode($order->get_total());
        $email = urlencode($order->get_billing_email());
        $callback_url = urlencode(WC()->api_request_url('chapa_success') . "?order_id=" . $order_id);
        $first_name = urlencode($order->get_billing_first_name());
        $last_name = urlencode($order->get_billing_last_name());
        $title = urlencode("Payment for items on " . get_bloginfo('name'));
        $url = WC()->api_request_url('chapa_Proceed') . "?callback_url={$callback_url}&return_url={$callback_url}&tx_ref={$tx_ref}&amount={$amount}&email={$email}&first_name={$first_name}&last_name={$last_name}&title={$title}&currency={$currency_code}";
        //Return to Chapa Proceed page for the next step
        return array(
            'result' => 'success',
            'redirect' => $url
        );
    }
    /**
     * API page to handle the callback data from chapa
     */
    public function process_success()
    {
        if ($_GET['order_id']) {
            $order_id = intval(sanitize_text_field($_GET['order_id']));
            $wc_order = wc_get_order($order_id);
            $args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->secret_key
                )
            );
            $chapa_request = wp_remote_get($this->verify_api.$this->invoice_prefix.$order_id, $args);
            //dd($chapa_request);
                $chapa_order = json_decode(wp_remote_retrieve_body($chapa_request));
                $status = $chapa_order->status;
                if ($status === "success") {
                    $order_total = $wc_order->get_total();
                    $amount_paid = $chapa_order->data->amount;
                    $reference_id = $chapa_order->data->reference;
                    $order_currency = $wc_order->get_currency();
                    $currency_symbol = get_woocommerce_currency_symbol($order_currency);
                    if (($amount_paid < $order_total) || ($order_currency !== $chapa_order->data->currency)) {
                        // Mark as on-hold
                        $wc_order->update_status('on-hold', '');
                        update_post_meta($order_id, '_transaction_id', $reference_id);
                        $notice      = 'Thank you for shopping with us.<br />Your payment was successful, but the amount paid or currency is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
                        $notice_type = 'notice';
                        // Add Customer Order Note
                        $wc_order->add_order_note($notice, 1);
                        // Add Admin Order Note
                        $wc_order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>' . $currency_symbol . $amount_paid . '</strong> while the total order amount is <strong>' . $currency_symbol . $order_total . '</strong><br /><strong>Reference ID:</strong> ' . $reference_id);

                        wc_add_notice($notice, $notice_type);
                    } else {
                        //Complete order
                        $wc_order->payment_complete($reference_id);
                        $wc_order->add_order_note(sprintf('Payment via Chapa successful (<strong>Reference ID:</strong> %s)', $reference_id));
                    }
                    wp_redirect($this->get_return_url($wc_order));
                    die();
                } elseif ($status === "failed/cancelled") {
                    $wc_order->update_status('canceled', 'Payment was canceled.');
                    wc_add_notice('Payment was canceled.', 'error');
                    wp_redirect(wc_get_page_permalink('checkout'));
                    die();
                } else {
                    $wc_order->update_status('failed', 'Payment was declined by Chapa.');
                    wc_add_notice('Payment was declined by Chapa.', 'error');
                    wp_redirect(wc_get_page_permalink('checkout'));
                    die();
                }
        }
        die();
    }

    /**
     * API page to redirect user to chapa
     */
    public function chapa_proceed()
    {
        $invalid = 0;
        if (wp_http_validate_url($_GET['callback_url'])) {
            $callback_url = esc_url($_GET['callback_url']);
        } else {
            wc_add_notice('The payment setting of this website is not correct, please contact us', 'error');
            $invalid++;
        }
        if (!empty($_GET['tx_ref'])) {
            $tx_ref = sanitize_text_field($_GET['tx_ref']);
        } else {
            wc_add_notice('It seems that something is wrong with your order. Please try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['amount']) && is_numeric($_GET['amount'])) {
            $amount = floatval(sanitize_text_field($_GET['amount']));
        } else {
            wc_add_notice('It seems that you have submitted an invalid price for this order. Please try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['email']) && is_email($_GET['email'])) {
            $email = sanitize_email($_GET['email']);
        } else {
            wc_add_notice('Your email is empty or not valid. Please check and try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['first_name'])) {
            $first_name = sanitize_text_field($_GET['first_name']);
        } else {
            wc_add_notice('Your first name is empty or not valid. Please check and try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['last_name'])) {
            $last_name = sanitize_text_field($_GET['last_name']);
        } else {
            wc_add_notice('Your last name is empty or not valid. Please check and try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['title'])) {
            $title = sanitize_text_field($_GET['title']);
        } else {
            wc_add_notice('The order title is empty or not valid. Please check and try again', 'error');
            $invalid++;
        }
        if (!empty($_GET['currency']) && is_numeric($_GET['currency'])) {
            $currency = sanitize_text_field($_GET['currency']);
        } else {
            wc_add_notice('The currency code is not valid. Please check and try again', 'error');
            $invalid++;
        }
        if ($invalid === 0) {
        ?>
            <!DOCTYPE html>
            <html>

            <head>
                <title>chapa Secure Verification</title>
                <script language="Javascript">
                    window.onload = function() {
                        document.forms['waf_chapa_payment_form'].submit();
                    }
                </script>
            </head>

            <body>
                <div>
                </div>
                <h3>We are redirecting you to Chapa, please wait ...</h3>
                <form id="waf_chapa_payment_form" name="waf_chapa_payment_form" method="POST" action="https://api.chapa.co/v1/woocommerce">
                    <input type="hidden" name="secret_key" value="<?php esc_attr_e($this->secret_key);  ?>" />
                    <input type="hidden" name="callback_url" value="<?php echo esc_url($callback_url);  ?>" />
                    <input type="hidden" name="return_url" value="<?php echo esc_url($callback_url);  ?>" />
                    <input type="hidden" name="tx_ref" value="<?php esc_attr_e($tx_ref);  ?>" />
                    <input type="hidden" name="amount" value="<?php esc_attr_e($amount);  ?>" />
                    <input type="hidden" name="email" value="<?php esc_attr_e($email); ?>" />
                    <input type="hidden" name="first_name" value="<?php esc_attr_e($first_name); ?>" />
                    <input type="hidden" name="last_name" value="<?php esc_attr_e($last_name); ?>" />
                    <input type="hidden" name="title" value="<?php esc_attr_e($title); ?>" />
                    <input type="hidden" name="description" value="<?php esc_attr_e($title); ?>" />
                    <input type="hidden" name="currency" value="<?php esc_attr_e($currency); ?>" />
                    <input type="submit" value="submit" style="display: none" />
                </form>
            </body>

            </html>
<?php
        } else {
            wp_redirect(wc_get_page_permalink('checkout'));
        }
        die();
    }
    /**
     * Get the return url (thank you page).
     *
     * @param WC_Order|null $order Order object.
     * @return string
     */
    public function get_return_url($order = null)
    {
        if ($order) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_checkout_url());
        }
        return apply_filters('woocommerce_get_return_url', $return_url, $order);
    }
}
