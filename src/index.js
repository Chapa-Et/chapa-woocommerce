
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('chapa_data', {});
const defaultLabel = __('Chapa', 'woo-gutenberg-products-block');
const defaultButtonText = __('Proceed to Chapa', 'woocommerce');


const label = decodeEntities(chapa_data.title) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities(chapa_data.description || '');
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={label} />;
};

const Button = () => {
	return (
		<button>{decodeEntities(chapa_data.order_button_text || defaultButtonText)}</button>
	);
};

/**
 * Chapa payment method config object.
 */
const Chapa = {
	name: "chapa",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: decodeEntities(chapa_data.title || defaultLabel),
	supports: {
		features: settings.supports,
	},
	orderButton: <Button />
};

registerPaymentMethod(Chapa);
