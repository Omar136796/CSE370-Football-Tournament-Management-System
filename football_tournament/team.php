<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('teams.php');
}

$team_id = $_GET['id'];

// Get team details
$stmt = $pdo->prepare("SELECT * FROM teams WHERE team_id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if (!$team) {
    redirect('teams.php');
}

// Get team players
$stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY position, jersey_number");
$stmt->execute([$team_id]);
$players = $stmt->fetchAll();

// Get team matches
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team 
                       FROM matches m
                       JOIN teams t1 ON m.home_team_id = t1.team_id
                       JOIN teams t2 ON m.away_team_id = t2.team_id
                       WHERE (m.home_team_id = ? OR m.away_team_id = ?)
                       ORDER BY m.match_date DESC");
$stmt->execute([$team_id, $team_id]);
$matches = $stmt->fetchAll();

// Check if user follows this team
$is_following = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE user_id = ? AND team_id = ?");
    $stmt->execute([$_SESSION['user_id'], $team_id]);
    $is_following = $stmt->fetchColumn() > 0;
}
?>

<div class="team-header">
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <?php if (!empty($team['team_logo'])): ?>
            <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 100px; height: 100px; margin-right: 20px;">
        <?php endif; ?>
        <h1><?php echo $team['team_name']; ?></h1>
    </div>
    
    <div class="team-actions" style="margin-bottom: 20px;">
        <?php if ($is_following): ?>
            <form action="unfollow_team.php" method="POST" style="display: inline;">
                <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                <button type="submit" class="btn" style="background-color: #dc3545;">Unfollow Team</button>
            </form>
        <?php else: ?>
            <form action="follow_team.php" method="POST" style="display: inline;">
                <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                <button type="submit" class="btn">Follow Team</button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="team-info" style="margin-bottom: 30px;">
        <p><strong>Founded:</strong> <?php echo $team['founded_year'] ?? 'Unknown'; ?></p>
        <p><strong>Home Stadium:</strong> <?php echo $team['home_stadium'] ?? 'Unknown'; ?></p>
        <p><strong>Coach:</strong> <?php echo $team['coach_name'] ?? 'Unknown'; ?></p>
    </div>
</div>

<div class="team-sections">
    <div class="card">
        <h3>Players</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Nationality</th>
                    <th>Age</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td><?php echo $player['jersey_number']; ?></td>
                        <td><?php echo $player['player_name']; ?></td>
                        <td><?php echo $player['position']; ?></td>
                        <td><?php echo $player['nationality']; ?></td>
                        <td>
                            <?php 
                            if ($player['date_of_birth']) {
                                $birthDate = new DateTime($player['date_of_birth']);
                                $today = new DateTime();
                                echo $today->diff($birthDate)->y;
                            } else {
                                echo 'Unknown';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h3>Recent Matches</h3>
        <?php if (!empty($matches)): ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-card">
                    <div class="team">
                        <div><?php echo $match['home_team']; ?></div>
                    </div>
                    <div class="score">
                        <?php if ($match['status'] === 'completed'): ?>
                            <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
                        <?php else: ?>
                            vs
                        <?php endif; ?>
                    </div>
                    <div class="team">
                        <div><?php echo $match['away_team']; ?></div>
                    </div>
                </div>
                <div style="text-align: center; margin-bottom: 15px;">
                    <?php echo date('M j, Y', strtotime($match['match_date'])); ?> at <?php echo $match['venue']; ?>
                    <?php if ($match['status'] === 'completed'): ?>
                        <a href="match.php?id=<?php echo $match['match_id']; ?>" style="margin-left: 10px;">View Details</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No matches found for this team.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>