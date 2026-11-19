<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$player_id = $_GET['id'];

// Get player details
$stmt = $pdo->prepare("SELECT p.*, t.team_name, t.team_logo 
                       FROM players p
                       JOIN teams t ON p.team_id = t.team_id
                       WHERE p.player_id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch();

if (!$player) {
    redirect('index.php');
}

// Calculate age
$age = '';
if ($player['date_of_birth']) {
    $birthDate = new DateTime($player['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

// Get player stats
$stats = [
    'goals' => 0,
    'assists' => 0,
    'yellow_cards' => 0,
    'red_cards' => 0
];

// Goals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM match_events 
                      WHERE player_id = ? AND event_type = 'goal'");
$stmt->execute([$player_id]);
$stats['goals'] = $stmt->fetchColumn();

// Assists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM match_events 
                      WHERE related_player_id = ? AND event_type = 'goal'");
$stmt->execute([$player_id]);
$stats['assists'] = $stmt->fetchColumn();

// Yellow cards
$stmt = $pdo->prepare("SELECT COUNT(*) FROM match_events 
                      WHERE player_id = ? AND event_type = 'yellow_card'");
$stmt->execute([$player_id]);
$stats['yellow_cards'] = $stmt->fetchColumn();

// Red cards
$stmt = $pdo->prepare("SELECT COUNT(*) FROM match_events 
                      WHERE player_id = ? AND event_type = 'red_card'");
$stmt->execute([$player_id]);
$stats['red_cards'] = $stmt->fetchColumn();
?>

<div class="player-header" style="display: flex; align-items: center; margin-bottom: 30px;">
    <div style="margin-right: 30px; text-align: center;">
        <?php if (!empty($player['team_logo'])): ?>
            <img src="<?php echo BASE_URL . 'uploads/' . $player['team_logo']; ?>" alt="<?php echo $player['team_name']; ?>" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px;">
        <?php endif; ?>
        <h3><a href="team.php?id=<?php echo $player['team_id']; ?>"><?php echo $player['team_name']; ?></a></h3>
    </div>
    <div>
        <h1><?php echo $player['player_name']; ?></h1>
        <p><strong>Jersey Number:</strong> <?php echo $player['jersey_number']; ?></p>
        <p><strong>Position:</strong> <?php echo $player['position']; ?></p>
        <p><strong>Nationality:</strong> <?php echo $player['nationality']; ?></p>
        <?php if ($age): ?>
            <p><strong>Age:</strong> <?php echo $age; ?></p>
        <?php endif; ?>
        <?php if ($player['height']): ?>
            <p><strong>Height:</strong> <?php echo $player['height']; ?> cm</p>
        <?php endif; ?>
        <?php if ($player['weight']): ?>
            <p><strong>Weight:</strong> <?php echo $player['weight']; ?> kg</p>
        <?php endif; ?>
    </div>
</div>

<div class="player-stats">
    <div class="card">
        <h3>Player Statistics</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #1a3e72;"><?php echo $stats['goals']; ?></div>
                <div>Goals</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #1a3e72;"><?php echo $stats['assists']; ?></div>
                <div>Assists</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #ffc107;"><?php echo $stats['yellow_cards']; ?></div>
                <div>Yellow Cards</div>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <div style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo $stats['red_cards']; ?></div>
                <div>Red Cards</div>
            </div>
        </div>
    </div>
</div>

<div class="player-matches" style="margin-top: 30px;">
    <div class="card">
        <h3>Recent Matches</h3>
        <?php
        // Get matches where the player participated
        $stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team, 
                               tr.tournament_name,
                               (SELECT COUNT(*) FROM match_events WHERE match_id = m.match_id AND player_id = ? AND event_type = 'goal') as goals,
                               (SELECT COUNT(*) FROM match_events WHERE match_id = m.match_id AND related_player_id = ? AND event_type = 'goal') as assists
                               FROM matches m
                               JOIN teams t1 ON m.home_team_id = t1.team_id
                               JOIN teams t2 ON m.away_team_id = t2.team_id
                               JOIN tournaments tr ON m.tournament_id = tr.tournament_id
                               WHERE m.match_id IN (
                                   SELECT match_id FROM match_events WHERE player_id = ? OR related_player_id = ?
                               )
                               ORDER BY m.match_date DESC
                               LIMIT 5");
        $stmt->execute([$player_id, $player_id, $player_id, $player_id]);
        $matches = $stmt->fetchAll();
        ?>
        
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
                    <br>
                    <strong>Performance:</strong> 
                    <?php if ($match['goals'] > 0): ?>
                        ⚽ <?php echo $match['goals']; ?> goal<?php echo $match['goals'] > 1 ? 's' : ''; ?>
                    <?php endif; ?>
                    <?php if ($match['assists'] > 0): ?>
                        🅰️ <?php echo $match['assists']; ?> assist<?php echo $match['assists'] > 1 ? 's' : ''; ?>
                    <?php endif; ?>
                    <?php if ($match['goals'] == 0 && $match['assists'] == 0): ?>
                        No goals or assists
                    <?php endif; ?>
                    <br>
                    <a href="match.php?id=<?php echo $match['match_id']; ?>">View Match</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No match data available for this player.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>