<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Football Tournament'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">Football Tournament</div>
            <nav>
                <ul>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>teams.php">Teams</a></li>
                        <li><a href="<?php echo BASE_URL; ?>tournaments.php">Tournaments</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>profile.php">Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">