<?php
require_once __DIR__ . '/includes/session_config.php';
require_once 'includes/functions.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get display name (fallback to username)
$display_name = $_SESSION["display_name"] ?? $_SESSION["username"];
$role = ucfirst($_SESSION["role"]);
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="favicon-192.png">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            color: #333; 
        }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px); 
            border-radius: 15px; 
            padding: 20px 30px; 
            margin-bottom: 30px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); 
        }
        .header-logo { font-size: 1.5rem; font-weight: bold; color: #667eea; display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; }
        .header-user-info { display: flex; align-items: center; gap: 15px; }
        .welcome-link {
            cursor: pointer;
            padding: 8px 15px;
            borderRadius: 8px;
            background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid #667eea;
            font-weight: 500;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
        }
        .btn { 
            background: linear-gradient(45deg, #667eea, #764ba2); 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            font-weight: 600; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
        
        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        .back-link:hover { opacity: 1; text-decoration: underline; }
        
        /* Profile Card */
        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
        }
        .avatar-section { position: relative; }
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            border: 4px solid rgba(255,255,255,0.3);
            overflow: hidden;
        }
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-upload {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            border: none;
            font-size: 1.1rem;
        }
        .avatar-upload:hover { background: #f0f0f0; }
        .profile-info { flex: 1; }
        .profile-name { font-size: 1.8rem; font-weight: bold; margin-bottom: 5px; }
        .profile-title { font-size: 1rem; opacity: 0.9; margin-bottom: 3px; }
        .profile-username { font-size: 0.85rem; opacity: 0.7; }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e1e5e9;
            background: #f8f9fa;
        }
        .tab {
            padding: 15px 25px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab:hover { color: #667eea; }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: white;
        }
        
        /* Form Section */
        .form-section { padding: 30px; display: none; }
        .form-section.active { display: block; }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e5e9;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
            font-size: 0.9rem;
        }
        .form-group label .hint {
            font-weight: normal;
            color: #6c757d;
            font-size: 0.8rem;
        }
        .form-group input, .form-group select {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group input.readonly {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        /* Button Row */
        .button-row {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e1e5e9;
        }
        
        /* Messages */
        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .message.show { display: flex; }
        
        /* Password Strength */
        .password-strength {
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background-color 0.3s;
        }
        .password-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* File Input */
        .file-input { display: none; }
        
        /* Photo Options */
        .photo-options {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .photo-option-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
        }
        .photo-option-btn.upload { background: #667eea; color: white; }
        .photo-option-btn.remove { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-logo">
                <img src="logo.png" alt="Logo">
            </div>
            <div class="header-user-info">
                <a href="profile.php" class="welcome-link">👤 Welcome, <b><?php echo htmlspecialchars($display_name); ?></b> (<?php echo $role; ?>)</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
        
        <!-- Back Link -->
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="avatar-section">
                    <div class="avatar" id="avatarDisplay">
                        <span id="avatarInitials"></span>
                    </div>
                    <button class="avatar-upload" onclick="document.getElementById('photoInput').click()" title="Upload Photo">📷</button>
                    <input type="file" id="photoInput" class="file-input" accept="image/jpeg,image/png,image/gif,image/webp" onchange="uploadPhoto(this)">
                </div>
                <div class="profile-info">
                    <div class="profile-name" id="headerDisplayName">Loading...</div>
                    <div class="profile-title" id="headerJobTitle">—</div>
                    <div class="profile-username" id="headerUsername">@</div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" data-tab="profile" onclick="switchTab('profile')">👤 Profile Information</button>
                <button class="tab" data-tab="security" onclick="switchTab('security')">🔒 Security</button>
            </div>
            
            <!-- Profile Information Tab -->
            <div class="form-section active" id="profileSection">
                <div class="message success" id="profileSuccessMsg">✅ Profile updated successfully!</div>
                <div class="message error" id="profileErrorMsg"></div>
                
                <h3 class="section-title">Display Name</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="firstName" placeholder="Enter first name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" id="lastName" placeholder="Enter last name" required>
                    </div>
                </div>
                
                <h3 class="section-title">Contact Information</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Email Address <span class="hint">(for contact purposes)</span></label>
                        <input type="email" id="email" placeholder="email@company.com">
                    </div>
                    <div class="form-group">
                        <label>Work Phone</label>
                        <input type="tel" id="workPhone" placeholder="(xxx) xxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label>Mobile Phone</label>
                        <input type="tel" id="mobilePhone" placeholder="(xxx) xxx-xxxx">
                    </div>
                </div>
                
                <h3 class="section-title">Professional Information</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Job Title</label>
                        <input type="text" id="jobTitle" placeholder="e.g., Business Development Manager">
                    </div>
                </div>
                
                <h3 class="section-title">Login Information</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Username <span class="hint">(cannot be changed)</span></label>
                        <input type="text" id="username" class="readonly" readonly>
                    </div>
                </div>
                
                <div class="button-row">
                    <button class="btn" onclick="saveProfile()">💾 Save Changes</button>
                    <button class="btn btn-secondary" onclick="loadProfile()">Cancel</button>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div class="form-section" id="securitySection">
                <div class="message success" id="passwordSuccessMsg">✅ Password updated successfully!</div>
                <div class="message error" id="passwordErrorMsg"></div>
                
                <h3 class="section-title">Change Password</h3>
                <p style="color: #6c757d; margin-bottom: 20px;">
                    Update your password to keep your account secure. You'll need to enter your current password to confirm the change.
                </p>
                
                <div style="max-width: 400px;">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Current Password</label>
                        <input type="password" id="currentPassword" placeholder="Enter current password">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>New Password</label>
                        <input type="password" id="newPassword" placeholder="Enter new password" oninput="updatePasswordStrength()">
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <span class="password-hint">Password should be at least 6 characters</span>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPassword" placeholder="Confirm new password">
                    </div>
                </div>
                
                <div class="button-row">
                    <button class="btn" onclick="changePassword()">🔒 Update Password</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    const API_URL = 'api.php';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    function csrfHeaders(extraHeaders = {}) {
        return { 'X-CSRF-Token': CSRF_TOKEN, ...extraHeaders };
    }
    let currentProfile = null;
    
    document.addEventListener('DOMContentLoaded', loadProfile);
    
    async function loadProfile() {
        try {
            const response = await fetch(`${API_URL}?action=getProfile`);
            const data = await response.json();
            
            if (data.success) {
                currentProfile = data.profile;
                populateForm();
                updateHeader();
            } else {
                showMessage('profileErrorMsg', data.error || 'Failed to load profile');
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            showMessage('profileErrorMsg', 'Error loading profile');
        }
    }
    
    function populateForm() {
        if (!currentProfile) return;
        
        document.getElementById('firstName').value = currentProfile.first_name || '';
        document.getElementById('lastName').value = currentProfile.last_name || '';
        document.getElementById('email').value = currentProfile.email || '';
        document.getElementById('workPhone').value = currentProfile.work_phone || '';
        document.getElementById('mobilePhone').value = currentProfile.mobile_phone || '';
        document.getElementById('jobTitle').value = currentProfile.job_title || '';
        document.getElementById('username').value = currentProfile.username || '';
    }
    
    function updateHeader() {
        if (!currentProfile) return;
        
        const firstName = currentProfile.first_name || '';
        const lastName = currentProfile.last_name || '';
        const displayName = currentProfile.display_name || currentProfile.username;
        const jobTitle = currentProfile.job_title || 'No title set';
        
        document.getElementById('headerDisplayName').textContent = displayName;
        document.getElementById('headerJobTitle').textContent = jobTitle;
        document.getElementById('headerUsername').textContent = '@' + currentProfile.username;
        
        // Update avatar
        const avatarDisplay = document.getElementById('avatarDisplay');
        if (currentProfile.profile_photo) {
            avatarDisplay.innerHTML = `<img src="${currentProfile.profile_photo}" alt="Profile Photo">`;
        } else {
            const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase() || currentProfile.username.charAt(0).toUpperCase();
            avatarDisplay.innerHTML = `<span id="avatarInitials">${initials}</span>`;
        }
    }
    
    async function saveProfile() {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        
        if (!firstName || !lastName) {
            showMessage('profileErrorMsg', 'First name and last name are required');
            return;
        }
        
        const profileData = {
            first_name: firstName,
            last_name: lastName,
            email: document.getElementById('email').value.trim(),
            work_phone: document.getElementById('workPhone').value.trim(),
            mobile_phone: document.getElementById('mobilePhone').value.trim(),
            job_title: document.getElementById('jobTitle').value.trim()
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveProfile`, {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify(profileData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('profileSuccessMsg');
                // Update local data
                currentProfile.first_name = profileData.first_name;
                currentProfile.last_name = profileData.last_name;
                currentProfile.display_name = data.display_name;
                currentProfile.email = profileData.email;
                currentProfile.work_phone = profileData.work_phone;
                currentProfile.mobile_phone = profileData.mobile_phone;
                currentProfile.job_title = profileData.job_title;
                updateHeader();
                
                // Update welcome message in page header
                document.querySelector('.welcome-link b').textContent = data.display_name;
            } else {
                showMessage('profileErrorMsg', data.error || 'Failed to save profile');
            }
        } catch (error) {
            console.error('Error saving profile:', error);
            showMessage('profileErrorMsg', 'Error saving profile');
        }
    }
    
    async function uploadPhoto(input) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        const formData = new FormData();
        formData.append('photo', file);
        
        try {
            const response = await fetch(`${API_URL}?action=uploadProfilePhoto`, {
                method: 'POST',
                headers: csrfHeaders(),
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentProfile.profile_photo = data.photo_url;
                updateHeader();
                showMessage('profileSuccessMsg', '✅ Photo uploaded successfully!');
            } else {
                showMessage('profileErrorMsg', data.error || 'Failed to upload photo');
            }
        } catch (error) {
            console.error('Error uploading photo:', error);
            showMessage('profileErrorMsg', 'Error uploading photo');
        }
        
        // Reset file input
        input.value = '';
    }
    
    async function deletePhoto() {
        if (!confirm('Are you sure you want to remove your profile photo?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteProfilePhoto`, {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentProfile.profile_photo = null;
                updateHeader();
                showMessage('profileSuccessMsg', '✅ Photo removed successfully!');
            } else {
                showMessage('profileErrorMsg', data.error || 'Failed to remove photo');
            }
        } catch (error) {
            console.error('Error removing photo:', error);
            showMessage('profileErrorMsg', 'Error removing photo');
        }
    }
    
    async function changePassword() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            showMessage('passwordErrorMsg', 'All password fields are required');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showMessage('passwordErrorMsg', 'New passwords do not match');
            return;
        }
        
        if (newPassword.length < 6) {
            showMessage('passwordErrorMsg', 'New password must be at least 6 characters');
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=changePassword`, {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage('passwordSuccessMsg');
                // Clear password fields
                document.getElementById('currentPassword').value = '';
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                updatePasswordStrength();
            } else {
                showMessage('passwordErrorMsg', data.error || 'Failed to change password');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showMessage('passwordErrorMsg', 'Error changing password');
        }
    }
    
    function updatePasswordStrength() {
        const password = document.getElementById('newPassword').value;
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        let strength = 0;
        if (password.length >= 6) strength += 25;
        if (password.length >= 10) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        
        if (strength <= 25) {
            strengthBar.style.backgroundColor = '#dc3545';
        } else if (strength <= 50) {
            strengthBar.style.backgroundColor = '#ffc107';
        } else if (strength <= 75) {
            strengthBar.style.backgroundColor = '#28a745';
        } else {
            strengthBar.style.backgroundColor = '#20c997';
        }
    }
    
    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });
        
        // Update sections
        document.getElementById('profileSection').classList.toggle('active', tabName === 'profile');
        document.getElementById('securitySection').classList.toggle('active', tabName === 'security');
        
        // Hide messages when switching tabs
        hideAllMessages();
    }
    
    function showMessage(elementId, customMessage = null) {
        hideAllMessages();
        const element = document.getElementById(elementId);
        if (customMessage) {
            element.textContent = customMessage;
        }
        element.classList.add('show');
        
        // Auto-hide success messages
        if (elementId.includes('Success')) {
            setTimeout(() => {
                element.classList.remove('show');
            }, 3000);
        }
    }
    
    function hideAllMessages() {
        document.querySelectorAll('.message').forEach(msg => msg.classList.remove('show'));
    }
    </script>
</body>
</html>
