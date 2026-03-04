<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

// Security Check - Redirect if not admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: login.php");
    exit;
}

$message = '';
$message_type = 'success';
$debug_info = '';

// DEBUG: Check what's being posted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $debug_info = "POST received. Keys: " . implode(', ', array_keys($_POST));
    if (isset($_POST['change_role_user_id'])) {
        $debug_info .= " | User ID: " . $_POST['change_role_user_id'] . " | New Role: " . ($_POST['new_role'] ?? 'NOT SET');
    }
}

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Handle User Approval
    if (isset($_POST['approve_user_id'])) {
        $user_id_to_approve = intval($_POST['approve_user_id']);
        $stmt = $conn->prepare("UPDATE users SET approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_approve);
        if ($stmt->execute()) {
            $message = "User has been approved successfully.";
        } else {
            $message = "Error: Could not approve user.";
            $message_type = 'error';
        }
        $stmt->close();
    }

    // Handle Role Change
    if (isset($_POST['change_role_user_id']) && isset($_POST['new_role'])) {
        $debug_info .= " | ROLE CHANGE HANDLER REACHED";
        
        $user_id_to_change = intval($_POST['change_role_user_id']);
        $new_role = trim($_POST['new_role']);
        $allowed_roles = ['admin', 'manager', 'user', 'specialty'];
        $current_user_id = intval($_SESSION['user_id']); // Cast to int for proper comparison
        
        $debug_info .= " | Parsed: uid=$user_id_to_change, role=$new_role, current_uid=$current_user_id";

        if (!in_array($new_role, $allowed_roles)) {
            $message = "Error: Invalid role '{$new_role}' specified.";
            $message_type = 'error';
        } else if ($user_id_to_change === $current_user_id) {
            $message = "Error: You cannot change your own role.";
            $message_type = 'error';
        } else if ($user_id_to_change > 0) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $new_role, $user_id_to_change);
                if ($stmt->execute()) {
                    $debug_info .= " | SQL executed, affected_rows=" . $stmt->affected_rows;
                    if ($stmt->affected_rows > 0) {
                        $conn->commit(); // Ensure change is committed
                        $message = "User role has been updated to '{$new_role}' successfully. The user must log out and log back in for the change to take effect.";
                    } else {
                        $message = "Warning: No changes made. User may already have this role or user not found.";
                        $message_type = 'error';
                    }
                } else {
                    $debug_info .= " | SQL execute failed: " . $stmt->error;
                    $message = "Error: Database error - " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $debug_info .= " | SQL prepare failed: " . $conn->error;
                $message = "Error: Could not prepare statement - " . $conn->error;
                $message_type = 'error';
            }
        } else {
            $message = "Error: Invalid user ID.";
            $message_type = 'error';
        }
    }
    
    // Handle Toggle Active/Inactive Status
    if (isset($_POST['toggle_active_user_id'])) {
        $user_id_to_toggle = intval($_POST['toggle_active_user_id']);
        $new_status = intval($_POST['new_active_status']);
        
        if ($user_id_to_toggle !== $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET active = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_status, $user_id_to_toggle);
            if ($stmt->execute()) {
                $status_text = $new_status ? "activated" : "deactivated";
                $message = "User has been {$status_text} successfully.";
            } else {
                $message = "Error: Could not update user status.";
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Error: You cannot deactivate your own account.";
            $message_type = 'error';
        }
    }
    
    // Handle Delete User
    if (isset($_POST['delete_user_id'])) {
        $user_id_to_delete = intval($_POST['delete_user_id']);
        
        if ($user_id_to_delete !== $_SESSION['user_id']) {
            // First check if user exists
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id_to_delete);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user_to_delete = $result->fetch_assoc();
                $stmt->close();
                
                // Delete the user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id_to_delete);
                if ($stmt->execute()) {
                    $message = "User '{$user_to_delete['username']}' has been deleted successfully.";
                } else {
                    $message = "Error: Could not delete user.";
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $message = "Error: User not found.";
                $message_type = 'error';
                $stmt->close();
            }
        } else {
            $message = "Error: You cannot delete your own account.";
            $message_type = 'error';
        }
    }
    
    // Handle Password Reset
    if (isset($_POST['reset_password_user_id']) && isset($_POST['new_password'])) {
        $user_id_to_reset = intval($_POST['reset_password_user_id']);
        $new_password = $_POST['new_password'];
        
        if (strlen($new_password) < 6) {
            $message = "Error: Password must be at least 6 characters.";
            $message_type = 'error';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id_to_reset);
            if ($stmt->execute()) {
                $message = "Password has been reset successfully.";
            } else {
                $message = "Error: Could not reset password.";
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Check if 'active' column exists, if not create it
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'active'");
if ($column_check->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1 AFTER approved");
}

// Fetch All Users
$users_result = $conn->query("SELECT id, username, role, created_at, approved, COALESCE(active, 1) as active FROM users ORDER BY created_at DESC");
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; background-color: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15); }
        h1 { color: #2c3e50; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        h2 { color: #2c3e50; margin-top: 0; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        
        /* Tab Styles */
        .admin-tabs { display: flex; gap: 5px; margin-bottom: 25px; border-bottom: 2px solid #e0e0e0; padding-bottom: 0; }
        .admin-tab { padding: 12px 25px; background: #f8f9fa; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 600; color: #555; transition: all 0.2s; margin-bottom: -2px; }
        .admin-tab:hover { background: #e9ecef; }
        .admin-tab.active { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border-bottom: 2px solid white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .user-table, .archive-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .user-table th, .user-table td, .archive-table th, .archive-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #e0e0e0; vertical-align: middle; }
        .user-table th, .archive-table th { background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #555; }
        .user-table tr:hover, .archive-table tr:hover { background-color: #f8f9ff; }
        .user-table tr.inactive-user { background-color: #f8f8f8; opacity: 0.7; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-active { background: #cce5ff; color: #004085; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .btn { border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; font-size: 12px; font-weight: 500; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-approve:hover { background-color: #218838; }
        .btn-role { background-color: #007bff; color: white; }
        .btn-role:hover { background-color: #0056b3; }
        .btn-deactivate { background-color: #ffc107; color: #333; }
        .btn-deactivate:hover { background-color: #e0a800; }
        .btn-activate { background-color: #17a2b8; color: white; }
        .btn-activate:hover { background-color: #138496; }
        .btn-delete { background-color: #dc3545; color: white; }
        .btn-delete:hover { background-color: #c82333; }
        .btn-reset { background-color: #6f42c1; color: white; }
        .btn-reset:hover { background-color: #5a32a3; }
        .btn-restore { background-color: #28a745; color: white; }
        .btn-restore:hover { background-color: #218838; }
        .btn-permanent-delete { background-color: #dc3545; color: white; }
        .btn-permanent-delete:hover { background-color: #c82333; }
        .role-select { padding: 5px 8px; border-radius: 6px; border: 1px solid #ccc; font-size: 12px; }
        .action-group { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
        .back-link { display: inline-block; margin-top: 20px; color: #667eea; text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        
        /* Archive Section Styles */
        .archive-section { margin-bottom: 30px; }
        .archive-section h3 { color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; }
        .archive-count { font-size: 0.85rem; color: #666; font-weight: normal; }
        .no-records { text-align: center; padding: 40px; color: #6c757d; font-style: italic; }
        .archive-info { font-size: 0.8rem; color: #6c757d; }
        .archive-meta { font-size: 0.75rem; color: #888; margin-top: 3px; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 5px 30px rgba(0,0,0,0.3); }
        .modal-content h3 { margin-top: 0; color: #333; }
        .modal-content input[type="password"] { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; margin: 15px 0; font-size: 14px; box-sizing: border-box; }
        .modal-content input[type="password"]:focus { outline: none; border-color: #667eea; }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .modal-buttons .btn { padding: 10px 20px; }
        .btn-cancel { background-color: #6c757d; color: white; }
        .btn-cancel:hover { background-color: #5a6268; }
        
        .user-count { font-size: 0.9rem; color: #666; font-weight: normal; margin-left: auto; }
        .current-user { background-color: #e8f4f8; }
        .current-user td:first-child::after { content: " (You)"; color: #667eea; font-weight: 500; font-size: 0.85em; }
        
        .loading { text-align: center; padding: 20px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ Admin Panel</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
            <div class="message" style="background: #fff3cd; color: #856404;"><?php echo htmlspecialchars($debug_info); ?></div>
        <?php endif; ?>
        
        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <button class="admin-tab active" onclick="showAdminTab('users', this)">👥 User Management</button>
            <button class="admin-tab" onclick="showAdminTab('archive', this)">📦 Archive Management</button>
        </div>
        
        <!-- User Management Tab -->
        <div id="users-tab" class="tab-content active">
            <h2>👥 User Management <span class="user-count"><?php echo count($users); ?> users</span></h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Username</th>
                    <th>Role</th>
                    <th>Registered</th>
                    <th>Approval</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): 
                    $is_current_user = ($user['id'] === $_SESSION['user_id']);
                    $is_inactive = !$user['active'];
                ?>
                    <tr class="<?php echo $is_current_user ? 'current-user' : ''; ?> <?php echo $is_inactive ? 'inactive-user' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td><?php echo date("M j, Y", strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['approved']): ?>
                                <span class="status-badge status-approved">Approved</span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <?php if (!$user['approved']): ?>
                                    <!-- Approve Button -->
                                    <form action="admin.php" method="post" style="margin:0;">
                                        <input type="hidden" name="approve_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-approve" title="Approve User">✓ Approve</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if (!$is_current_user): ?>
                                    <?php if ($user['approved']): ?>
                                        <!-- Role Change -->
                                        <form action="admin.php" method="post" style="margin:0; display: flex; gap: 5px;">
                                            <input type="hidden" name="change_role_user_id" value="<?php echo $user['id']; ?>">
                                            <select name="new_role" class="role-select">
                                                <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>User</option>
                                                <option value="manager" <?php if ($user['role'] === 'manager') echo 'selected'; ?>>Manager</option>
                                                <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                                                <option value="specialty" <?php if ($user['role'] === 'specialty') echo 'selected'; ?>>Specialty</option>
                                            </select>
                                            <button type="submit" class="btn btn-role" title="Update Role">Role</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Toggle Active/Inactive -->
                                    <form action="admin.php" method="post" style="margin:0;">
                                        <input type="hidden" name="toggle_active_user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="new_active_status" value="<?php echo $user['active'] ? 0 : 1; ?>">
                                        <?php if ($user['active']): ?>
                                            <button type="submit" class="btn btn-deactivate" title="Deactivate User" onclick="return confirm('Are you sure you want to deactivate this user?');">⏸ Deactivate</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-activate" title="Activate User">▶ Activate</button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <!-- Reset Password -->
                                    <button type="button" class="btn btn-reset" title="Reset Password" onclick="openResetModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>')">🔑 Reset</button>
                                    
                                    <!-- Delete User -->
                                    <form action="admin.php" method="post" style="margin:0;">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-delete" title="Delete User" onclick="return confirm('Are you sure you want to permanently delete user \'<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>\'? This action cannot be undone.');">🗑 Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <!-- Archive Management Tab -->
        <div id="archive-tab" class="tab-content">
            <h2>📦 Archive Management</h2>
            <p style="color: #6c757d; margin-bottom: 20px;">View and manage archived Opportunities and Proposals. You can restore records or permanently delete them.</p>
            
            <div id="archiveContent">
                <div class="loading">Loading archived records...</div>
            </div>
        </div>

        <a href="index.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <!-- Password Reset Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <h3>🔑 Reset Password</h3>
            <p>Enter a new password for <strong id="resetUsername"></strong>:</p>
            <form action="admin.php" method="post" id="resetForm">
                <input type="hidden" name="reset_password_user_id" id="resetUserId">
                <input type="password" name="new_password" id="newPassword" placeholder="New password (min 6 characters)" required minlength="6">
                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeResetModal()">Cancel</button>
                    <button type="submit" class="btn btn-reset">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        function showAdminTab(tabName, element) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.admin-tab').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            element.classList.add('active');
            
            // Load archive data when switching to archive tab
            if (tabName === 'archive') {
                loadArchivedRecords();
            }
        }
        
        // Load archived records
        async function loadArchivedRecords() {
            const container = document.getElementById('archiveContent');
            container.innerHTML = '<div class="loading">Loading archived records...</div>';
            
            try {
                const response = await fetch('api.php?action=getArchivedRecords');
                const data = await response.json();
                
                if (data.success) {
                    renderArchivedRecords(data.archived);
                } else {
                    container.innerHTML = '<div class="message error">' + (data.error || 'Failed to load archived records') + '</div>';
                }
            } catch (error) {
                console.error('Error loading archived records:', error);
                container.innerHTML = '<div class="message error">Error loading archived records. Please try again.</div>';
            }
        }
        
        // Render archived records
        function renderArchivedRecords(archived) {
            const container = document.getElementById('archiveContent');
            let html = '';
            
            // Archived Opportunities
            html += '<div class="archive-section">';
            html += '<h3>💼 Archived Opportunities <span class="archive-count">(' + (archived.opportunities?.length || 0) + ')</span></h3>';
            
            if (archived.opportunities && archived.opportunities.length > 0) {
                html += '<table class="archive-table">';
                html += '<thead><tr><th>Title</th><th>Agency</th><th>Value</th><th>Status</th><th>Archived</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                archived.opportunities.forEach(opp => {
                    const archivedDate = new Date(opp.archived_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    html += '<tr>';
                    html += '<td><strong>' + (opp.title || '') + '</strong>';
                    if (opp.archivedTasksCount > 0) {
                        html += '<div class="archive-meta">📋 ' + opp.archivedTasksCount + ' related task(s)</div>';
                    }
                    html += '</td>';
                    html += '<td>' + (opp.agencyName || '—') + '</td>';
                    html += '<td>$' + parseFloat(opp.value || 0).toLocaleString() + '</td>';
                    html += '<td>' + (opp.status || '') + '</td>';
                    html += '<td><div class="archive-info">' + archivedDate + '</div><div class="archive-meta">by ' + (opp.archivedByDisplayName || opp.archivedByUsername || 'Unknown') + '</div></td>';
                    html += '<td><div class="action-group">';
                    html += '<button class="btn btn-restore" onclick="restoreOpportunity(' + opp.id + ')">↩️ Restore</button>';
                    html += '<button class="btn btn-permanent-delete" onclick="permanentlyDeleteOpportunity(' + opp.id + ', \'' + (opp.title || '').replace(/'/g, "\\'") + '\')">🗑️ Delete</button>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                html += '<div class="no-records">No archived opportunities</div>';
            }
            html += '</div>';
            
            // Archived Proposals
            html += '<div class="archive-section">';
            html += '<h3>📄 Archived Proposals <span class="archive-count">(' + (archived.proposals?.length || 0) + ')</span></h3>';
            
            if (archived.proposals && archived.proposals.length > 0) {
                html += '<table class="archive-table">';
                html += '<thead><tr><th>Title</th><th>Agency</th><th>Value</th><th>Status</th><th>Archived</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                archived.proposals.forEach(prop => {
                    const archivedDate = new Date(prop.archived_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    html += '<tr>';
                    html += '<td><strong>' + (prop.title || '') + '</strong>';
                    if (prop.converted_from_opportunity_id) {
                        html += '<div class="archive-meta">🔄 Converted from Opportunity #' + prop.converted_from_opportunity_id + '</div>';
                    }
                    if (prop.archivedTasksCount > 0) {
                        html += '<div class="archive-meta">📋 ' + prop.archivedTasksCount + ' related task(s)</div>';
                    }
                    html += '</td>';
                    html += '<td>' + (prop.agencyName || '—') + '</td>';
                    html += '<td>$' + parseFloat(prop.value || 0).toLocaleString() + '</td>';
                    html += '<td>' + (prop.status || '') + '</td>';
                    html += '<td><div class="archive-info">' + archivedDate + '</div><div class="archive-meta">by ' + (prop.archivedByDisplayName || prop.archivedByUsername || 'Unknown') + '</div></td>';
                    html += '<td><div class="action-group">';
                    html += '<button class="btn btn-restore" onclick="restoreProposal(' + prop.id + ')">↩️ Restore</button>';
                    html += '<button class="btn btn-permanent-delete" onclick="permanentlyDeleteProposal(' + prop.id + ', \'' + (prop.title || '').replace(/'/g, "\\'") + '\')">🗑️ Delete</button>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                html += '<div class="no-records">No archived proposals</div>';
            }
            html += '</div>';
            
            // Archived Tasks (standalone - not archived with opportunity/proposal)
            html += '<div class="archive-section">';
            html += '<h3>✅ Archived Tasks <span class="archive-count">(' + (archived.tasks?.length || 0) + ')</span></h3>';
            
            if (archived.tasks && archived.tasks.length > 0) {
                html += '<table class="archive-table">';
                html += '<thead><tr><th>Title</th><th>Related To</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Archived</th><th>Actions</th></tr></thead>';
                html += '<tbody>';
                
                archived.tasks.forEach(task => {
                    const archivedDate = new Date(task.archived_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    const assignedTo = task.assignedToDisplayName || task.assignedToUsername || task.assignedTo || '—';
                    const relatedDisplay = task.relatedTo ? task.relatedTo + (task.related_item_id ? ' #' + task.related_item_id : '') : '—';
                    html += '<tr>';
                    html += '<td><strong>' + (task.title || '') + '</strong>';
                    html += '<div class="archive-meta">Assigned to: ' + assignedTo + '</div>';
                    html += '</td>';
                    html += '<td>' + relatedDisplay + '</td>';
                    html += '<td>' + (task.dueDate || '—') + '</td>';
                    html += '<td>' + (task.priority || '—') + '</td>';
                    html += '<td>' + (task.status || '—') + '</td>';
                    html += '<td><div class="archive-info">' + archivedDate + '</div><div class="archive-meta">by ' + (task.archivedByDisplayName || task.archivedByUsername || 'Unknown') + '</div></td>';
                    html += '<td><div class="action-group">';
                    html += '<button class="btn btn-restore" onclick="restoreTask(' + task.id + ')">↩️ Restore</button>';
                    html += '<button class="btn btn-permanent-delete" onclick="permanentlyDeleteTask(' + task.id + ', \'' + (task.title || '').replace(/'/g, "\\'") + '\')">🗑️ Delete</button>';
                    html += '</div></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
            } else {
                html += '<div class="no-records">No archived tasks</div>';
            }
            html += '</div>';
            
            container.innerHTML = html;
        }
        
        // Restore Opportunity
        async function restoreOpportunity(id) {
            if (!confirm('Are you sure you want to restore this opportunity? Related tasks will also be restored.')) return;
            
            try {
                const response = await fetch('api.php?action=restoreOpportunity', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Opportunity restored successfully!');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to restore opportunity'));
                }
            } catch (error) {
                console.error('Error restoring opportunity:', error);
                alert('Error restoring opportunity. Please try again.');
            }
        }
        
        // Restore Proposal
        async function restoreProposal(id) {
            if (!confirm('Are you sure you want to restore this proposal? Related tasks will also be restored.')) return;
            
            try {
                const response = await fetch('api.php?action=restoreProposal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Proposal restored successfully!');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to restore proposal'));
                }
            } catch (error) {
                console.error('Error restoring proposal:', error);
                alert('Error restoring proposal. Please try again.');
            }
        }
        
        // Permanently Delete Opportunity
        async function permanentlyDeleteOpportunity(id, title) {
            if (!confirm('⚠️ PERMANENT DELETE ⚠️\n\nAre you sure you want to PERMANENTLY delete "' + title + '"?\n\nThis action CANNOT be undone. All related tasks and notes will also be deleted.')) return;
            if (!confirm('This is your final warning. Click OK to permanently delete this opportunity and all related data.')) return;
            
            try {
                const response = await fetch('api.php?action=permanentlyDeleteOpportunity', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Opportunity permanently deleted.');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete opportunity'));
                }
            } catch (error) {
                console.error('Error deleting opportunity:', error);
                alert('Error deleting opportunity. Please try again.');
            }
        }
        
        // Permanently Delete Proposal
        async function permanentlyDeleteProposal(id, title) {
            if (!confirm('⚠️ PERMANENT DELETE ⚠️\n\nAre you sure you want to PERMANENTLY delete "' + title + '"?\n\nThis action CANNOT be undone. All related tasks and notes will also be deleted.')) return;
            if (!confirm('This is your final warning. Click OK to permanently delete this proposal and all related data.')) return;
            
            try {
                const response = await fetch('api.php?action=permanentlyDeleteProposal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Proposal permanently deleted.');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete proposal'));
                }
            } catch (error) {
                console.error('Error deleting proposal:', error);
                alert('Error deleting proposal. Please try again.');
            }
        }
        
        // Restore Task
        async function restoreTask(id) {
            if (!confirm('Are you sure you want to restore this task?')) return;
            
            try {
                const response = await fetch('api.php?action=restoreTask', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Task restored successfully!');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to restore task'));
                }
            } catch (error) {
                console.error('Error restoring task:', error);
                alert('Error restoring task. Please try again.');
            }
        }
        
        // Permanently Delete Task
        async function permanentlyDeleteTask(id, title) {
            if (!confirm('⚠️ PERMANENT DELETE ⚠️\n\nAre you sure you want to PERMANENTLY delete "' + title + '"?\n\nThis action CANNOT be undone.')) return;
            if (!confirm('This is your final warning. Click OK to permanently delete this task.')) return;
            
            try {
                const response = await fetch('api.php?action=permanentlyDeleteTask', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Task permanently deleted.');
                    loadArchivedRecords();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete task'));
                }
            } catch (error) {
                console.error('Error deleting task:', error);
                alert('Error deleting task. Please try again.');
            }
        }
        
        // Password Reset Modal
        function openResetModal(userId, username) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUsername').textContent = username;
            document.getElementById('newPassword').value = '';
            document.getElementById('resetModal').style.display = 'block';
            document.getElementById('newPassword').focus();
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resetModal');
            if (event.target === modal) {
                closeResetModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeResetModal();
            }
        });
    </script>
</body>
</html>
