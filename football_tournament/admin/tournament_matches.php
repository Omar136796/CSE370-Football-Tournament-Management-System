<?php
// admin/tournament_matches.php
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

// Get matches for this tournament
$stmt = $pdo->prepare("SELECT m.*, 
                      t1.team_name as home_team, t2.team_name as away_team
                      FROM matches m
                      JOIN teams t1 ON m.home_team_id = t1.team_id
                      JOIN teams t2 ON m.away_team_id = t2.team_id
                      WHERE m.tournament_id = ?
                      ORDER BY m.match_date ASC");
$stmt->execute([$tournament_id]);
$matches = $stmt->fetchAll();

// Get teams in this tournament
$stmt = $pdo->prepare("SELECT t.* FROM teams t
                      JOIN tournament_teams tt ON t.team_id = tt.team_id
                      WHERE tt.tournament_id = ?
                      ORDER BY t.team_name");
$stmt->execute([$tournament_id]);
$teams = $stmt->fetchAll();

// Handle add match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_match'])) {
    $home_team_id = intval($_POST['home_team_id']);
    $away_team_id = intval($_POST['away_team_id']);
    $match_date = sanitizeInput($_POST['match_date']);
    $venue = sanitizeInput($_POST['venue']);
    $match_round = isset($_POST['match_round']) ? sanitizeInput($_POST['match_round']) : null;
    $group_name = isset($_POST['group_name']) ? sanitizeInput($_POST['group_name']) : null;
    
    // Validate inputs
    if (empty($home_team_id) || empty($away_team_id) || empty($match_date)) {
        $error = 'Required fields are missing';
    } elseif ($home_team_id === $away_team_id) {
        $error = 'Home and away teams cannot be the same';
    } else {
        $stmt = $pdo->prepare("INSERT INTO matches (tournament_id, home_team_id, away_team_id, match_date, venue, match_round, group_name) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$tournament_id, $home_team_id, $away_team_id, $match_date, $venue, $match_round, $group_name])) {
            // Redirect to refresh the page
            redirect("tournament_matches.php?id=$tournament_id");
        } else {
            $error = 'Failed to add match';
        }
    }
}

// Handle delete match
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // First delete match events
    $stmt = $pdo->prepare("DELETE FROM match_events WHERE match_id = ?");
    $stmt->execute([$delete_id]);
    
    // Then delete the match
    $stmt = $pdo->prepare("DELETE FROM matches WHERE match_id = ?");
    $stmt->execute([$delete_id]);
    
    // Redirect to refresh the page
    redirect("tournament_matches.php?id=$tournament_id");
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
        <h1>Manage Matches for <?php echo $tournament['tournament_name']; ?></h1>
        
        <div style="margin-bottom: 20px;">
            <a href="tournaments.php" class="btn" style="background-color: #6c757d;">Back to Tournaments</a>
        </div>
        
        <div class="card" style="margin-bottom: 30px;">
            <h3>Add New Match</h3>
            <form method="POST">
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
                    <input type="datetime-local" id="match_date" name="match_date" required>
                </div>
                <div class="form-group">
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue">
                </div>
                <?php if ($tournament['tournament_type'] === 'knockout' || $tournament['tournament_type'] === 'group_stage'): ?>
                    <div class="form-group">
                        <label for="match_round">Round (e.g., Quarterfinal, Semifinal, Final)</label>
                        <input type="text" id="match_round" name="match_round">
                    </div>
                <?php endif; ?>
                <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                    <div class="form-group">
                        <label for="group_name">Group (A, B, C, etc.)</label>
                        <input type="text" id="group_name" name="group_name" maxlength="1" style="text-transform: uppercase;">
                    </div>
                <?php endif; ?>
                <button type="submit" name="add_match" class="btn">Add Match</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Matches in Tournament</h3>
            <?php if (!empty($matches)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Home Team</th>
                            <th>Score</th>
                            <th>Away Team</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <?php if ($tournament['tournament_type'] === 'knockout' || $tournament['tournament_type'] === 'group_stage'): ?>
                                <th>Round</th>
                            <?php endif; ?>
                            <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                                <th>Group</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td><?php echo $match['home_team']; ?></td>
                                <td>
                                    <?php if ($match['status'] === 'completed'): ?>
                                        <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
                                    <?php else: ?>
                                        vs
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $match['away_team']; ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($match['match_date'])); ?></td>
                                <td><?php echo $match['venue'] ?? 'N/A'; ?></td>
                                <?php if ($tournament['tournament_type'] === 'knockout' || $tournament['tournament_type'] === 'group_stage'): ?>
                                    <td><?php echo $match['match_round'] ?? 'N/A'; ?></td>
                                <?php endif; ?>
                                <?php if ($tournament['tournament_type'] === 'group_stage'): ?>
                                    <td><?php echo $match['group_name'] ?? 'N/A'; ?></td>
                                <?php endif; ?>
                                <td><?php echo ucfirst($match['status']); ?></td>
                                <td>
                                    <a href="edit_match.php?id=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                                    <a href="tournament_matches.php?id=<?php echo $tournament_id; ?>&delete=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this match?')">Delete</a>
                                    <a href="match_events.php?id=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #17a2b8;">Manage Events</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No matches scheduled for this tournament yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>