/**
 * WasteJustice - Subscription JavaScript
 * Handles subscription form, payment validation, and dynamic updates
 */

// Handle subscription form submission
function handleSubscriptionForm() {
    const form = document.getElementById('subscriptionForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Validate form
        if (!validateSubscriptionForm(formData)) {
            return;
        }
        
        // Show loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        // Submit subscription
        // Use form action or construct URL from current location
        const formAction = form.getAttribute('action');
        const submitUrl = formAction || (window.location.origin + '/WasteJustice/actions/subscription_action.php');
        fetch(submitUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text();
            }
        })
        .then(data => {
            if (data && data.includes('error')) {
                showNotification('Subscription failed. Please check your payment details.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            } else {
                showNotification('Subscription successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}

// Validate subscription form
function validateSubscriptionForm(formData) {
    const paymentMethod = formData.get('paymentMethod');
    const referenceNumber = formData.get('referenceNumber');
    const freeTrial = formData.get('freeTrial');
    
    if (!paymentMethod) {
        showNotification('Please select a payment method', 'error');
        return false;
    }
    
    if (!freeTrial && !referenceNumber) {
        showNotification('Payment reference number is required', 'error');
        return false;
    }
    
    if (paymentMethod === 'Mobile Money') {
        const mobileMoney = formData.get('mobileMoneyNumber');
        if (!mobileMoney || mobileMoney.length < 10) {
            showNotification('Please enter a valid mobile money number', 'error');
            return false;
        }
    }
    
    return true;
}

// Check subscription status asynchronously
function checkSubscriptionStatus(userID) {
    fetch(`api/subscription_status.php?userID=${userID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSubscriptionUI(data.subscription);
                
                // Show expiry notice if needed
                if (data.expiryNotice) {
                    showExpiryNotice(data.expiryNotice);
                }
            }
        })
        .catch(error => {
            console.error('Error checking subscription:', error);
        });
}

// Update subscription UI based on status
function updateSubscriptionUI(subscription) {
    if (!subscription) return;
    
    const subscriptionBadge = document.getElementById('subscriptionBadge');
    if (subscriptionBadge) {
        if (subscription.paymentStatus === 'Success' && subscription.isActive) {
            subscriptionBadge.textContent = subscription.planName + ' Plan';
            subscriptionBadge.className = 'badge badge-completed';
        } else {
            subscriptionBadge.textContent = 'Subscription Required';
            subscriptionBadge.className = 'badge badge-warning';
        }
    }
    
    // Update subscription expiry display
    const expiryDisplay = document.getElementById('subscriptionExpiry');
    if (expiryDisplay && subscription.subscriptionEnd) {
        const expiryDate = new Date(subscription.subscriptionEnd);
        const daysLeft = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
        
        if (daysLeft < 0) {
            expiryDisplay.innerHTML = '<span style="color: var(--red);">Expired</span>';
        } else if (daysLeft <= 7) {
            expiryDisplay.innerHTML = `<span style="color: var(--orange);">Expires in ${daysLeft} days</span>`;
        } else {
            expiryDisplay.textContent = `Expires: ${expiryDate.toLocaleDateString()}`;
        }
    }
}

// Show expiry notice
function showExpiryNotice(notice) {
    if (!notice) return;
    
    const noticeDiv = document.createElement('div');
    noticeDiv.className = `alert alert-${notice.type === 'expired' ? 'error' : 'warning'}`;
    noticeDiv.innerHTML = `
        <strong>Subscription Notice:</strong> ${notice.message}
        ${notice.type === 'expired' ? 
            '<a href="/WasteJustice/views/subscription.php" class="btn btn-primary" style="margin-left: 1rem;">Renew Now</a>' : 
            '<button onclick="this.parentElement.remove()" style="margin-left: 1rem;">Dismiss</button>'
        }
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(noticeDiv, container.firstChild);
    }
}

// Renew subscription
function renewSubscription(subscriptionID) {
    if (!confirm('Renew your subscription for another month?')) {
        return;
    }
    
    fetch('actions/renew_subscription_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `subscriptionID=${subscriptionID}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Subscription renewed successfully!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showNotification(data.message || 'Renewal failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Cancel subscription
function cancelSubscription() {
    if (!confirm('Are you sure you want to cancel your subscription? You will lose access to premium features.')) {
        return;
    }
    
    fetch('actions/cancel_subscription_action.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Subscription cancelled successfully', 'success');
            setTimeout(() => {
                window.location.href = '/WasteJustice/views/subscription.php';
            }, 1500);
        } else {
            showNotification(data.message || 'Cancellation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    handleSubscriptionForm();
    
    // Check subscription status if user is logged in
    const userID = <?php echo isset($_SESSION['userID']) ? $_SESSION['userID'] : 'null'; ?>;
    if (userID) {
        checkSubscriptionStatus(userID);
        
        // Check status every 5 minutes
        setInterval(() => checkSubscriptionStatus(userID), 300000);
    }
});

