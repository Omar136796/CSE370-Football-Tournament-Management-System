<?php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all league and group stage tournaments
$tournaments = $pdo->query("SELECT * FROM tournaments 
                           WHERE tournament_type IN ('league', 'group_stage')
                           ORDER BY start_date DESC")->fetchAll();

$selected_tournament = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : null;

// Get standings for selected tournament
$standings = [];
$groups = [];
if ($selected_tournament) {
    // Get tournament details
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
    $stmt->execute([$selected_tournament]);
    $tournament = $stmt->fetch();
    
    if ($tournament) {
        // Get standings
        $stmt = $pdo->prepare("SELECT s.*, t.team_name, t.team_logo 
                              FROM standings s
                              JOIN teams t ON s.team_id = t.team_id
                              WHERE s.tournament_id = ?
                              ORDER BY s.group_name, s.points DESC, s.goal_difference DESC, s.goals_for DESC");
        $stmt->execute([$selected_tournament]);
        $standings = $stmt->fetchAll();
        
        // Get unique groups
        $groups = array_unique(array_column($standings, 'group_name'));
        $groups = array_filter($groups); // Remove empty/null values
        sort($groups);
    }
}

// Handle update standings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_standings'])) {
    foreach ($_POST['standings'] as $standing_id => $data) {
        $matches_played = intval($data['matches_played']);
        $wins = intval($data['wins']);
        $draws = intval($data['draws']);
        $losses = intval($data['losses']);
        $goals_for = intval($data['goals_for']);
        $goals_against = intval($data['goals_against']);
        
        $goal_difference = $goals_for - $goals_against;
        $points = ($wins * 3) + ($draws * 1);
        
        $stmt = $pdo->prepare("UPDATE standings SET 
                              matches_played = ?, 
                              wins = ?, 
                              draws = ?, 
                              losses = ?, 
                              goals_for = ?, 
                              goals_against = ?, 
                              goal_difference = ?, 
                              points = ?
                              WHERE standing_id = ?");
        $stmt->execute([$matches_played, $wins, $draws, $losses, $goals_for, $goals_against, $goal_difference, $points, $standing_id]);
    }
    
    // Redirect to refresh the page
    redirect("standings.php?tournament_id=$selected_tournament");
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
            <li><a href="standings.php" class="active">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Update Standings</h1>
        
        <div class="card" style="margin-bottom: 20px;">
            <h3>Select Tournament</h3>
            <form method="GET">
                <div class="form-group">
                    <label for="tournament_id">Tournament</label>
                    <select id="tournament_id" name="tournament_id" onchange="this.form.submit()">
                        <option value="">-- Select Tournament --</option>
                        <?php foreach ($tournaments as $t): ?>
                            <option value="<?php echo $t['tournament_id']; ?>" <?php if ($selected_tournament == $t['tournament_id']) echo 'selected'; ?>>
                                <?php echo $t['tournament_name']; ?> (<?php echo ucfirst($t['tournament_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if ($selected_tournament && !empty($standings)): ?>
            <form method="POST">
                <?php if (!empty($groups)): ?>
                    <?php foreach ($groups as $group): ?>
                        <div class="card" style="margin-bottom: 30px;">
                            <h3>Group <?php echo $group; ?></h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Team</th>
                                        <th>MP</th>
                                        <th>W</th>
                                        <th>D</th>
                                        <th>L</th>
                                        <th>GF</th>
                                        <th>GA</th>
                                        <th>GD</th>
                                        <th>Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($standings as $standing): ?>
                                        <?php if ($standing['group_name'] === $group): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($standing['team_logo'])): ?>
                                                        <img src="<?php echo BASE_URL . 'uploads/' . $standing['team_logo']; ?>" alt="<?php echo $standing['team_name']; ?>" style="width: 20px; height: 20px; margin-right: 5px; vertical-align: middle;">
                                                    <?php endif; ?>
                                                    <?php echo $standing['team_name']; ?>
                                                    <input type="hidden" name="standings[<?php echo $standing['standing_id']; ?>][team_id]" value="<?php echo $standing['team_id']; ?>">
                                                </td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][matches_played]" value="<?php echo $standing['matches_played']; ?>" min="0" style="width: 40px;"></td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][wins]" value="<?php echo $standing['wins']; ?>" min="0" style="width: 40px;"></td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][draws]" value="<?php echo $standing['draws']; ?>" min="0" style="width: 40px;"></td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][losses]" value="<?php echo $standing['losses']; ?>" min="0" style="width: 40px;"></td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][goals_for]" value="<?php echo $standing['goals_for']; ?>" min="0" style="width: 40px;"></td>
                                                <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][goals_against]" value="<?php echo $standing['goals_against']; ?>" min="0" style="width: 40px;"></td>
                                                <td><?php echo $standing['goal_difference']; ?></td>
                                                <td><?php echo $standing['points']; ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Team</th>
                                    <th>MP</th>
                                    <th>W</th>
                                    <th>D</th>
                                    <th>L</th>
                                    <th>GF</th>
                                    <th>GA</th>
                                    <th>GD</th>
                                    <th>Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standings as $standing): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($standing['team_logo'])): ?>
                                                <img src="<?php echo BASE_URL . 'uploads/' . $standing['team_logo']; ?>" alt="<?php echo $standing['team_name']; ?>" style="width: 20px; height: 20px; margin-right: 5px; vertical-align: middle;">
                                            <?php endif; ?>
                                            <?php echo $standing['team_name']; ?>
                                            <input type="hidden" name="standings[<?php echo $standing['standing_id']; ?>][team_id]" value="<?php echo $standing['team_id']; ?>">
                                        </td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][matches_played]" value="<?php echo $standing['matches_played']; ?>" min="0" style="width: 40px;"></td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][wins]" value="<?php echo $standing['wins']; ?>" min="0" style="width: 40px;"></td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][draws]" value="<?php echo $standing['draws']; ?>" min="0" style="width: 40px;"></td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][losses]" value="<?php echo $standing['losses']; ?>" min="0" style="width: 40px;"></td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][goals_for]" value="<?php echo $standing['goals_for']; ?>" min="0" style="width: 40px;"></td>
                                        <td><input type="number" name="standings[<?php echo $standing['standing_id']; ?>][goals_against]" value="<?php echo $standing['goals_against']; ?>" min="0" style="width: 40px;"></td>
                                        <td><?php echo $standing['goal_difference']; ?></td>
                                        <td><?php echo $standing['points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="update_standings" class="btn">Update Standings</button>
                </div>
            </form>
        <?php elseif ($selected_tournament): ?>
            <div class="card">
                <p>No standings data available for this tournament.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>