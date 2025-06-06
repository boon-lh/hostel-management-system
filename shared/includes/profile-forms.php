<?php
function display_profile_edit_form($name, $email, $phone, $office_number, $username) {
?>
<div class="tab-content active" id="edit-profile">
    <div class="form-section-header">
        <h3>Update your profile information below</h3>
        <p>Fill in only the fields you want to update and click "Save Changes" when you're done.</p>
    </div>
    <form action="" method="post">
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email address" required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-phone"></i> Contact Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" class="form-control" id="phone" name="contact_no" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Enter your phone number" required>
                </div>
                <div class="form-group">
                    <label for="office_number">Office Number</label>
                    <input type="text" class="form-control" id="office_number" name="office_number" value="<?php echo htmlspecialchars($office_number); ?>" placeholder="Enter your office number">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-user-shield"></i> Account Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="admin_username">Username</label>
                    <input type="text" class="form-control" id="admin_username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" readonly>
                    <small class="form-text text-muted">Username cannot be changed</small>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <input type="hidden" name="update_profile" value="1">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Profile Changes
            </button>
        </div>
    </form>
</div>
<?php
}

function display_security_form() {
?>
<div class="tab-content" id="security">
    <div class="form-section">
        <h3><i class="fas fa-key"></i> Change Password</h3>
        <form action="" method="post">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <small class="form-text text-muted">Password should be at least 8 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <input type="hidden" name="change_password" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>
<?php
}
?>
