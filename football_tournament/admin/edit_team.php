<?php
// admin/edit_team.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('teams.php');
}

$team_id = $_GET['id'];

// Get team details
$stmt = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if (!$team) {
    redirect('teams.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = sanitizeInput($_POST['team_name']);
    $founded_year = sanitizeInput($_POST['founded_year']);
    $home_stadium = sanitizeInput($_POST['home_stadium']);
    $coach_name = sanitizeInput($_POST['coach_name']);
    
// Handle file upload
    $team_logo = $team['team_logo'];
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {

        $upload_dir = '../uploads/';
        // Verify directory exists and is writable
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        if (!is_writable($upload_dir)) {
            die("Error: Upload directory is not writable");
        }
        
        $file_name = basename($_FILES['team_logo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Verify file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['team_logo']['tmp_name']);
        finfo_close($finfo);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_ext, $allowed_ext) ){
            $error = 'Invalid file extension. Only JPG, JPEG, PNG, GIF are allowed.';
        } elseif (!in_array($mime, $allowed_mime)) {
            $error = 'Invalid file type detected.';
        } else {
            $unique_name = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['team_logo']['tmp_name'], $target_path)) {
                // Delete old logo if exists
                if (!empty($team_logo) && file_exists($upload_dir . $team_logo)) {
                    unlink($upload_dir . $team_logo);
                }
                $team_logo = $unique_name;
                $success = 'Logo uploaded successfully!';
            } else {
                $error = 'Failed to move uploaded file. Check permissions.';
                // Get detailed error
                $error .= "<br>Error: " . $_FILES['team_logo']['error'];
                $error .= "<br>Temp file: " . $_FILES['team_logo']['tmp_name'];
                $error .= "<br>Target: " . $target_path;
            }
        }
    }
    
    // Validate inputs
    if (empty($team_name)) {
        $error = 'Team name is required';
    } else {
        // Check if team already exists (excluding current team)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE team_name = ? AND team_id != ?");
        $stmt->execute([$team_name, $team_id]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $error = 'Team with this name already exists';
        } else {
            $stmt = $pdo->prepare("UPDATE teams SET team_name = ?, team_logo = ?, founded_year = ?, home_stadium = ?, coach_name = ? WHERE team_id = ?");
            
            if ($stmt->execute([$team_name, $team_logo, $founded_year, $home_stadium, $coach_name, $team_id])) {
                $success = 'Team updated successfully';
                
                // Refresh team data
                $stmt = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
                $stmt->execute([$team_id]);
                $team = $stmt->fetch();
            } else {
                $error = 'Failed to update team';
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
        <h1>Edit Team</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="team_name">Team Name</label>
                <input type="text" id="team_name" name="team_name" value="<?php echo $team['team_name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="team_logo">Team Logo</label>
                <input type="file" id="team_logo" name="team_logo">
                <?php if (!empty($team['team_logo'])): ?>
                    <div style="margin-top: 10px;">
                        <p>Current Logo:</p>
                        <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 100px; height: 100px; object-fit: contain;">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="founded_year">Founded Year</label>
                <input type="number" id="founded_year" name="founded_year" value="<?php echo $team['founded_year']; ?>">
            </div>
            <div class="form-group">
                <label for="home_stadium">Home Stadium</label>
                <input type="text" id="home_stadium" name="home_stadium" value="<?php echo $team['home_stadium']; ?>">
            </div>
            <div class="form-group">
                <label for="coach_name">Coach Name</label>
                <input type="text" id="coach_name" name="coach_name" value="<?php echo $team['coach_name']; ?>">
            </div>
            <button type="submit" class="btn">Update Team</button>
            <a href="teams.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>