<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$match_id = $_GET['id'];

// Get match details
$stmt = $pdo->prepare("SELECT m.*, t1.team_name as home_team, t2.team_name as away_team, 
                       t1.team_logo as home_logo, t2.team_logo as away_logo,
                       tr.tournament_name
                       FROM matches m
                       JOIN teams t1 ON m.home_team_id = t1.team_id
                       JOIN teams t2 ON m.away_team_id = t2.team_id
                       JOIN tournaments tr ON m.tournament_id = tr.tournament_id
                       WHERE m.match_id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    redirect('index.php');
}

// Get match events (goals, cards, etc.)
$stmt = $pdo->prepare("SELECT me.*, p.player_name, p.jersey_number, 
                       rp.player_name as related_player_name
                       FROM match_events me
                       JOIN players p ON me.player_id = p.player_id
                       LEFT JOIN players rp ON me.related_player_id = rp.player_id
                       WHERE me.match_id = ?
                       ORDER BY me.minute ASC");
$stmt->execute([$match_id]);
$events = $stmt->fetchAll();

// Get match reviews
$stmt = $pdo->prepare("SELECT mr.*, u.username 
                       FROM match_reviews mr
                       JOIN users u ON mr.user_id = u.user_id
                       WHERE mr.match_id = ? AND mr.parent_review_id IS NULL
                       ORDER BY mr.created_at DESC");
$stmt->execute([$match_id]);
$reviews = $stmt->fetchAll();

// Get replies for each review
foreach ($reviews as &$review) {
    $stmt = $pdo->prepare("SELECT mr.*, u.username 
                          FROM match_reviews mr
                          JOIN users u ON mr.user_id = u.user_id
                          WHERE mr.parent_review_id = ?
                          ORDER BY mr.created_at ASC");
    $stmt->execute([$review['review_id']]);
    $review['replies'] = $stmt->fetchAll();
}
unset($review);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $review_text = sanitizeInput($_POST['review_text']);
    $rating = intval($_POST['rating']);
    $parent_review_id = isset($_POST['parent_review_id']) ? intval($_POST['parent_review_id']) : null;
    
    if (!empty($review_text) ){
        $stmt = $pdo->prepare("INSERT INTO match_reviews (match_id, user_id, parent_review_id, review_text, rating) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$match_id, $_SESSION['user_id'], $parent_review_id, $review_text, $rating]);
        
        // Refresh page to show new review
        redirect("match.php?id=$match_id");
    }
}
?>

<div class="match-header">
    <h1>
        <a href="tournament.php?id=<?php echo $match['tournament_id']; ?>">
            <?php echo $match['tournament_name']; ?>
        </a>
    </h1>
    
    <div class="match-info" style="text-align: center; margin: 20px 0;">
        <p><?php echo date('M j, Y H:i', strtotime($match['match_date'])); ?> at <?php echo $match['venue']; ?></p>
        <p><?php echo $match['match_round'] ? 'Round: ' . $match['match_round'] : ''; ?></p>
    </div>
    
    <div class="match-score" style="display: flex; justify-content: center; align-items: center; margin: 30px 0;">
        <div style="text-align: center; margin: 0 20px;">
            <?php if (!empty($match['home_logo'])): ?>
                <img src="<?php echo BASE_URL . 'uploads/' . $match['home_logo']; ?>" alt="<?php echo $match['home_team']; ?>" style="width: 80px; height: 80px; object-fit: contain;">
            <?php endif; ?>
            <h3><?php echo $match['home_team']; ?></h3>
        </div>
        
        <div style="font-size: 24px; font-weight: bold; margin: 0 20px;">
            <?php if ($match['status'] === 'completed'): ?>
                <?php echo $match['home_score']; ?> - <?php echo $match['away_score']; ?>
            <?php else: ?>
                vs
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin: 0 20px;">
            <?php if (!empty($match['away_logo'])): ?>
                <img src="<?php echo BASE_URL . 'uploads/' . $match['away_logo']; ?>" alt="<?php echo $match['away_team']; ?>" style="width: 80px; height: 80px; object-fit: contain;">
            <?php endif; ?>
            <h3><?php echo $match['away_team']; ?></h3>
        </div>
    </div>
</div>

<div class="match-sections">
    <?php if ($match['status'] === 'completed' && !empty($events)): ?>
        <div class="card">
            <h3>Match Events</h3>
            <table>
                <thead>
                    <tr>
                        <th>Minute</th>
                        <th>Team</th>
                        <th>Player</th>
                        <th>Event</th>
                        <th>Details</th>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h3>Match Reviews</h3>
        
        <?php if (isLoggedIn()): ?>
            <div class="review-form" style="margin-bottom: 30px;">
                <h4>Add Your Review</h4>
                <form method="POST">
                    <div class="form-group">
                        <label for="review_text">Your Review</label>
                        <textarea id="review_text" name="review_text" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <select id="rating" name="rating" required>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <button type="submit" name="submit_review" class="btn">Submit Review</button>
                </form>
            </div>
        <?php else: ?>
            <p><a href="login.php">Login</a> to post a review.</p>
        <?php endif; ?>
        
        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review">
                        <div class="review-header">
                            <div class="review-user"><?php echo $review['username']; ?></div>
                            <div class="review-rating">
                                <?php 
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="review-date">
                            <?php echo date('M j, Y H:i', strtotime($review['created_at'])); ?>
                        </div>
                        <div class="review-text">
                            <?php echo nl2br($review['review_text']); ?>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <button onclick="document.getElementById('reply-form-<?php echo $review['review_id']; ?>').style.display='block'; this.style.display='none';" 
                                    class="btn" style="margin-top: 10px; padding: 5px 10px; font-size: 14px;">
                                Reply
                            </button>
                            
                            <form id="reply-form-<?php echo $review['review_id']; ?>" method="POST" style="display: none; margin-top: 10px;">
                                <input type="hidden" name="parent_review_id" value="<?php echo $review['review_id']; ?>">
                                <div class="form-group">
                                    <textarea name="review_text" rows="2" required placeholder="Write your reply..."></textarea>
                                </div>
                                <input type="hidden" name="rating" value="3">
                                <button type="submit" name="submit_review" class="btn" style="padding: 5px 10px; font-size: 14px;">Post Reply</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (!empty($review['replies'])): ?>
                            <?php foreach ($review['replies'] as $reply): ?>
                                <div class="reply">
                                    <div class="review-header">
                                        <div class="review-user"><?php echo $reply['username']; ?></div>
                                        <div class="review-date">
                                            <?php echo date('M j, Y H:i', strtotime($reply['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="review-text">
                                        <?php echo nl2br($reply['review_text']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No reviews yet. Be the first to review!</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>