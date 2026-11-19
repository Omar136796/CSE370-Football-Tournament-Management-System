<?php
// admin/players.php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all players with team names
$players = $pdo->query("SELECT p.*, t.team_name 
                       FROM players p
                       LEFT JOIN teams t ON p.team_id = t.team_id
                       ORDER BY p.player_name")->fetchAll();

// Handle player deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM players WHERE player_id = ?");
    $stmt->execute([$delete_id]);
    
    // Redirect to refresh the page
    redirect('players.php');
}
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="teams.php">Manage Teams</a></li>
            <li><a href="players.php" class="active">Manage Players</a></li>
            <li><a href="tournaments.php">Manage Tournaments</a></li>
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Manage Players</h1>
        
        <div style="margin-bottom: 20px;">
            <a href="add_player.php" class="btn">Add New Player</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Team</th>
                    <th>Position</th>
                    <th>Jersey #</th>
                    <th>Nationality</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td><?php echo $player['player_id']; ?></td>
                        <td><?php echo $player['player_name']; ?></td>
                        <td><?php echo $player['team_name'] ?? 'Free Agent'; ?></td>
                        <td><?php echo $player['position'] ?? 'N/A'; ?></td>
                        <td><?php echo $player['jersey_number'] ?? 'N/A'; ?></td>
                        <td><?php echo $player['nationality'] ?? 'N/A'; ?></td>
                        <td>
                            <a href="edit_player.php?id=<?php echo $player['player_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="players.php?delete=<?php echo $player['player_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this player?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>