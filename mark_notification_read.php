<?php
session_start();
if (!isset($_SESSION['user_id'])) { exit; }

$host = 'localhost';
$dbname = 'alumni_system';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed"); }

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Notification fetch karo
    $query = $conn->query("SELECT * FROM notifications WHERE id = $id");
    $notif = $query->fetch_assoc();

    if ($notif) {
        // Notification ko read mark karo
        $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $id");

        // User ko relevant page par bhejo
        if ($notif['type'] == 'new_alumni') {
            header("Location: index.php?highlight_id=" . $notif['target_id']);
            exit;
        } elseif ($notif['type'] == 'new_story') {
            header("Location: index.php#stories_section");
            exit;
        }
    }
}
header("Location: index.php");
exit;
?>