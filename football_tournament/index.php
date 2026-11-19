<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get upcoming matches
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team 
                       FROM matches m
                       JOIN teams t1 ON m.home_team_id = t1.team_id
                       JOIN teams t2 ON m.away_team_id = t2.team_id
                       WHERE m.status = 'scheduled'
                       ORDER BY m.match_date ASC
                       LIMIT 5");
$stmt->execute();
$upcoming_matches = $stmt->fetchAll();

// Get recent matches
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team 
                       FROM matches m
                       JOIN teams t1 ON m.home_team_id = t1.team_id
                       JOIN teams t2 ON m.away_team_id = t2.team_id
                       WHERE m.status = 'completed'
                       ORDER BY m.match_date DESC
                       LIMIT 5");
$stmt->execute();
$recent_matches = $stmt->fetchAll();

// Get followed teams if user is logged in
$followed_teams = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT t.* FROM teams t
                          JOIN user_follows uf ON t.team_id = uf.team_id
                          WHERE uf.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $followed_teams = $stmt->fetchAll();
}
?>

<div class="dashboard">
    <div class="sidebar">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="teams.php">All Teams</a></li>
            <li><a href="tournaments.php">Tournaments</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="stats.php">Statistics</a></li>

            <?php if (isAdmin()): ?>
                <li><a href="admin/dashboard.php">Admin Dashboard</a></li>
            <?php endif; ?>
        </ul>
        
        <?php if (!empty($followed_teams)): ?>
            <h3>Followed Teams</h3>
            <ul>
                <?php foreach ($followed_teams as $team): ?>
                    <li><a href="team.php?id=<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    
    <div class="main-content">
        <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
        
        <div class="card">
            <h3>Upcoming Matches</h3>
            <?php if (!empty($upcoming_matches)): ?>
                <?php foreach ($upcoming_matches as $match): ?>
                    <div class="match-card">
                        <div class="team">
                            <div><?php echo $match['home_team']; ?></div>
                        </div>
                        <div class="score">vs</div>
                        <div class="team">
                            <div><?php echo $match['away_team']; ?></div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <?php echo date('M j, Y H:i', strtotime($match['match_date'])); ?> at <?php echo $match['venue']; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No upcoming matches scheduled.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3>Recent Matches</h3>
            <?php if (!empty($recent_matches)): ?>
                <?php foreach ($recent_matches as $match): ?>
                    <div class="match-card">
                        <div class="team">
                            <div><?php echo $match['home_team']; ?></div>
                        </div>
                        <div class="score">
                            <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
                        </div>
                        <div class="team">
                            <div><?php echo $match['away_team']; ?></div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-bottom: 15px;">
                        <?php echo date('M j, Y', strtotime($match['match_date'])); ?> at <?php echo $match['venue']; ?>
                        <a href="match.php?id=<?php echo $match['match_id']; ?>" style="margin-left: 10px;">View Details</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent matches to display.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>