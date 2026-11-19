<?php
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

// Get teams in this tournament
$stmt = $pdo->prepare("SELECT t.*, tt.group_name 
                      FROM teams t
                      JOIN tournament_teams tt ON t.team_id = tt.team_id
                      WHERE tt.tournament_id = ?
                      ORDER BY tt.group_name, t.team_name");
$stmt->execute([$tournament_id]);
$tournament_teams = $stmt->fetchAll();

// Get all teams not in this tournament
$stmt = $pdo->prepare("SELECT * FROM teams 
                      WHERE team_id NOT IN (
                          SELECT team_id FROM tournament_teams WHERE tournament_id = ?
                      )
                      ORDER BY team_name");
$stmt->execute([$tournament_id]);
$available_teams = $stmt->fetchAll();

// Handle add team to tournament
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $team_id = intval($_POST['team_id']);
    $group_name = isset($_POST['group_name']) ? sanitizeInput($_POST['group_name']) : null;
    
    // Check if team is already in tournament
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = ? AND team_id = ?");
    $stmt->execute([$tournament_id, $team_id]);
    $exists = $stmt->fetchColumn() > 0;
    
    if (!$exists) {
        $stmt = $pdo->prepare("INSERT INTO tournament_teams (tournament_id, team_id, group_name) VALUES (?, ?, ?)");
        $stmt->execute([$tournament_id, $team_id, $group_name]);
        
        // If it's a league or group stage tournament, add to standings
        if ($tournament['tournament_type'] === 'league' || $tournament['tournament_type'] === 'group_stage') {
            $stmt = $pdo->prepare("INSERT INTO standings (tournament_id, team_id, group_name) VALUES (?, ?, ?)");
            $stmt->execute([$tournament_id, $team_id, $group_name]);
        }
        
        // Redirect to refresh the page
        redirect("tournament_teams.php?id=$tournament_id");
    }
}

// Handle remove team from tournament
if (isset($_GET['remove_team']) && is_numeric($_GET['remove_team'])) {
    $team_id = $_GET['remove_team'];
    
    $stmt = $pdo->prepare("DELETE FROM tournament_teams WHERE tournament_id = ? AND team_id = ?");
    $stmt->execute([$tournament_id, $team_id]);
    
    // Also remove from standings if exists
    $stmt = $pdo->prepare("DELETE FROM standings WHERE tournament_id = ? AND team_id = ?");
    $stmt->execute([$tournament_id, $team_id]);
    
    // Redirect to refresh the page
    redirect("tournament_teams.php?id=$tournament_id");
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
        <h1>Manage Teams for <?php echo $tournament['tournament_name']; ?></h1>
        
        <div style="margin-bottom: 20px;">
            <a href="tournaments.php" class="btn" style="background-color: #6c757d;">Back to Tournaments</a>
        </div>
        
        <div class="card" style="margin-bottom: 30px;">
            <h3>Add Team to Tournament</h3>
            <?php if (!empty($available_teams)): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="team_id">Select Team</label>
                        <select id="team_id" name="team_id" required>
                            <?php foreach ($available_teams as $team): ?>
                                <option value="<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                        <div class="form-group">
                            <label for="group_name">Group (A, B, C, etc.)</label>
                            <input type="text" id="group_name" name="group_name" maxlength="1" style="text-transform: uppercase;" required>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="add_team" class="btn">Add Team</button>
                </form>
            <?php else: ?>
                <p>All teams are already in this tournament.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Teams in Tournament</h3>
            <?php if (!empty($tournament_teams)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Team</th>
                            <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                                <th>Group</th>
                            <?php endif; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tournament_teams as $team): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($team['team_logo'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 30px; height: 30px; margin-right: 10px; vertical-align: middle;">
                                    <?php endif; ?>
                                    <?php echo $team['team_name']; ?>
                                </td>
                                <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                                    <td><?php echo $team['group_name']; ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="tournament_teams.php?id=<?php echo $tournament_id; ?>&remove_team=<?php echo $team['team_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to remove this team from the tournament?')">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No teams added to this tournament yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>