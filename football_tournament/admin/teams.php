<?php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all teams
$teams = $pdo->query("SELECT * FROM teams ORDER BY team_name")->fetchAll();

// Handle team deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
    $stmt->execute([$delete_id]);
    
    // Redirect to refresh the page
    redirect('teams.php');
}
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="teams.php" class="active">Manage Teams</a></li>
            <li><a href="players.php">Manage Players</a></li>
            <li><a href="tournaments.php">Manage Tournaments</a></li>
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Manage Teams</h1>
        
        <div style="margin-bottom: 20px;">
            <a href="add_team.php" class="btn">Add New Team</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Stadium</th>
                    <th>Coach</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?php echo $team['team_id']; ?></td>
                        <td>
                            <?php if (!empty($team['team_logo'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 40px; height: 40px; object-fit: contain;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo $team['team_name']; ?></td>
                        <td><?php echo $team['home_stadium'] ?? 'N/A'; ?></td>
                        <td><?php echo $team['coach_name'] ?? 'N/A'; ?></td>
                        <td>
                            <a href="edit_team.php?id=<?php echo $team['team_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">Edit</a>
                            <a href="teams.php?delete=<?php echo $team['team_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this team?')">Delete</a>
                            <a href="team_players.php?id=<?php echo $team['team_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #17a2b8;">Manage Players</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>