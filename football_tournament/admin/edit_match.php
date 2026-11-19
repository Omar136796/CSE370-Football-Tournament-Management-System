<?php
// admin/edit_match.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('matches.php');
}

$match_id = $_GET['id'];

// Get match details
$stmt = $pdo->prepare("SELECT * FROM matches WHERE match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    redirect('matches.php');
}

// Get tournament details
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
$stmt->execute([$match['tournament_id']]);
$tournament = $stmt->fetch();

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
    $status = sanitizeInput($_POST['status']);
    $home_score = isset($_POST['home_score']) ? intval($_POST['home_score']) : null;
    $away_score = isset($_POST['away_score']) ? intval($_POST['away_score']) : null;
    $match_round = isset($_POST['match_round']) ? sanitizeInput($_POST['match_round']) : null;
    $group_name = isset($_POST['group_name']) ? sanitizeInput($_POST['group_name']) : null;
    
    // Validate inputs
    if (empty($tournament_id) || empty($home_team_id) || empty($away_team_id) || empty($match_date)) {
        $error = 'Required fields are missing';
    } elseif ($home_team_id === $away_team_id) {
        $error = 'Home and away teams cannot be the same';
    } else {
        $stmt = $pdo->prepare("UPDATE matches SET 
                              tournament_id = ?, 
                              home_team_id = ?, 
                              away_team_id = ?, 
                              match_date = ?, 
                              venue = ?, 
                              status = ?, 
                              home_score = ?, 
                              away_score = ?, 
                              match_round = ?, 
                              group_name = ?
                              WHERE match_id = ?");
        
        if ($stmt->execute([$tournament_id, $home_team_id, $away_team_id, $match_date, $venue, $status, $home_score, $away_score, $match_round, $group_name, $match_id])) {
            $success = 'Match updated successfully';
            
            // Refresh match data
            $stmt = $pdo->prepare("SELECT * FROM matches WHERE match_id = ?");
            $stmt->execute([$match_id]);
            $match = $stmt->fetch();
            
            // Refresh tournament data
            $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
            $stmt->execute([$match['tournament_id']]);
            $tournament = $stmt->fetch();
        } else {
            $error = 'Failed to update match';
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
        <h1>Edit Match</h1>
        
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
                    <?php foreach ($tournaments as $t): ?>
                        <option value="<?php echo $t['tournament_id']; ?>" <?php if ($match['tournament_id'] == $t['tournament_id']) echo 'selected'; ?>>
                            <?php echo $t['tournament_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="home_team_id">Home Team</label>
                <select id="home_team_id" name="home_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>" <?php if ($match['home_team_id'] == $team['team_id']) echo 'selected'; ?>>
                            <?php echo $team['team_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="away_team_id">Away Team</label>
                <select id="away_team_id" name="away_team_id" required>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team['team_id']; ?>" <?php if ($match['away_team_id'] == $team['team_id']) echo 'selected'; ?>>
                            <?php echo $team['team_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="match_date">Match Date & Time</label>
                <input type="datetime-local" id="match_date" name="match_date" value="<?php echo date('Y-m-d\TH:i', strtotime($match['match_date'])); ?>" required>
            </div>
            <div class="form-group">
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" value="<?php echo $match['venue']; ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="scheduled" <?php if ($match['status'] === 'scheduled') echo 'selected'; ?>>Scheduled</option>
                    <option value="ongoing" <?php if ($match['status'] === 'ongoing') echo 'selected'; ?>>Ongoing</option>
                    <option value="completed" <?php if ($match['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                    <option value="postponed" <?php if ($match['status'] === 'postponed') echo 'selected'; ?>>Postponed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="home_score">Home Score (if completed)</label>
                <input type="number" id="home_score" name="home_score" min="0" value="<?php echo $match['home_score'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="away_score">Away Score (if completed)</label>
                <input type="number" id="away_score" name="away_score" min="0" value="<?php echo $match['away_score'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="match_round">Round (e.g., Quarterfinal, Semifinal, Final - leave blank for league matches)</label>
                <input type="text" id="match_round" name="match_round" value="<?php echo $match['match_round'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label for="group_name">Group (A, B, C, etc. - for group stage tournaments)</label>
                <input type="text" id="group_name" name="group_name" maxlength="1" style="text-transform: uppercase;" value="<?php echo $match['group_name'] ?? ''; ?>">
            </div>
            <button type="submit" class="btn">Update Match</button>
            <a href="matches.php" class="btn" style="background-color: #6c757d;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>