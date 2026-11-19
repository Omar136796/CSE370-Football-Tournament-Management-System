<?php
require_once 'config.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get all tournaments
$stmt = $pdo->query("SELECT * FROM tournaments ORDER BY start_date DESC");
$tournaments = $stmt->fetchAll();
?>

<h1>Tournaments</h1>

<div class="tournament-list">
    <?php foreach ($tournaments as $tournament): ?>
        <div class="card" style="margin-bottom: 20px;">
            <h3>
                <a href="tournament.php?id=<?php echo $tournament['tournament_id']; ?>">
                    <?php echo $tournament['tournament_name']; ?>
                </a>
            </h3>
            <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $tournament['tournament_type'])); ?></p>
            <p><strong>Dates:</strong> <?php echo date('M j, Y', strtotime($tournament['start_date'])); ?> to <?php echo date('M j, Y', strtotime($tournament['end_date'])); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($tournament['status']); ?></p>
            <p><?php echo $tournament['description']; ?></p>
            <a href="tournament.php?id=<?php echo $tournament['tournament_id']; ?>" class="btn">View Tournament</a>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>