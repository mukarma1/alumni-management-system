<?php
if (session_status() == PHP_SESSION_NONE) {
 session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <title>Alumni Record Management System</title>
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/
water.css">
</head>
<body>
 <header>
 <nav>
 <strong>Alumni Portal</strong> |
 <a href="/alumni_system/index.php">Dashboard</a> |
 <a href="/alumni_system/create.php">Add New Alumni</a> |
 <?php if(isset($_SESSION['user_id'])): ?>
 <a href="/alumni_system/auth/logout.php" style="float:right;">Logout
(<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
php else: ?>
 <a href="/alumni_system/auth/login.php" style="float:right;">Login</a>
 <?php endif; ?>
 </nav>
 </header>
 <hr>
