<?php
// admin/add_team.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = sanitizeInput($_POST['team_name']);
    $founded_year = sanitizeInput($_POST['founded_year']);
    $home_stadium = sanitizeInput($_POST['home_stadium']);
    $coach_name = sanitizeInput($_POST['coach_name']);
    
    // Handle file upload
    $team_logo = '';
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = basename($_FILES['team_logo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $unique_name = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['team_logo']['tmp_name'], $target_path)) {
                $team_logo = $unique_name;
            }
        }
    }
    
    // Validate inputs
    if (empty($team_name)) {
        $error = 'Team name is required';
    } else {
        // Check if team already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ?");
        $stmt->execute([$team_name]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $error = 'Team with this name already exists';
        } else {
            $stmt = $pdo->prepare("INSERT INTO teams (team_name, team_logo, founded_year, home_stadium, coach_name) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$team_name, $team_logo, $founded_year, $home_stadium, $coach_name])) {
                $success = 'Team added successfully';
                // Clear form
                $team_name = $founded_year = $home_stadium = $coach_name = '';
            } else {
                $error = 'Failed to add team';
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
        <h1>Add New Team</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="team_name">Team Name</label>
                <input type="text" id="team_name" name="team_name" value="<?php echo isset($team_name) ? $team_name : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="team_logo">Team Logo</label>
                <input type="file" id="team_logo" name="team_logo">
            </div>
            <div class="form-group">
                <label for="founded_year">Founded Year</label>
                <input type="number" id="founded_year" name="founded_year" value="<?php echo isset($founded_year) ? $founded_year : ''; ?>">
            </div>
            <div class="form-group">
                <label for="home_stadium">Home Stadium</label>
                <input type="text" id="home_stadium" name="home_stadium" value="<?php echo isset($home_stadium) ? $home_stadium : ''; ?>">
            </div>
            <div class="form-group">
                <label for="coach_name">Coach Name</label>
                <input type="text" id="coach_name" name="coach_name" value="<?php echo isset($coach_name) ? $coach_name : ''; ?>">
            </div>
            <button type="submit" class="btn">Add Team</button>
            <a href="teams.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>