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

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = intval($_GET['id']);
$query = $conn->query("SELECT * FROM alumni WHERE id = $id");
$row = $query->fetch_assoc();

if (!$row) {
    die("Alumni not found.");
}

// Agar profile pic upload hai toh use karein, warna default avatar use karein
if(!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])) {
    $img_src = 'uploads/' . $row['profile_pic'];
} else {
    $img_src = 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name']) . '&background=2a5298&color=fff&size=128&bold=true';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni ID Card</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #eef2f7;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        /* Card Container with Realistic Paper Border */
        .card-container {
            width: 380px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(30, 60, 114, 0.15);
            overflow: hidden;
            position: relative;
            border: 3px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        /* Premium Header with Shine Effect */
        .card-header {
            background: linear-gradient(145deg, #0b1e33 0%, #1e3c72 100%);
            padding: 30px 20px 50px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        /* Shine Animation in Header */
        .card-header::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to bottom right, rgba(255,255,255,0.1) 0%, transparent 50%, transparent 100%);
            transform: rotate(30deg);
            pointer-events: none;
        }

        .card-header i {
            font-size: 48px;
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }
        
        .card-header h3 {
            margin: 10px 0 0 0;
            font-size: 18px;
            letter-spacing: 2px;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }
        
        /* Profile Pic Section */
        .photo-section {
            position: relative;
            margin-top: -50px;
            display: flex;
            justify-content: center;
            z-index: 10;
        }
        
        .profile-pic {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 5px solid #ffffff;
            object-fit: cover;
            background: #f0f2f5;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        /* Info Section with Glass Badges */
        .info-section {
            padding: 20px 25px 35px 25px;
            text-align: center;
            position: relative;
        }
        
        /* Watermark behind text */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 80px;
            color: rgba(30, 60, 114, 0.04);
            font-weight: 900;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 10px;
        }

        .info-section h2 {
            color: #0b1e33;
            margin: 10px 0 15px 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Detail Badges */
        .detail-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #f8faff;
            border: 1px solid #eaeff5;
            padding: 8px 16px;
            border-radius: 50px;
            margin: 5px 4px;
            font-size: 13px;
            color: #444;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .detail-badge i {
            color: #1e3c72;
            width: 16px;
        }

        /* Alumni Tag */
        .alumni-tag {
            display: inline-block;
            margin: 15px 0 10px 0;
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
            color: #1a1a1a;
            padding: 8px 24px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(247, 151, 30, 0.3);
        }

        /* Buttons */
        .action-btn {
            margin-top: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .btn-print {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.25);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(30, 60, 114, 0.35);
            background: #2a5298;
        }

        .back-link {
            text-decoration: none;
            color: #667eea;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s;
        }
        .back-link:hover {
            color: #1e3c72;
        }

        /* Print Styles */
        @media print {
            body { 
                background: white; 
                padding: 0;
                display: block;
            }
            .card-container { 
                box-shadow: none; 
                border: 1px solid #ccc;
                margin: 20px auto;
            }
            .btn-print, .back-link { display: none; }
            .watermark { opacity: 0.05; }
        }
        
        /* Mobile Responsive */
        @media (max-width: 480px) {
            .card-container { width: 100%; max-width: 400px; }
            .detail-badge { font-size: 12px; padding: 6px 12px; }
            .info-section h2 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Transparent Watermark -->
        <div class="watermark">ALUMNI</div>
        
        <!-- Glossy Header -->
        <div class="card-header">
            <i class="fas fa-user-graduate"></i>
            <h3>ALUMNI PORTAL</h3>
        </div>
        
        <!-- Photo -->
        <div class="photo-section">
            <img src="<?php echo $img_src; ?>" alt="Profile" class="profile-pic">
        </div>
        
        <!-- Details -->
        <div class="info-section">
            <h2><?php echo htmlspecialchars($row['full_name']); ?></h2>
            
            <div class="detail-badge">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?>
            </div>
            <div class="detail-badge">
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($row['degree_program']); ?>
            </div>
            <div class="detail-badge">
                <i class="fas fa-calendar-alt"></i> Class of <?php echo htmlspecialchars($row['graduation_year']); ?>
            </div>
            
            <?php if(!empty($row['job_title'])): ?>
            <div class="detail-badge" style="background: #eaf6ed; border-color: #c3e6cb;">
                <i class="fas fa-briefcase" style="color: #28a745;"></i> 
                <?php echo htmlspecialchars($row['job_title'] . ' at ' . $row['current_company']); ?>
            </div>
            <?php endif; ?>
            
            <div class="alumni-tag"><?php echo strtoupper($row['degree_program']); ?> ALUMNUS</div>
            
            <div class="action-btn">
                <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
                <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>