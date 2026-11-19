<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get all teams
$stmt = $pdo->query("SELECT * FROM teams ORDER BY team_name");
$teams = $stmt->fetchAll();

// Check if user follows each team
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT team_id FROM user_follows WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $followed_teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $followed_teams = [];
}
?>

<h1>Teams</h1>

<div class="team-list">
    <?php foreach ($teams as $team): ?>
        <div class="team-card">
            <?php if (!empty($team['team_logo'])): ?>
                <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" class="team-logo">
            <?php else: ?>
                <div class="team-logo">No Logo</div>
            <?php endif; ?>
            <div class="team-info">
                <h4><a href="team.php?id=<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></a></h4>
                <p>Stadium: <?php echo $team['home_stadium'] ?? 'Unknown'; ?></p>
                <p>Coach: <?php echo $team['coach_name'] ?? 'Unknown'; ?></p>
            </div>
            <div class="team-actions">
                <?php if (in_array($team['team_id'], $followed_teams)): ?>
                    <form action="unfollow_team.php" method="POST" style="display: inline;">
                        <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                        <button type="submit" class="btn" style="background-color: #dc3545;">Unfollow</button>
                    </form>
                <?php else: ?>
                    <form action="follow_team.php" method="POST" style="display: inline;">
                        <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                        <button type="submit" class="btn">Follow</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>