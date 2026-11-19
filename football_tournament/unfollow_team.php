<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("DELETE FROM user_follows WHERE user_id = ? AND team_id = ?");
    $stmt->execute([$user_id, $team_id]);
    
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