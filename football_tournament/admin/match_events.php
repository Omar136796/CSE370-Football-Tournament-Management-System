<?php
require_once '../config.php';
require_once '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if (!isset($_GET['id'])) {
    redirect('matches.php');
}

$match_id = $_GET['id'];

// Get match details
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team 
                      FROM matches m
                      JOIN teams t1 ON m.home_team_id = t1.team_id
                      JOIN teams t2 ON m.away_team_id = t2.team_id
                      WHERE m.match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    redirect('matches.php');
}

// Get match events
$stmt = $pdo->prepare("SELECT me.*, p.player_name, p.jersey_number, 
                      rp.player_name as related_player_name
                      FROM match_events me
                      JOIN players p ON me.player_id = p.player_id
                      LEFT JOIN players rp ON me.related_player_id = rp.player_id
                      WHERE me.match_id = ?
                      ORDER BY me.minute ASC");
$stmt->execute([$match_id]);
$events = $stmt->fetchAll();

// Get players from both teams
$stmt = $pdo->prepare("SELECT p.* FROM players p
                      WHERE p.team_id IN (?, ?)
                      ORDER BY p.team_id, p.player_name");
$stmt->execute([$match['home_team_id'], $match['away_team_id']]);
$players = $stmt->fetchAll();

// Handle add event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $event_type = sanitizeInput($_POST['event_type']);
    $player_id = intval($_POST['player_id']);
    $team_id = intval($_POST['team_id']);
    $minute = intval($_POST['minute']);
    $related_player_id = isset($_POST['related_player_id']) ? intval($_POST['related_player_id']) : null;
    
    // Validate inputs
    if (empty($event_type) || empty($player_id) || empty($team_id) || empty($minute)) {
        $error = 'Required fields are missing';
    } else {
        $stmt = $pdo->prepare("INSERT INTO match_events (match_id, player_id, team_id, event_type, minute, related_player_id) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$match_id, $player_id, $team_id, $event_type, $minute, $related_player_id])) {
            // Redirect to refresh the page
            redirect("match_events.php?id=$match_id");
        } else {
            $error = 'Failed to add event';
        }
    }
}

// Handle delete event
if (isset($_GET['delete_event']) && is_numeric($_GET['delete_event'])) {
    $event_id = $_GET['delete_event'];
    
    $stmt = $pdo->prepare("DELETE FROM match_events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    // Redirect to refresh the page
    redirect("match_events.php?id=$match_id");
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
            <li><a href="matches.php">Manage Matches</a></li>
            <li><a href="standings.php">Update Standings</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h1>Match Events for <?php echo $match['home_team']; ?> vs <?php echo $match['away_team']; ?></h1>
        
        <div style="margin-bottom: 20px;">
            <a href="matches.php" class="btn" style="background-color: #6c757d;">Back to Matches</a>
        </div>
        
        <div class="card" style="margin-bottom: 30px;">
            <h3>Add New Event</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <select id="event_type" name="event_type" required>
                        <option value="goal">Goal</option>
                        <option value="assist">Assist</option>
                        <option value="yellow_card">Yellow Card</option>
                        <option value="red_card">Red Card</option>
                        <option value="substitution">Substitution</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="team_id">Team</label>
                    <select id="team_id" name="team_id" required>
                        <option value="<?php echo $match['home_team_id']; ?>"><?php echo $match['home_team']; ?></option>
                        <option value="<?php echo $match['away_team_id']; ?>"><?php echo $match['away_team']; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="player_id">Player</label>
                    <select id="player_id" name="player_id" required>
                        <?php foreach ($players as $player): ?>
                            <option value="<?php echo $player['player_id']; ?>" data-team="<?php echo $player['team_id']; ?>">
                                <?php echo $player['player_name']; ?> (<?php echo $player['jersey_number'] ?? 'N/A'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="minute">Minute</label>
                    <input type="number" id="minute" name="minute" min="1" max="120" required>
                </div>
                <div class="form-group" id="related_player_group" style="display: none;">
                    <label for="related_player_id">Related Player (for assists or substitutions)</label>
                    <select id="related_player_id" name="related_player_id">
                        <?php foreach ($players as $player): ?>
                            <option value="<?php echo $player['player_id']; ?>">
                                <?php echo $player['player_name']; ?> (<?php echo $player['jersey_number'] ?? 'N/A'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_event" class="btn">Add Event</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Match Events</h3>
            <?php if (!empty($events)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Minute</th>
                            <th>Team</th>
                            <th>Player</th>
                            <th>Event</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo $event['minute']; ?>'</td>
                                <td>
                                    <?php 
                                    $team_name = ($event['team_id'] == $match['home_team_id']) ? $match['home_team'] : $match['away_team'];
                                    echo $team_name;
                                    ?>
                                </td>
                                <td><?php echo $event['player_name']; ?> (<?php echo $event['jersey_number']; ?>)</td>
                                <td>
                                    <?php 
                                    switch ($event['event_type']) {
                                        case 'goal': echo '⚽ Goal'; break;
                                        case 'assist': echo '🅰️ Assist'; break;
                                        case 'yellow_card': echo '🟨 Yellow Card'; break;
                                        case 'red_card': echo '🟥 Red Card'; break;
                                        case 'substitution': echo '🔄 Substitution'; break;
                                        default: echo ucfirst($event['event_type']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($event['event_type'] === 'goal' && $event['related_player_id']) {
                                        echo 'Assist by ' . $event['related_player_name'];
                                    } elseif ($event['event_type'] === 'substitution' && $event['related_player_id']) {
                                        echo 'In: ' . $event['player_name'] . ', Out: ' . $event['related_player_name'];
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="match_events.php?id=<?php echo $match_id; ?>&delete_event=<?php echo $event['event_id']; ?>" class="btn" style="padding: 5px 10px; font-size: 14px; background-color: #dc3545;" onclick="return confirm('Are you sure you want to delete this event?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No events recorded for this match yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Show/hide related player field based on event type
document.getElementById('event_type').addEventListener('change', function() {
    const relatedPlayerGroup = document.getElementById('related_player_group');
    if (this.value === 'goal' || this.value === 'substitution') {
        relatedPlayerGroup.style.display = 'block';
    } else {
        relatedPlayerGroup.style.display = 'none';
    }
});

// Filter players based on selected team
document.getElementById('team_id').addEventListener('change', function() {
    const teamId = this.value;
    const playerSelect = document.getElementById('player_id');
    const options = playerSelect.options;
    
    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        if (option.dataset.team === teamId) {
            option.style.display = '';
            if (i === 0 || option.selected) {
                playerSelect.value = option.value;
            }
        } else {
            option.style.display = 'none';
            if (option.selected) {
                playerSelect.value = '';
            }
        }
    }
});

// Trigger change event on page load to set initial state
document.getElementById('event_type').dispatchEvent(new Event('change'));
document.getElementById('team_id').dispatchEvent(new Event('change'));
</script>

<?php require_once '../includes/footer.php'; ?>
