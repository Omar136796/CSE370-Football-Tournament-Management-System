<?php
// admin/add_match.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get tournaments for dropdown
$tournaments = $pdo->query("SELECT * FROM tournaments ORDER BY start_date DESC")->fetchAll();

// Get teams for dropdown
$teams = $pdo->query("SELECT * FROM teams ORDER BY team_name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournament_id = intval($_POST['tournament_id']);
    $home_team_id = intval($_POST['home_team_id']);
    $away_team_id = intval($_POST['away_team_id']);
    $match_date = sanitizeInput($_POST['match_date']);
    $venue = sanitizeInput($_POST['venue']);
    $match_round = isset($_POST['match_round']) ? sanitizeInput($_POST['match_round']) : null;
    $group_name = isset($_POST['group_name']) ? sanitizeInput($_POST['group_name']) : null;
    
    // Validate inputs
    if (empty($tournament_id) || empty($home_team_id) || empty($away_team_id) || empty($match_date)) {
        $error = 'Required fields are missing';
    } elseif ($home_team_id === $away_team_id) {
        $error = 'Home and away teams cannot be the same';
    } else {
        $stmt = $pdo->prepare("INSERT INTO matches (tournament_id, home_team_id, away_team_id, match_date, venue, match_round, group_name) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$tournament_id, $home_team_id, $away_team_id, $match_date, $venue, $match_round, $group_name])) {
            $success = 'Match added successfully';
            // Clear form
            $match_date = $venue = $match_round = $group_name = '';
        } else {
            $error = 'Failed to add match';
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
        <h1>Add New Match</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="tournament_id">Tournament</label>
                <select id="tournament_id" name="tournament_id" required>
                    <?php foreach ($tournaments as $tournament): ?>
                        <option value="<?php echo $tournament['tournament_id']; ?>"><?php echo $tournament['tournament_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="home_team_id">Home Team</label>
                <select id="home_team_id" name="home_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="away_team_id">Away Team</label>
                <select id="away_team_id" name="away_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="match_date">Match Date & Time</label>
                <input type="datetime-local" id="match_date" name="match_date" value="<?php echo isset($match_date) ? $match_date : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" value="<?php echo isset($venue) ? $venue : ''; ?>">
            </div>
            <div class="form-group">
                <label for="match_round">Round (e.g., Quarterfinal, Semifinal, Final - leave blank for league matches)</label>
                <input type="text" id="match_round" name="match_round" value="<?php echo isset($match_round) ? $match_round : ''; ?>">
            </div>
            <div class="form-group">
                <label for="group_name">Group (A, B, C, etc. - for group stage tournaments)</label>
                <input type="text" id="group_name" name="group_name" maxlength="1" style="text-transform: uppercase;" value="<?php echo isset($group_name) ? $group_name : ''; ?>">
            </div>
            <button type="submit" class="btn">Add Match</button>
            <a href="matches.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>