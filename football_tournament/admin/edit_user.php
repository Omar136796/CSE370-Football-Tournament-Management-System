<?php
// admin/edit_user.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('users.php');
}

$user_id = $_GET['id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('users.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $user_type = sanitizeInput($_POST['user_type']);
    $password = sanitizeInput($_POST['password']);
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->execute([$username, $email, $user_id]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $error = 'Username or email already exists';
        } else {
            $update_data = [
                'username' => $username,
                'email' => $email,
                'user_type' => $user_type,
                'user_id' => $user_id
            ];
            
            $update_sql = "UPDATE users SET username = :username, email = :email, user_type = :user_type";
            
            if (!empty($password)) {
                $update_sql .= ", password = :password";
                $update_data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $update_sql .= " WHERE user_id = :user_id";
            
            $stmt = $pdo->prepare($update_sql);
            if ($stmt->execute($update_data)) {
                $success = 'User updated successfully';
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="teams.php">Manage Teams</a></li>
            <li><a href="players.php">Manage Players</a></li>
            <li><a href="tournaments.php">Manage Tournaments</a></li>
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Edit User</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
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
                <label for="user_type">User Type</label>
                <select id="user_type" name="user_type" required>
                    <option value="user" <?php if ($user['user_type'] === 'user') echo 'selected'; ?>>User</option>
                    <option value="admin" <?php if ($user['user_type'] === 'admin') echo 'selected'; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="password">New Password (leave blank to keep current password)</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" class="btn">Update User</button>
            <a href="users.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>