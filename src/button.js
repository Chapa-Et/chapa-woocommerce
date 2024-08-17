document.addEventListener('DOMContentLoaded', function () {

    function updateCheckoutButtonText() {
        const button = document.querySelector('.wc-block-components-checkout-place-order-button');
        const selectedPaymentMethod = document.querySelector('input[name="radio-control-wc-payment-method-options"]:checked');
        if (selectedPaymentMethod && selectedPaymentMethod.value === 'chapa') {
            const customText = 'Proceed to Chapa';
            button.value = customText;
            button.textContent = customText;
        } else {
            button.value = 'Place order';
            button.textContent = 'Place order';
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'radio-control-wc-payment-method-options') {
            updateCheckoutButtonText();
        }
    });
});
