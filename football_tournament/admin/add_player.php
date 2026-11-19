<?php
// admin/add_player.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get teams for dropdown
$teams = $pdo->query("SELECT * FROM teams ORDER BY team_name")->fetchAll();

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : null;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $player_name = sanitizeInput($_POST['player_name']);
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : null;
    $position = sanitizeInput($_POST['position']);
    $jersey_number = sanitizeInput($_POST['jersey_number']);
    $nationality = sanitizeInput($_POST['nationality']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $height = sanitizeInput($_POST['height']);
    $weight = sanitizeInput($_POST['weight']);
    
    // Validate inputs
    if (empty($player_name)) {
        $error = 'Player name is required';
    } else {
        $stmt = $pdo->prepare("INSERT INTO players (player_name, team_id, position, jersey_number, nationality, date_of_birth, height, weight) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$player_name, $team_id, $position, $jersey_number, $nationality, $date_of_birth, $height, $weight])) {
            $success = 'Player added successfully';
            
            // If added from team_players.php, redirect back
            if (isset($_POST['from_team']) && $_POST['from_team']) {
                redirect("team_players.php?id=$team_id");
            }
            
            // Clear form
            $player_name = $position = $jersey_number = $nationality = $date_of_birth = $height = $weight = '';
            $team_id = null;
        } else {
            $error = 'Failed to add player';
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
        <h1>Add New Player</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="from_team" value="<?php echo isset($_GET['team_id']) ? '1' : '0'; ?>">
            
            <div class="form-group">
                <label for="player_name">Player Name</label>
                <input type="text" id="player_name" name="player_name" value="<?php echo isset($player_name) ? $player_name : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="team_id">Team</label>
                <select id="team_id" name="team_id">
                    <option value="">Free Agent</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>" <?php if ($team_id == $team['team_id']) echo 'selected'; ?>>
                            <?php echo $team['team_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" id="position" name="position" value="<?php echo isset($position) ? $position : ''; ?>">
            </div>
            <div class="form-group">
                <label for="jersey_number">Jersey Number</label>
                <input type="number" id="jersey_number" name="jersey_number" value="<?php echo isset($jersey_number) ? $jersey_number : ''; ?>">
            </div>
            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" value="<?php echo isset($nationality) ? $nationality : ''; ?>">
            </div>
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo isset($date_of_birth) ? $date_of_birth : ''; ?>">
            </div>
            <div class="form-group">
                <label for="height">Height (cm)</label>
                <input type="number" id="height" name="height" step="0.01" value="<?php echo isset($height) ? $height : ''; ?>">
            </div>
            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input type="number" id="weight" name="weight" step="0.01" value="<?php echo isset($weight) ? $weight : ''; ?>">
            </div>
            <button type="submit" class="btn">Add Player</button>
            <?php if (isset($_GET['team_id'])): ?>
                <a href="team_players.php?id=<?php echo $_GET['team_id']; ?>" class="btn" style="background-color: #6c757d;">Cancel</a>
            <?php else: ?>
                <a href="players.php" class="btn" style="background-color: #6c757d;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>