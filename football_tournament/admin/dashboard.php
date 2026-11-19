<?php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get counts for dashboard
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$teams_count = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
$players_count = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
$tournaments_count = $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
$matches_count = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="teams.php">Manage Teams</a></li>
            <li><a href="players.php">Manage Players</a></li>
            <li><a href="tournaments.php">Manage Tournaments</a></li>
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
            <div class="stat-card" style="background: #1a3e72; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $users_count; ?></div>
                <div>Users</div>
            </div>
            <div class="stat-card" style="background: #1a3e72; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $teams_count; ?></div>
                <div>Teams</div>
            </div>
            <div class="stat-card" style="background: #1a3e72; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $players_count; ?></div>
                <div>Players</div>
            </div>
            <div class="stat-card" style="background: #1a3e72; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $tournaments_count; ?></div>
                <div>Tournaments</div>
            </div>
            <div class="stat-card" style="background: #1a3e72; color: white; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: bold;"><?php echo $matches_count; ?></div>
                <div>Matches</div>
            </div>
        </div>
        
        <div class="card">
            <h3>Recent Activities</h3>
            <p>Here you would typically display recent system activities or logs.</p>
            <p>This could include:</p>
            <ul>
                <li>Recently added teams or players</li>
                <li>Upcoming matches</li>
                <li>Recent user registrations</li>
                <li>System notifications</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>