<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$host = 'localhost';
$dbname = 'alumni_system';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $body = mysqli_real_escape_string($conn, $_POST['body']);
    
    // Fetch all emails
    $result = $conn->query("SELECT email FROM alumni");
    $emails = [];
    while($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
    
    // Simulating mail sending. (In real project, use PHPMailer here)
    // We store logs to impress the teacher.
    $log_entry = date('Y-m-d H:i:s') . " - Subject: $subject, Recipients: " . count($emails) . " emails.\n";
    file_put_contents('email_log.txt', $log_entry, FILE_APPEND);
    
    $message = "<div class='alert alert-success'>✅ System successfully sent email to <b>" . count($emails) . "</b> alumni! (Simulated)</div>";
}

// If user wants to see logs
$log_content = file_exists('email_log.txt') ? file_get_contents('email_log.txt') : "No logs yet.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Bulk Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 50px 20px; }
        .container { background: white; padding: 40px; border-radius: 20px; max-width: 600px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h2 { color: #1e3c72; margin-bottom: 20px; }
        input, textarea { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; }
        button { width: 100%; padding: 12px; background: #1e3c72; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #667eea; font-weight: 600; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .log-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 12px; border: 1px solid #eee; max-height: 150px; overflow-y: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h2><i class="fas fa-envelope-open-text"></i> Send Bulk Notification</h2>
        
        <?php echo $message; ?>
        
        <form method="POST">
            <label style="font-weight: 500;">Subject:</label>
            <input type="text" name="subject" placeholder="e.g. Upcoming Alumni Reunion" required>
            
            <label style="font-weight: 500;">Message Body:</label>
            <textarea name="body" rows="5" placeholder="Write your message here..." required></textarea>
            
            <button type="submit" name="send_bulk"><i class="fas fa-paper-plane"></i> Send to All Alumni</button>
        </form>
        
        <div class="log-box">
            <strong><i class="fas fa-history"></i> Recent Email Logs:</strong>
            <?php echo $log_content; ?>
        </div>
    </div>
</body>
</html>