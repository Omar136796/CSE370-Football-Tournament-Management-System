<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('tournaments.php');
}

$tournament_id = $_GET['id'];

// Get tournament details
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE tournament_id = ?");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    redirect('tournaments.php');
}

// Get tournament teams
$stmt = $pdo->prepare("SELECT t.* FROM teams t
                      JOIN tournament_teams tt ON t.team_id = tt.team_id
                      WHERE tt.tournament_id = ?
                      ORDER BY t.team_name");
$stmt->execute([$tournament_id]);
$teams = $stmt->fetchAll();

// Get tournament matches
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team 
                      FROM matches m
                      JOIN teams t1 ON m.home_team_id = t1.team_id
                      JOIN teams t2 ON m.away_team_id = t2.team_id
                      WHERE m.tournament_id = ?
                      ORDER BY m.match_date ASC");
$stmt->execute([$tournament_id]);
$matches = $stmt->fetchAll();

// Get standings if it's a league or group stage tournament
$standings = [];
if ($tournament['tournament_type'] === 'league' || $tournament['tournament_type'] === 'group_stage') {
    $stmt = $pdo->prepare("SELECT s.*, t.team_name, t.team_logo 
                          FROM standings s
                          JOIN teams t ON s.team_id = t.team_id
                          WHERE s.tournament_id = ?
                          ORDER BY s.group_name, s.points DESC, s.goal_difference DESC, s.goals_for DESC");
    $stmt->execute([$tournament_id]);
    $standings = $stmt->fetchAll();
}
?>

<div class="tournament-header">
    <h1><?php echo $tournament['tournament_name']; ?></h1>
    <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $tournament['tournament_type'])); ?></p>
    <p><strong>Dates:</strong> <?php echo date('M j, Y', strtotime($tournament['start_date'])); ?> to <?php echo date('M j, Y', strtotime($tournament['end_date'])); ?></p>
    <p><strong>Status:</strong> <?php echo ucfirst($tournament['status']); ?></p>
    <p><?php echo $tournament['description']; ?></p>
</div>

<div class="tournament-sections">
    <div class="card">
        <h3>Teams</h3>
        <div class="team-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($teams as $team): ?>
                <div style="text-align: center;">
                    <?php if (!empty($team['team_logo'])): ?>
                        <img src="<?php echo BASE_URL . 'uploads/' . $team['team_logo']; ?>" alt="<?php echo $team['team_name']; ?>" style="width: 50px; height: 50px; object-fit: contain;">
                    <?php endif; ?>
                    <div><a href="team.php?id=<?php echo $team['team_id']; ?>"><?php echo $team['team_name']; ?></a></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if (!empty($standings)): ?>
        <div class="card">
            <h3>Standings</h3>
            <?php
            // Group standings by group if it's a group stage tournament
            $grouped_standings = [];
            if ($tournament['tournament_type'] === 'group_stage') {
                foreach ($standings as $standing) {
                    $grouped_standings[$standing['group_name']][] = $standing;
                }
            } else {
                $grouped_standings[''] = $standings;
            }
            
            foreach ($grouped_standings as $group => $group_standings):
                if ($group): ?>
                    <h4>Group <?php echo $group; ?></h4>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Team</th>
                            <th>P</th>
                            <th>W</th>
                            <th>D</th>
                            <th>L</th>
                            <th>GF</th>
                            <th>GA</th>
                            <th>GD</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group_standings as $index => $standing): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if (!empty($standing['team_logo'])): ?>
                                        <img src="<?php echo BASE_URL . 'uploads/' . $standing['team_logo']; ?>" alt="<?php echo $standing['team_name']; ?>" style="width: 20px; height: 20px; margin-right: 5px; vertical-align: middle;">
                                    <?php endif; ?>
                                    <?php echo $standing['team_name']; ?>
                                </td>
                                <td><?php echo $standing['matches_played']; ?></td>
                                <td><?php echo $standing['wins']; ?></td>
                                <td><?php echo $standing['draws']; ?></td>
                                <td><?php echo $standing['losses']; ?></td>
                                <td><?php echo $standing['goals_for']; ?></td>
                                <td><?php echo $standing['goals_against']; ?></td>
                                <td><?php echo $standing['goal_difference']; ?></td>
                                <td><?php echo $standing['points']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Matches</h3>
        <?php if (!empty($matches)): ?>
            <?php
            // Group matches by round or date
            $grouped_matches = [];
            foreach ($matches as $match) {
                $group = $match['match_round'] ? $match['match_round'] : date('Y-m-d', strtotime($match['match_date']));
                $grouped_matches[$group][] = $match;
            }
            
            foreach ($grouped_matches as $group => $group_matches):
                if ($group && $tournament['tournament_type'] !== 'league'): ?>
                    <h4><?php echo $group; ?></h4>
                <?php elseif ($group): ?>
                    <h4><?php echo date('M j, Y', strtotime($group)); ?></h4>
                <?php endif; ?>
                
                <?php foreach ($group_matches as $match): ?>
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
                        <?php echo date('M j, Y H:i', strtotime($match['match_date'])); ?> at <?php echo $match['venue']; ?>
                        <?php if ($match['status'] === 'completed'): ?>
                            <a href="match.php?id=<?php echo $match['match_id']; ?>" style="margin-left: 10px;">View Details</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No matches scheduled for this tournament yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>