<?php
// admin/edit_tournament.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('tournaments.php');
}

$tournament_id = $_GET['id'];

// Get tournament details
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    redirect('tournaments.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_name = sanitizeInput($_POST['tournament_name']);
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    $tournament_type = sanitizeInput($_POST['tournament_type']);
    $status = sanitizeInput($_POST['status']);
    $description = sanitizeInput($_POST['description']);
    
    // Validate inputs
    if (empty($tournament_name) || empty($start_date) || empty($end_date) || empty($tournament_type)) {
        $error = 'Required fields are missing';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error = 'End date must be after start date';
    } else {
        // Check if tournament already exists (excluding current tournament)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE tournament_name = ? AND tournament_id != ?");
        $stmt->execute([$tournament_name, $tournament_id]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            $error = 'Tournament with this name already exists';
        } else {
            $stmt = $pdo->prepare("UPDATE tournaments SET tournament_name = ?, start_date = ?, end_date = ?, tournament_type = ?, status = ?, description = ? 
                                  WHERE tournament_id = ?");
            
            if ($stmt->execute([$tournament_name, $start_date, $end_date, $tournament_type, $status, $description, $tournament_id])) {
                $success = 'Tournament updated successfully';
                
                // Refresh tournament data
                $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
                $stmt->execute([$tournament_id]);
                $tournament = $stmt->fetch();
            } else {
                $error = 'Failed to update tournament';
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
        <h1>Edit Tournament</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="tournament_name">Tournament Name</label>
                <input type="text" id="tournament_name" name="tournament_name" value="<?php echo $tournament['tournament_name']; ?>" required>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $tournament['start_date']; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $tournament['end_date']; ?>" required>
            </div>
            <div class="form-group">
                <label for="tournament_type">Tournament Type</label>
                <select id="tournament_type" name="tournament_type" required>
                    <option value="league" <?php if ($tournament['tournament_type'] === 'league') echo 'selected'; ?>>League</option>
                    <option value="knockout" <?php if ($tournament['tournament_type'] === 'knockout') echo 'selected'; ?>>Knockout</option>
                    <option value="group_stage" <?php if ($tournament['tournament_type'] === 'group_stage') echo 'selected'; ?>>Group Stage + Knockout</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="upcoming" <?php if ($tournament['status'] === 'upcoming') echo 'selected'; ?>>Upcoming</option>
                    <option value="ongoing" <?php if ($tournament['status'] === 'ongoing') echo 'selected'; ?>>Ongoing</option>
                    <option value="completed" <?php if ($tournament['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo $tournament['description']; ?></textarea>
            </div>
            <button type="submit" class="btn">Update Tournament</button>
            <a href="tournaments.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>