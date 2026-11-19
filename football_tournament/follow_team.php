<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if already following
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_follows WHERE user_id = ? AND team_id = ?");
    $stmt->execute([$user_id, $team_id]);
    $already_following = $stmt->fetchColumn() > 0;
    
    if (!$already_following) {
        $stmt = $pdo->prepare("INSERT INTO user_follows (user_id, team_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $team_id]);
    }
    
    // Redirect back to previous page
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        redirect('teams.php');
    }
    exit();
}

redirect('teams.php');
?>