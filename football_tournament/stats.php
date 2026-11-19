<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get all tournaments for the dropdown
$stmt = $pdo->query("SELECT * FROM tournaments ORDER BY start_date DESC");
$tournaments = $stmt->fetchAll();

// Get stats for selected tournament or all tournaments
$selected_tournament = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : null;

// Top scorers
$top_scorers_sql = "SELECT p.player_id, p.player_name, t.team_name, COUNT(*) as goals
                   FROM match_events me
                   JOIN players p ON me.player_id = p.player_id
                   JOIN teams t ON p.team_id = t.team_id
                   JOIN matches m ON me.match_id = m.match_id
                   WHERE me.event_type = 'goal'";
                   
if ($selected_tournament) {
    $top_scorers_sql .= " AND m.tournament_id = $selected_tournament";
}

$top_scorers_sql .= " GROUP BY p.player_id ORDER BY goals DESC LIMIT 10";

$top_scorers = $pdo->query($top_scorers_sql)->fetchAll();

// Top assist providers
$top_assists_sql = "SELECT p.player_id, p.player_name, t.team_name, COUNT(*) as assists
                   FROM match_events me
                   JOIN players p ON me.related_player_id = p.player_id
                   JOIN teams t ON p.team_id = t.team_id
                   JOIN matches m ON me.match_id = m.match_id
                   WHERE me.event_type = 'goal' AND me.related_player_id IS NOT NULL";
                   
if ($selected_tournament) {
    $top_assists_sql .= " AND m.tournament_id = $selected_tournament";
}

$top_assists_sql .= " GROUP BY p.player_id ORDER BY assists DESC LIMIT 10";

$top_assists = $pdo->query($top_assists_sql)->fetchAll();

// Most yellow cards
$yellow_cards_sql = "SELECT p.player_id, p.player_name, t.team_name, COUNT(*) as yellow_cards
                    FROM match_events me
                    JOIN players p ON me.player_id = p.player_id
                    JOIN teams t ON p.team_id = t.team_id
                    JOIN matches m ON me.match_id = m.match_id
                    WHERE me.event_type = 'yellow_card'";
                    
if ($selected_tournament) {
    $yellow_cards_sql .= " AND m.tournament_id = $selected_tournament";
}

$yellow_cards_sql .= " GROUP BY p.player_id ORDER BY yellow_cards DESC LIMIT 10";

$yellow_cards = $pdo->query($yellow_cards_sql)->fetchAll();

// Most red cards
$red_cards_sql = "SELECT p.player_id, p.player_name, t.team_name, COUNT(*) as red_cards
                 FROM match_events me
                 JOIN players p ON me.player_id = p.player_id
                 JOIN teams t ON p.team_id = t.team_id
                 JOIN matches m ON me.match_id = m.match_id
                 WHERE me.event_type = 'red_card'";
                 
if ($selected_tournament) {
    $red_cards_sql .= " AND m.tournament_id = $selected_tournament";
}

$red_cards_sql .= " GROUP BY p.player_id ORDER BY red_cards DESC LIMIT 10";

$red_cards = $pdo->query($red_cards_sql)->fetchAll();
?>

<h1>Statistics</h1>

<div class="stats-filter" style="margin-bottom: 20px;">
    <form method="GET">
        <div class="form-group" style="display: inline-block; margin-right: 15px;">
            <label for="tournament_id">Select Tournament:</label>
            <select id="tournament_id" name="tournament_id" onchange="this.form.submit()">
                <option value="">All Tournaments</option>
                <?php foreach ($tournaments as $tournament): ?>
                    <option value="<?php echo $tournament['tournament_id']; ?>" 
                        <?php if ($selected_tournament == $tournament['tournament_id']) echo 'selected'; ?>>
                        <?php echo $tournament['tournament_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="stats-sections">
    <div class="card">
        <h3>Top Scorers</h3>
        <?php if (!empty($top_scorers)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th>Goals</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_scorers as $index => $player): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><a href="player.php?id=<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></a></td>
                            <td><?php echo $player['team_name']; ?></td>
                            <td><?php echo $player['goals']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No goal data available.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3>Top Assist Providers</h3>
        <?php if (!empty($top_assists)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th>Assists</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_assists as $index => $player): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><a href="player.php?id=<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></a></td>
                            <td><?php echo $player['team_name']; ?></td>
                            <td><?php echo $player['assists']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No assist data available.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3>Most Yellow Cards</h3>
        <?php if (!empty($yellow_cards)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th>Yellow Cards</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yellow_cards as $index => $player): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><a href="player.php?id=<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></a></td>
                            <td><?php echo $player['team_name']; ?></td>
                            <td><?php echo $player['yellow_cards']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No yellow card data available.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h3>Most Red Cards</h3>
        <?php if (!empty($red_cards)): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th>Red Cards</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($red_cards as $index => $player): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><a href="player.php?id=<?php echo $player['player_id']; ?>"><?php echo $player['player_name']; ?></a></td>
                            <td><?php echo $player['team_name']; ?></td>
                            <td><?php echo $player['red_cards']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No red card data available.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>