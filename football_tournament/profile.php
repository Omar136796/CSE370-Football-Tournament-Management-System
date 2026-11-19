<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get followed teams
$stmt = $pdo->prepare("SELECT t.* FROM teams t
                      JOIN user_follows uf ON t.team_id = uf.team_id
                      WHERE uf.user_id = ?");
$stmt->execute([$user_id]);
$followed_teams = $stmt->fetchAll();

// Handle profile update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $current_password = sanitizeInput($_POST['current_password']);
    $new_password = sanitizeInput($_POST['new_password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    
    // Check if username or email already exists (excluding current user)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->execute([$username, $email, $user_id]);
    $exists = $stmt->fetchColumn() > 0;
    
    if ($exists) {
        $error = 'Username or email already exists';
    } else {
        // Verify current password if changing password
        if (!empty($new_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match';
            }
        }
        
        if (!$error) {
            $update_data = [
                'username' => $username,
                'email' => $email,
                'user_id' => $user_id
            ];
            
            $update_sql = "UPDATE users SET username = :username, email = :email";
            
            if (!empty($new_password)) {
                $update_sql .= ", password = :password";
                $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $update_sql .= " WHERE user_id = :user_id";
            
            $stmt = $pdo->prepare($update_sql);
            if ($stmt->execute($update_data)) {
                // Update session data
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                $success = 'Profile updated successfully';
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}
?>

<div class="profile-container">
    <h1>My Profile</h1>
    
    <?php if ($error): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="profile-sections">
        <div class="card">
            <h3>Profile Information</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="current_password">Current Password (leave blank to keep unchanged)</label>
                    <input type="password" id="current_password" name="current_password">
                </div>
                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep unchanged)</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Followed Teams</h3>
            <?php if (!empty($followed_teams)): ?>
                <div class="team-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach ($followed_teams as $team): ?>
                        <div style="text-align: center;">
                            <?php if (!empty($team['team_logo'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 50px; height: 50px; object-fit: contain;">
                            <?php endif; ?>
                            <div><a href="team.php?id=<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></a></div>
                            <form action="unfollow_team.php" method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                <button type="submit" class="btn" style="padding: 5px 10px; font-size: 12px;">Unfollow</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You are not following any teams yet. <a href="teams.php">Browse teams</a> to follow.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>