/**
 * WasteJustice - Feedback System JavaScript
 * Records ratings and reviews asynchronously for all roles
 */

// Submit feedback
function submitFeedback(fromUserID, toUserID, rating, comment, collectionID = null, batchID = null) {
    const formData = new FormData();
    formData.append('fromUserID', fromUserID);
    formData.append('toUserID', toUserID);
    formData.append('rating', rating);
    formData.append('comment', comment);
    if (collectionID) formData.append('collectionID', collectionID);
    if (batchID) formData.append('batchID', batchID);
    
    return fetch('actions/feedback_action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Feedback submitted successfully!', 'success');
            return true;
        } else {
            showNotification(data.message || 'Failed to submit feedback', 'error');
            return false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        return false;
    });
}

// Show feedback modal
function showFeedbackModal(toUserID, userName, collectionID = null, batchID = null) {
    const modal = document.createElement('div');
    modal.className = 'feedback-modal modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            <h2>Leave Feedback for ${userName}</h2>
            <form id="feedbackForm">
                <input type="hidden" name="toUserID" value="${toUserID}">
                ${collectionID ? `<input type="hidden" name="collectionID" value="${collectionID}">` : ''}
                ${batchID ? `<input type="hidden" name="batchID" value="${batchID}">` : ''}
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="rating-stars" id="ratingStars">
                        ${[1,2,3,4,5].map(i => `<span class="star" data-rating="${i}" onclick="selectStar(${i})">⭐</span>`).join('')}
                    </div>
                    <input type="hidden" name="rating" id="selectedRating" required>
                </div>
                <div class="form-group">
                    <label>Comment:</label>
                    <textarea name="comment" rows="4" placeholder="Share your experience..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Handle form submission
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const rating = document.getElementById('selectedRating').value;
        if (!rating) {
            showNotification('Please select a rating', 'warning');
            return;
        }
        
        const formData = new FormData(e.target);
        const fromUserID = <?php echo isset($_SESSION['userID']) ? $_SESSION['userID'] : 'null'; ?>;
        formData.append('fromUserID', fromUserID);
        
        submitFeedback(
            fromUserID,
            formData.get('toUserID'),
            rating,
            formData.get('comment'),
            formData.get('collectionID'),
            formData.get('batchID')
        ).then(success => {
            if (success) {
                modal.remove();
            }
        });
    });
}

// Select star rating
function selectStar(rating) {
    document.getElementById('selectedRating').value = rating;
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.opacity = '1';
            star.style.transform = 'scale(1.2)';
        } else {
            star.style.opacity = '0.3';
            star.style.transform = 'scale(1)';
        }
    });
}

// Display rating stars
function displayRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let html = '⭐'.repeat(fullStars);
    if (hasHalfStar) html += '⭐';
    const emptyStars = 5 - Math.ceil(rating);
    if (emptyStars > 0) html += '<span style="opacity:0.3">⭐'.repeat(emptyStars) + '</span>';
    return html;
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
    // Make feedback buttons interactive
    document.querySelectorAll('[data-feedback]').forEach(button => {
        button.addEventListener('click', function() {
            const toUserID = this.dataset.toUserid;
            const userName = this.dataset.username;
            const collectionID = this.dataset.collectionId || null;
            const batchID = this.dataset.batchId || null;
            showFeedbackModal(toUserID, userName, collectionID, batchID);
        });
    });
});

