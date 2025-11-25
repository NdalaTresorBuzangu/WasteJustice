<?php
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';

$role = isset($_GET['role']) ? $_GET['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2>🌍 Join <?php echo APP_NAME; ?></h2>
                <p>Create your account and start making impact</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem; padding: 1rem; background: #ffebee; color: #c62828; border-radius: 0.5rem; border-left: 4px solid #c62828;">
                    <?php 
                    $error = $_GET['error'];
                    if ($error == 'address_required') {
                        $roleParam = $_GET['role'] ?? '';
                        if ($roleParam == 'company') {
                            echo '⚠️ <strong>Address Required:</strong> Company address is mandatory for recycling companies. Please enter your company address.';
                        } else {
                            echo '⚠️ <strong>Address Required:</strong> Business address is mandatory for aggregators. Please enter your business address.';
                        }
                    } elseif ($error == 'business_name_required') {
                        echo '⚠️ <strong>Business Name Required:</strong> Please enter a valid business name (at least 3 characters).';
                    } elseif ($error == 'company_name_required') {
                        echo '⚠️ <strong>Company Name Required:</strong> Please enter a valid company name (at least 3 characters).';
                    } elseif ($error == 'failed') {
                        $message = isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : 'Registration failed. Please try again.';
                        echo '✗ <strong>Registration Failed:</strong> ' . $message;
                    } elseif ($error == 'exists') {
                        echo '✗ <strong>Email Already Exists:</strong> This email is already registered. Please use a different email or <a href="' . VIEWS_URL . '/auth/login.php" style="color: inherit; text-decoration: underline;">login here</a>.';
                    } elseif ($error == 'failed') {
                        echo '✗ <strong>Registration Failed:</strong> Please try again or contact support.';
                    } else {
                        echo '✗ <strong>Error:</strong> ' . htmlspecialchars($error);
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo ACTIONS_URL; ?>/auth/signup_action.php">
                <div class="form-group">
                    <label for="userName">Full Name</label>
                    <input 
                        type="text" 
                        id="userName" 
                        name="userName" 
                        required 
                        placeholder="Enter your full name"
                    >
                </div>

                <div class="form-group">
                    <label for="userContact">Phone Number</label>
                    <input 
                        type="tel" 
                        id="userContact" 
                        name="userContact" 
                        required 
                        placeholder="+233 XXX XXX XXX"
                    >
                </div>

                <div class="form-group">
                    <label for="userEmail">Email Address</label>
                    <input 
                        type="email" 
                        id="userEmail" 
                        name="userEmail" 
                        required 
                        placeholder="your@email.com"
                    >
                </div>

                <div class="form-group">
                    <label for="userRole">I am a...</label>
                    <select id="userRole" name="userRole" required onchange="toggleRoleFields()">
                        <option value="">Select your role</option>
                        <option value="Waste Collector" <?php echo $role == 'collector' ? 'selected' : ''; ?>>
                            ♻️ Waste Collector
                        </option>
                        <option value="Aggregator" <?php echo $role == 'aggregator' ? 'selected' : ''; ?>>
                            📦 Aggregator
                        </option>
                        <option value="Recycling Company" <?php echo $role == 'company' ? 'selected' : ''; ?>>
                            🏭 Recycling Company
                        </option>
                    </select>
                
                <div id="aggregator-fields" style="display: none;">
                    <div class="form-group">
                        <label for="businessName">
                            <i class="fas fa-building"></i> Business Name <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="businessName" 
                            name="businessName" 
                            placeholder="e.g., Green Collection Hub, Eco Waste Center"
                        >
                    </div>
                    <div class="form-group">
                        <label for="address">
                            📍 Business Address <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="address" 
                            name="aggregatorAddress" 
                            placeholder="e.g., Kaneshie Market, Accra"
                        >
                        <small style="color: var(--gray); display: block; margin-top: 0.25rem;">
                            Enter your business address (required for aggregators)
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="businessLicense">Business License Number (Optional)</label>
                        <input 
                            type="text" 
                            id="businessLicense" 
                            name="businessLicense" 
                            placeholder="License number (optional)"
                        >
                    </div>
                </div>
                
                <div id="company-fields" style="display: none;">
                    <div class="form-group">
                        <label for="companyName">
                            🏭 Company Name <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="companyName" 
                            name="companyName" 
                            placeholder="e.g., RecycleGhana Ltd"
                        >
                    </div>
                    <div class="form-group">
                        <label for="companyAddress">
                            📍 Company Address <span style="color: var(--error);">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="companyAddress" 
                            name="companyAddress" 
                            placeholder="e.g., Industrial Area, Accra"
                        >
                        <small style="color: var(--gray); display: block; margin-top: 0.25rem;">
                            Enter your company address (required for recycling companies)
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="companyBusinessLicense">Business License Number (Optional)</label>
                        <input 
                            type="text" 
                            id="companyBusinessLicense" 
                            name="businessLicense" 
                            placeholder="License number (optional)"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="userPassword">Password</label>
                    <input 
                        type="password" 
                        id="userPassword" 
                        name="userPassword" 
                        required 
                        placeholder="Create a password"
                        minlength="6"
                    >
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirmPassword" 
                        name="confirmPassword" 
                        required 
                        placeholder="Confirm your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Create WasteJustice Account
                </button>
            </form>

            <div class="text-center mt-3">
                <p style="color: var(--gray);">
                    Already have an account? 
                    <a href="<?php echo VIEWS_URL; ?>/auth/login.php" style="color: var(--primary-green); font-weight: 600; text-decoration: none;">
                        Login here
                    </a>
                </p>
                <p style="margin-top: 1rem;">
                    <a href="<?php echo BASE_URL; ?>/index.php" style="color: var(--gray); text-decoration: none; margin-right: 1rem;">
                        ← Back to Home
                    </a>
                    <a href="<?php echo BASE_URL; ?>/about.php" style="color: var(--gray); text-decoration: none;">
                        About Us
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('userRole').value;
            const aggFields = document.getElementById('aggregator-fields');
            const companyFields = document.getElementById('company-fields');
            
            // Show/hide aggregator fields
            if (aggFields) {
                if (role === 'Aggregator') {
                    aggFields.style.display = 'block';
                } else {
                    aggFields.style.display = 'none';
                    // Clear aggregator fields when hidden
                    const addressField = document.getElementById('address');
                    const businessNameField = document.getElementById('businessName');
                    if (addressField) addressField.value = '';
                    if (businessNameField) businessNameField.value = '';
                }
            }
            
            // Show/hide company fields
            if (companyFields) {
                if (role === 'Recycling Company') {
                    companyFields.style.display = 'block';
                } else {
                    companyFields.style.display = 'none';
                    // Clear company fields when hidden
                    const companyAddressField = document.getElementById('companyAddress');
                    const companyNameField = document.getElementById('companyName');
                    if (companyAddressField) companyAddressField.value = '';
                    if (companyNameField) companyNameField.value = '';
                }
            }
        }
        
        // Get GPS location from address using geocoding (for aggregators)
        function getLocationForAddress() {
            const address = document.getElementById('address').value;
            const statusDiv = document.getElementById('locationStatus');
            const getLocationBtn = document.getElementById('getLocationBtn');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            
            if (!address || address.length < 5) {
                statusDiv.innerHTML = '<span style="color: var(--error);">⚠️ Please enter a valid address first (at least 5 characters)</span>';
                statusDiv.style.display = 'block';
                return;
            }
            
            getLocationBtn.disabled = true;
            getLocationBtn.textContent = '⏳ Getting location...';
            statusDiv.innerHTML = '<span style="color: var(--primary-green);">📍 Looking up address...</span>';
            statusDiv.style.display = 'block';
            
            // Use browser geolocation first (more accurate)
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // User's current location (they might be at their business)
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        latInput.value = lat.toFixed(8);
                        lngInput.value = lng.toFixed(8);
                        latInput.readOnly = false; // Allow manual editing
                        lngInput.readOnly = false;
                        
                        statusDiv.innerHTML = '<span style="color: var(--primary-green);">✅ GPS location captured! You can edit the coordinates if needed.</span>';
                        getLocationBtn.disabled = false;
                        getLocationBtn.textContent = '📍 Update GPS Location';
                    },
                    function(error) {
                        // If geolocation fails, try geocoding API (requires API key)
                        // For now, show manual entry option
                        statusDiv.innerHTML = '<span style="color: var(--orange);">⚠️ Could not get automatic location. Please enter GPS coordinates manually or use Google Maps to find them.</span>';
                        latInput.readOnly = false;
                        lngInput.readOnly = false;
                        getLocationBtn.disabled = false;
                        getLocationBtn.textContent = '📍 Get GPS Location';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                statusDiv.innerHTML = '<span style="color: var(--orange);">⚠️ Geolocation not supported. Please enter GPS coordinates manually.</span>';
                latInput.readOnly = false;
                lngInput.readOnly = false;
                getLocationBtn.disabled = false;
            }
        }
        
        // Simple form validation - let server handle most validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('userPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const role = document.getElementById('userRole').value;
                
                // Basic client-side checks only
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (!role) {
                    e.preventDefault();
                    alert('Please select your role.');
                    return false;
                }
                
                // Additional validation for aggregators
                if (role === 'Aggregator') {
                    const address = document.getElementById('address');
                    const businessName = document.getElementById('businessName');
                    
                    if (!businessName || !businessName.value || businessName.value.trim().length < 2) {
                        e.preventDefault();
                        alert('⚠️ Business name is required for aggregators (at least 2 characters).');
                        if (businessName) businessName.focus();
                        return false;
                    }
                    
                    if (!address || !address.value || address.value.trim().length < 3) {
                        e.preventDefault();
                        alert('⚠️ Business address is required for aggregators (at least 3 characters).');
                        if (address) address.focus();
                        return false;
                    }
                }
                
                // Additional validation for recycling companies
                if (role === 'Recycling Company') {
                    const companyAddress = document.getElementById('companyAddress');
                    const companyName = document.getElementById('companyName');
                    
                    if (!companyName || !companyName.value || companyName.value.trim().length < 2) {
                        e.preventDefault();
                        alert('⚠️ Company name is required for recycling companies (at least 2 characters).');
                        if (companyName) companyName.focus();
                        return false;
                    }
                    
                    if (!companyAddress || !companyAddress.value || companyAddress.value.trim().length < 3) {
                        e.preventDefault();
                        alert('⚠️ Company address is required for recycling companies (at least 3 characters).');
                        if (companyAddress) companyAddress.focus();
                        return false;
                    }
                }
                
                // Let form submit normally - server will validate the rest
                return true;
            });
        }
        
        // Get GPS location for company address
        function getLocationForCompanyAddress() {
            const address = document.getElementById('companyAddress').value;
            const statusDiv = document.getElementById('companyLocationStatus');
            const getLocationBtn = document.getElementById('getCompanyLocationBtn');
            const latInput = document.getElementById('companyLatitude');
            const lngInput = document.getElementById('companyLongitude');
            
            if (!address || address.length < 5) {
                statusDiv.innerHTML = '<span style="color: var(--error);">⚠️ Please enter a valid address first (at least 5 characters)</span>';
                statusDiv.style.display = 'block';
                return;
            }
            
            getLocationBtn.disabled = true;
            getLocationBtn.textContent = '⏳ Getting location...';
            statusDiv.innerHTML = '<span style="color: var(--primary-green);">📍 Looking up address...</span>';
            statusDiv.style.display = 'block';
            
            // Use browser geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        latInput.value = lat.toFixed(8);
                        lngInput.value = lng.toFixed(8);
                        latInput.readOnly = false;
                        lngInput.readOnly = false;
                        
                        statusDiv.innerHTML = '<span style="color: var(--primary-green);">✅ GPS location captured! You can edit the coordinates if needed.</span>';
                        getLocationBtn.disabled = false;
                        getLocationBtn.textContent = '📍 Update GPS Location';
                    },
                    function(error) {
                        statusDiv.innerHTML = '<span style="color: var(--orange);">⚠️ Could not get automatic location. Please enter GPS coordinates manually or use Google Maps to find them.</span>';
                        latInput.readOnly = false;
                        lngInput.readOnly = false;
                        getLocationBtn.disabled = false;
                        getLocationBtn.textContent = '📍 Get GPS Location';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                statusDiv.innerHTML = '<span style="color: var(--orange);">⚠️ Geolocation not supported. Please enter GPS coordinates manually.</span>';
                latInput.readOnly = false;
                lngInput.readOnly = false;
                getLocationBtn.disabled = false;
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleRoleFields();
        });
    </script>
</body>
</html>
