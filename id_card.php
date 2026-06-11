<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'alumni_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$result = $conn->query("SELECT * FROM alumni WHERE id = $id");
$row = $result->fetch_assoc();

if (!$row) {
    echo "Alumni not found!";
    exit;
}

$gradYear = $row['graduation_year'];
$currentYear = date('Y');
$anniversary = $currentYear - $gradYear;

// Determine badge
if ($anniversary >= 15) {
    $badge = "GOLD MEMBER";
    $badgeIcon = "🏆";
    $badgeColor = "#FFD700";
} elseif ($anniversary >= 10) {
    $badge = "SILVER MEMBER";
    $badgeIcon = "🥈";
    $badgeColor = "#C0C0C0";
} elseif ($anniversary >= 5) {
    $badge = "BRONZE MEMBER";
    $badgeIcon = "🥉";
    $badgeColor = "#CD7F32";
} else {
    $badge = "ACTIVE MEMBER";
    $badgeIcon = "⭐";
    $badgeColor = "#4CAF50";
}

// Check if photo exists
$photoPath = 'uploads/' . $row['profile_pic'];
$hasPhoto = (!empty($row['profile_pic']) && file_exists($photoPath));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Alumni ID Card - <?php echo $row['full_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card-container {
            text-align: center;
        }
        .id-card {
            width: 520px;
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .id-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .card-header h2 {
            font-size: 24px;
            letter-spacing: 2px;
        }
        .card-header p {
            font-size: 12px;
            opacity: 0.8;
        }
        .card-body {
            padding: 30px;
            display: flex;
            gap: 25px;
            background: white;
        }
        .photo-section {
            text-align: center;
        }
        .photo-frame {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4px;
        }
        .photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo i {
            font-size: 65px;
            color: #999;
        }
        .info-section {
            flex: 1;
        }
        .info-section h3 {
            color: #1e3c72;
            font-size: 20px;
            margin-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
            padding-bottom: 5px;
        }
        .detail-item {
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        .detail-item i {
            width: 25px;
            color: #2a5298;
            font-size: 14px;
        }
        .badge-container {
            margin-top: 15px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: <?php echo $badgeColor; ?>;
            color: <?php echo ($badgeColor == '#FFD700' || $badgeColor == '#C0C0C0') ? '#333' : 'white'; ?>;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: bold;
        }
        .qr-section {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .qr-section i {
            font-size: 50px;
            color: #1e3c72;
        }
        .qr-section p {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .card-footer {
            padding: 15px;
            text-align: center;
            background: #1e3c72;
            color: white;
        }
        .card-footer p {
            font-size: 10px;
            margin: 3px 0;
        }
        .btn-print, .btn-back {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin: 0 5px;
            transition: all 0.3s;
        }
        .btn-print {
            background: #1e3c72;
            color: white;
        }
        .btn-back {
            background: #dc3545;
            color: white;
        }
        .btn-print:hover, .btn-back:hover {
            transform: scale(1.05);
        }
        @media print {
            .btn-print, .btn-back, .card-container > div:last-child {
                display: none;
            }
            .id-card {
                box-shadow: none;
                margin: 0;
            }
            body {
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="id-card">
            <div class="card-header">
                <i class="fas fa-graduation-cap" style="font-size: 35px; margin-bottom: 5px;"></i>
                <h2>ALUMNI ID CARD</h2>
                <p>Department of Computer Science</p>
            </div>
            <div class="card-body">
                <div class="photo-section">
                    <div class="photo-frame">
                        <div class="photo">
                            <?php if($hasPhoto): ?>
                                <img src="<?php echo $photoPath; ?>" alt="Profile Photo">
                            <?php else: ?>
                                <i class="fas fa-user-graduate"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="info-section">
                    <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($row['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?php echo htmlspecialchars($row['degree_program']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Batch of <?php echo $row['graduation_year']; ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-briefcase"></i>
                        <span><?php echo htmlspecialchars($row['job_title'] . ' at ' . $row['current_company']); ?></span>
                    </div>
                    <div class="badge-container">
                        <span class="badge"><?php echo $badgeIcon; ?> <?php echo $badge; ?></span>
                    </div>
                </div>
            </div>
            <div class="qr-section">
                <i class="fas fa-qrcode"></i>
                <p>Scan to Verify | ID: <?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="card-footer">
                <p>📍 Valid ID • Lifetime Membership • CS Department</p>
                <p>✨ "Proud to be an Alumnus" ✨</p>
            </div>
        </div>
        <div>
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print ID Card</button>
            <button class="btn-back" onclick="window.location.href='index.php'"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
        </div>
    </div>
</body>
</html>