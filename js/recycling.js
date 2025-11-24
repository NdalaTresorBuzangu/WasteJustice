/**
 * WasteJustice - Recycling Company JavaScript
 * Handles purchase verification and feedback submission
 */

// Verify quality and purchase batch
function verifyAndPurchase(batchID) {
    const quality = confirm('Verify quality of this batch?\n\nOK = Quality Verified\nCancel = Quality Issue');
    
    fetch('actions/verify_purchase_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `batchID=${batchID}&qualityVerified=${quality ? '1' : '0'}`
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
            showNotification(`Purchase confirmed! Amount: GH₵ ${data.salePrice.toFixed(2)}`, 'success');
            setTimeout(() => {
                // Show feedback form
                showFeedbackForm(data.batchID, data.aggregatorID);
            }, 1500);
        } else {
            showNotification(data?.message || 'Purchase failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Show feedback form
function showFeedbackForm(batchID, aggregatorID) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h2>Leave Feedback</h2>
            <form id="feedbackForm">
                <input type="hidden" name="toUserID" value="${aggregatorID}">
                <input type="hidden" name="batchID" value="${batchID}">
                <div class="form-group">
                    <label>Rating (1-5):</label>
                    <div class="rating-input">
                        ${[1,2,3,4,5].map(i => `<span class="star" onclick="setRating(${i})" data-rating="${i}">⭐</span>`).join('')}
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" required>
                </div>
                <div class="form-group">
                    <label>Comment:</label>
                    <textarea name="comment" rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    
    document.getElementById('feedbackForm').addEventListener('submit', submitFeedback);
}

function setRating(rating) {
    document.getElementById('ratingValue').value = rating;
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.opacity = '1';
        } else {
            star.style.opacity = '0.3';
        }
    });
}

function submitFeedback(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    fetch('actions/feedback_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Feedback submitted successfully!', 'success');
            document.querySelector('.modal').remove();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message || 'Failed to submit feedback', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Filter batches by plastic type
function filterBatches(plasticTypeID) {
    const url = plasticTypeID ? 
        `recycling_dashboard.php?plasticTypeID=${plasticTypeID}` : 
        'recycling_dashboard.php';
    window.location.href = url;
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
    // Auto-refresh available batches every 30 seconds
    if (window.location.pathname.includes('recycling_dashboard')) {
        setInterval(() => {
            const batchesSection = document.getElementById('availableBatches');
            if (batchesSection) {
                location.reload();
            }
        }, 30000);
    }
});

