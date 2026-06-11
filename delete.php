<?php
require_once 'config/database.php';
session_start();
if (!isset($_SESSION['user_id'])) 
{
    header('Location: auth/login.php');
    exit;
}
if (isset($_GET['id'])) 
{
     $id = (int)$_GET['id'];
     $stmt = $conn->prepare('DELETE FROM alumni WHERE id = ?');
     $stmt->execute([$id]);
}
header('Location: index.php');
exit;
?>