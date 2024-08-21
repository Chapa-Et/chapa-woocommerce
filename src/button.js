document.addEventListener('DOMContentLoaded', function () {

    function updateCheckoutButtonText() {
        const button = document.querySelector('.wc-block-components-checkout-place-order-button');
        let text = button.querySelector('.wc-block-components-button__text');
        const selectedPaymentMethod = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        
        
        if (button) {
            const customText = 'Proceed to Chapa';
            const defaultText = 'Place order';          

            if (selectedPaymentMethod && selectedPaymentMethod.value === 'chapa') {
                text.textContent = customText;
            } else {
                text.textContent = defaultText;
            }
        } else {
            console.error('Checkout button not found.');
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'radio-control-wc-payment-method-options') {
            updateCheckoutButtonText();
        }
    });
});