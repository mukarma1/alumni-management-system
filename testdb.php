<?php
echo "<h2>Testing Database Connection</h2>";

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'alumni_system';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully!<br>";
}

$result = $conn->query("SELECT * FROM users");
if ($result->num_rows > 0) {
    echo "✅ Users table exists and has " . $result->num_rows . " record(s)<br>";
    while($row = $result->fetch_assoc()) {
        echo "- Username: " . $row['username'] . "<br>";
    }
} else {
    echo "❌ No users found. Please run the SQL to create users table.<br>";
}

$conn->close();
?>