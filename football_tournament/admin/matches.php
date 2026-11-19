<?php
// admin/matches.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all matches with team and tournament names
$matches = $pdo->query("SELECT m.*, 
                       t1.team_name as home_team, t2.team_name as away_team,
                       tr.tournament_name
                       FROM matches m
                       JOIN teams t1 ON m.home_team_id = t1.team_id
                       JOIN teams t2 ON m.away_team_id = t2.team_id
                       JOIN tournaments tr ON m.tournament_id = tr.tournament_id
                       ORDER BY m.match_date DESC")->fetchAll();

// Handle match deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // First delete match events
    $stmt = $pdo->prepare("DELETE FROM match_events WHERE match_id = ?");
    $stmt->execute([$delete_id]);
    
    // Then delete the match
    $stmt = $pdo->prepare("DELETE FROM matches WHERE match_id = ?");
    $stmt->execute([$delete_id]);
    
    // Redirect to refresh the page
    redirect('matches.php');
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
            <li><a href="matches.php" class="active">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Manage Matches</h1>
        
        <div style="margin-bottom: 20px;">
            <a href="add_match.php" class="btn">Add New Match</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tournament</th>
                    <th>Home Team</th>
                    <th>Score</th>
                    <th>Away Team</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><?php echo $match['match_id']; ?></td>
                        <td><?php echo $match['tournament_name']; ?></td>
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
                        <td><?php echo ucfirst($match['status']); ?></td>
                        <td>
                            <a href="edit_match.php?id=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="matches.php?delete=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this match?')">Delete</a>
                            <a href="match_events.php?id=<?php echo $match['match_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #17a2b8;">Manage Events</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>