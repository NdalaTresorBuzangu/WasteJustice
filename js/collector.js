/**
 * WasteJustice - Collector JavaScript
 * Handles waste upload, aggregator selection, pricing display, payment confirmation
 */

// Upload waste form handler
function uploadWaste() {
    const form = document.getElementById('uploadWasteForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        // Show loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        
        fetch('actions/upload_waste_action.php', {
            method: 'POST',
            body: formData
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
                showNotification('Waste uploaded successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data?.message || 'Upload failed', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
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

// Get nearest aggregators with transparent pricing
function getNearestAggregators(plasticTypeID) {
    // Get user location (if available)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                loadAggregators(lat, lng, plasticTypeID);
            },
            error => {
                console.error('Geolocation error:', error);
                loadAggregators(null, null, plasticTypeID); // Load without distance calculation
            }
        );
    } else {
        loadAggregators(null, null, plasticTypeID);
    }
}

function loadAggregators(lat, lng, plasticTypeID) {
    const params = new URLSearchParams({
        plasticTypeID: plasticTypeID,
        lat: lat || '',
        lng: lng || ''
    });
    
        fetch(`api/get_aggregators.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAggregators(data.aggregators);
            }
        })
        .catch(error => {
            console.error('Error loading aggregators:', error);
        });
}

function displayAggregators(aggregators) {
    const container = document.getElementById('aggregatorsList');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (aggregators.length === 0) {
        container.innerHTML = '<p>No aggregators found for this plastic type.</p>';
        return;
    }
    
    aggregators.forEach(agg => {
        const card = document.createElement('div');
        card.className = 'aggregator-card';
        card.innerHTML = `
            <h3>${agg.businessName}</h3>
            <p><strong>Contact:</strong> ${agg.contact}</p>
            <p><strong>Address:</strong> ${agg.address}</p>
            <p><strong>Rating:</strong> ${renderStars(agg.rating)} (${agg.totalRatings} reviews)</p>
            <p><strong>Price:</strong> <span class="price">GH₵ ${parseFloat(agg.pricePerKg).toFixed(2)}/kg</span></p>
            ${agg.distance ? `<p><strong>Distance:</strong> ${agg.distance.toFixed(2)} km</p>` : ''}
            <button onclick="selectAggregator(${agg.aggregatorID}, ${agg.plasticTypeID})" class="btn btn-primary">
                Select This Aggregator
            </button>
        `;
        container.appendChild(card);
    });
}

function renderStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    let stars = '⭐'.repeat(fullStars);
    if (halfStar) stars += '⭐';
    return stars;
}

function selectAggregator(aggregatorID, plasticTypeID) {
    const collectionID = document.getElementById('currentCollectionID')?.value;
    if (!collectionID) {
        showNotification('Please upload waste first', 'warning');
        return;
    }
    
    if (confirm('Assign this aggregator to your waste collection?')) {
        fetch('actions/assign_aggregator_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `collectionID=${collectionID}&aggregatorID=${aggregatorID}`
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to assign aggregator', 'error');
        });
    }
}

// View transparent prices
function showTransparentPrices(plasticTypeID) {
        fetch(`api/get_prices.php?plasticTypeID=${plasticTypeID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPrices(data.prices);
            }
        });
}

function displayPrices(prices) {
    const container = document.getElementById('pricesDisplay');
    if (!container) return;
    
    container.innerHTML = '<h3>Transparent Pricing</h3>';
    
    prices.forEach(price => {
        const div = document.createElement('div');
        div.className = 'price-item';
        div.innerHTML = `
            <strong>${price.aggregatorName}:</strong> 
            GH₵ ${parseFloat(price.pricePerKg).toFixed(2)}/kg
            <span class="rating">${renderStars(price.rating)}</span>
        `;
        container.appendChild(div);
    });
}

// Payment confirmation
function checkPaymentStatus(collectionID) {
        fetch(`api/payment_status.php?collectionID=${collectionID}`)
        .then(response => response.json())
        .then(data => {
            if (data.paid) {
                showPaymentConfirmation(data.payment);
            }
        });
}

function showPaymentConfirmation(payment) {
    const notification = document.createElement('div');
    notification.className = 'payment-confirmation';
    notification.innerHTML = `
        <h3>💵 Payment Received!</h3>
        <p><strong>Amount:</strong> GH₵ ${parseFloat(payment.amount).toFixed(2)}</p>
        <p><strong>Method:</strong> ${payment.paymentMethod}</p>
        <p><strong>Reference:</strong> ${payment.referenceNumber || 'N/A'}</p>
        <button onclick="this.parentElement.remove()">Close</button>
    `;
    document.body.appendChild(notification);
}

// Notification helper
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
    uploadWaste();
    
    // Auto-check payment status for pending collections
    const pendingCollections = document.querySelectorAll('[data-collection-status="pending"]');
    pendingCollections.forEach(element => {
        const collectionID = element.dataset.collectionId;
        setInterval(() => checkPaymentStatus(collectionID), 10000); // Check every 10 seconds
    });
});

