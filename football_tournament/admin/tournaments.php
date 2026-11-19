<?php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all tournaments
$tournaments = $pdo->query("SELECT * FROM tournaments ORDER BY start_date DESC")->fetchAll();

// Handle tournament deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM tournaments WHERE tournament_id = ?");
    $stmt->execute([$delete_id]);
    
    // Redirect to refresh the page
    redirect('tournaments.php');
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
            <li><a href="tournaments.php" class="active">Manage Tournaments</a></li>
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Manage Tournaments</h1>
        
        <div style="margin-bottom: 20px;">
            <a href="add_tournament.php" class="btn">Add New Tournament</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tournaments as $tournament): ?>
                    <tr>
                        <td><?php echo $tournament['tournament_id']; ?></td>
                        <td><?php echo $tournament['tournament_name']; ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $tournament['tournament_type'])); ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($tournament['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($tournament['end_date'])); ?>
                        </td>
                        <td><?php echo ucfirst($tournament['status']); ?></td>
                        <td>
                            <a href="edit_tournament.php?id=<?php echo $tournament['tournament_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="tournaments.php?delete=<?php echo $tournament['tournament_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this tournament?')">Delete</a>
                            <a href="tournament_teams.php?id=<?php echo $tournament['tournament_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #17a2b8;">Manage Teams</a>
                            <a href="tournament_matches.php?id=<?php echo $tournament['tournament_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #28a745;">Manage Matches</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>