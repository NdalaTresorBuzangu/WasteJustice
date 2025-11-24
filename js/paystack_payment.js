// Paystack Payment Integration
// Paystack Inline Payment Handler

document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    const payButton = document.getElementById('payButton');
    
    // Paystack public key
    const PAYSTACK_PUBLIC_KEY = 'pk_test_11c4dffd1bfb8c9efb25eceb0b6132aa85761747';
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const email = document.getElementById('email').value;
            const amount = parseFloat(document.getElementById('amount').value);
            const reference = document.getElementById('reference').value;
            const userId = document.getElementById('user_id').value;
            const description = document.getElementById('description').value;
            
            // Validate email
            if (!email || !email.includes('@')) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Validate amount
            if (!amount || amount <= 0) {
                alert('Invalid payment amount');
                return;
            }
            
            // Convert amount to kobo (Paystack uses smallest currency unit)
            // For GHS, multiply by 100 to get pesewas
            const amountInPesewas = Math.round(amount * 100);
            
            // Disable button
            payButton.disabled = true;
            payButton.textContent = '⏳ Processing...';
            
            // Initialize Paystack Inline
            const handler = PaystackPop.setup({
                key: PAYSTACK_PUBLIC_KEY,
                email: email,
                amount: amountInPesewas,
                currency: 'GHS',
                ref: reference,
                metadata: {
                    custom_fields: [
                        {
                            display_name: "User ID",
                            variable_name: "user_id",
                            value: userId
                        },
                        {
                            display_name: "Description",
                            variable_name: "description",
                            value: description
                        }
                    ]
                },
                callback: function(response) {
                    // Payment successful - redirect to verification
                    console.log('Payment successful:', response);
                    
                    // Get base URL from the page
                    const baseUrl = window.location.origin + '/WasteJustice';
                    
                    // Redirect to success page with reference
                    window.location.href = baseUrl + '/views/collector/payment_success.php?reference=' + response.reference;
                },
                onClose: function() {
                    // Payment cancelled
                    payButton.disabled = false;
                    payButton.textContent = '💳 Pay GH₵' + amount.toFixed(2);
                    
                    // Show cancellation message
                    if (confirm('Payment was cancelled. Do you want to try again?')) {
                        // User wants to retry
                        return;
                    } else {
                        // Redirect back or show message
                        const baseUrl = window.location.origin + '/WasteJustice';
                        window.location.href = baseUrl + '/views/collector/dashboard.php?payment=cancelled';
                    }
                }
            });
            
            // Open Paystack payment modal
            handler.openIframe();
        });
    }
});

// Helper function to format currency
function formatCurrency(amount) {
    return 'GH₵' + parseFloat(amount).toFixed(2);
}

