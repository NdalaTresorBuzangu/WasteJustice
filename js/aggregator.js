/**
 * WasteJustice - Aggregator JavaScript
 * Manages deliveries, approve/reject waste, process sales to recycling companies
 */

// Accept delivery and process Paystack payment
function acceptDelivery(collectionID) {
    if (!confirm('Accept this waste delivery? You will be redirected to Paystack to complete payment to the collector.')) {
        return;
    }
    
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    // First, accept the delivery (creates pending payment record)
    fetch(baseUrl + '/actions/accept_delivery_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `collectionID=${collectionID}`
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.success) {
            // Get collector details for Paystack payment
            fetch(baseUrl + `/api/get_collector_details.php?collectionID=${collectionID}`)
                .then(response => response.json())
                .then(collectorData => {
                    if (collectorData && collectorData.success) {
                        // Trigger Paystack payment
                        processPayment(
                            collectionID,
                            data.amount,
                            collectorData.collectorEmail,
                            collectorData.collectorName
                        );
                    } else {
                        // Fallback: show success message and reload
                        showNotification(`Delivery accepted! Amount: GH₵ ${data.amount.toFixed(2)}. Please process payment manually.`, 'success');
                        setTimeout(() => window.location.reload(), 2000);
                    }
                })
                .catch(error => {
                    console.error('Error fetching collector details:', error);
                    showNotification(`Delivery accepted! Amount: GH₵ ${data.amount.toFixed(2)}. Please process payment manually.`, 'success');
                    setTimeout(() => window.location.reload(), 2000);
                });
        } else {
            showNotification(data?.message || 'Failed to accept delivery', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Reject delivery
function rejectDelivery(collectionID) {
    const reason = prompt('Reason for rejection (optional):');
    
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    fetch(baseUrl + '/actions/reject_delivery_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `collectionID=${collectionID}&reason=${encodeURIComponent(reason || '')}`
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to reject delivery', 'error');
    });
}

// Create batch from accepted waste
function createBatch(plasticTypeID, collectionIDs) {
    if (!confirm('Create batch for sale?')) {
        return;
    }
    
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    fetch(baseUrl + '/actions/create_batch_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `plasticTypeID=${plasticTypeID}&collectionIDs=${collectionIDs}`
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.json();
        }
    })
    .then(data => {
        if (data && data.success) {
            showNotification('Batch created successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(data?.message || 'Failed to create batch', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Sell batch to company
function sellBatchToCompany(batchID, plasticTypeID) {
    // Load companies with prices
    loadCompaniesWithPrices(plasticTypeID, function(companies) {
        showCompanySelection(batchID, companies);
    });
}

function loadCompaniesWithPrices(plasticTypeID, callback) {
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    fetch(baseUrl + `/api/get_companies_with_prices.php?plasticTypeID=${plasticTypeID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                callback(data.companies);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load companies', 'error');
        });
}

function showCompanySelection(batchID, companies) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h2>Select Recycling Company</h2>
            <div id="companiesList"></div>
        </div>
    `;
    document.body.appendChild(modal);
    
    const list = document.getElementById('companiesList');
    companies.forEach(company => {
        const div = document.createElement('div');
        div.className = 'company-card';
        div.innerHTML = `
            <h3>${company.companyName}</h3>
            <p><strong>Price:</strong> GH₵ ${parseFloat(company.pricePerKg).toFixed(2)}/kg</p>
            <button onclick="confirmSale(${batchID}, ${company.userID})" class="btn btn-primary">
                Sell to This Company
            </button>
        `;
        list.appendChild(div);
    });
}

function confirmSale(batchID, companyID) {
    if (!confirm('Confirm sale to this company?')) {
        return;
    }
    
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    fetch(baseUrl + '/actions/aggregator_sale_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `batchID=${batchID}&companyID=${companyID}`
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.json();
        }
    })
    .then(data => {
        if (data && data.success) {
            showNotification(`Sale confirmed! Amount: GH₵ ${data.salePrice.toFixed(2)}`, 'success');
            document.querySelector('.modal').remove();
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(data?.message || 'Sale failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Process payment to collector via Paystack
function processPayment(collectionID, amount, collectorEmail, collectorName) {
    // Get base URL
    const baseUrl = window.location.origin + '/WasteJustice';
    
    // Generate unique reference
    const reference = 'WJ-PAY-' + Date.now() + '-' + collectionID;
    
    // Convert amount to pesewas (Paystack uses smallest currency unit)
    const amountInPesewas = Math.round(amount * 100);
    
    // Paystack public key
    const PAYSTACK_PUBLIC_KEY = 'pk_test_11c4dffd1bfb8c9efb25eceb0b6132aa85761747';
    
    // Initialize Paystack Inline
    const handler = PaystackPop.setup({
        key: PAYSTACK_PUBLIC_KEY,
        email: collectorEmail || 'collector@wastejustice.com',
        amount: amountInPesewas,
        currency: 'GHS',
        ref: reference,
        metadata: {
            custom_fields: [
                {
                    display_name: "Collection ID",
                    variable_name: "collection_id",
                    value: collectionID
                },
                {
                    display_name: "Collector Name",
                    variable_name: "collector_name",
                    value: collectorName || 'Waste Collector'
                },
                {
                    display_name: "Payment Type",
                    variable_name: "payment_type",
                    value: 'aggregator_to_collector'
                }
            ]
        },
        callback: function(response) {
            // Payment successful - verify and update payment record
            console.log('Payment successful:', response);
            
            // Verify payment with backend
            fetch(baseUrl + '/actions/verify_aggregator_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `collectionID=${collectionID}&reference=${response.reference}&amount=${amount}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Payment processed successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Payment verification failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Payment verification error', 'error');
            });
        },
        onClose: function() {
            // Payment cancelled
            showNotification('Payment was cancelled', 'warning');
        }
    });
    
    // Open Paystack payment modal
    handler.openIframe();
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh pending deliveries every 30 seconds
    if (window.location.pathname.includes('aggregator_dashboard')) {
        setInterval(() => {
            const pendingSection = document.getElementById('pendingDeliveries');
            if (pendingSection) {
                // Refresh pending deliveries
                location.reload();
            }
        }, 30000);
    }
});

