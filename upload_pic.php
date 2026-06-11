<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] != 0) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$alumniId = isset($_POST['alumni_id']) ? $_POST['alumni_id'] : 0;

if (!$alumniId) {
    echo json_encode(['success' => false, 'error' => 'Invalid alumni ID']);
    exit;
}

$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['profile_pic'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF allowed']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Max 2MB allowed']);
    exit;
}

$filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
$uploadPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $conn = new mysqli('localhost', 'root', '', 'alumni_system');
    
    $result = $conn->query("SELECT profile_pic FROM alumni WHERE id = $alumniId");
    $old = $result->fetch_assoc();
    if ($old && $old['profile_pic'] && file_exists($uploadDir . $old['profile_pic'])) {
        unlink($uploadDir . $old['profile_pic']);
    }
    
    $conn->query("UPDATE alumni SET profile_pic = '$filename' WHERE id = $alumniId");
    
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
?>