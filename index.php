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

// ==========================================
// 1. HANDLE POST REQUESTS
// ==========================================

// Add Alumni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_alumni'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $degree_program = $_POST['degree_program'];
    $graduation_year = $_POST['graduation_year'];
    $current_company = $_POST['current_company'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    
    $conn->query("INSERT INTO alumni (full_name, email, degree_program, graduation_year, current_company, job_title, created_at) 
                  VALUES ('$full_name', '$email', '$degree_program', '$graduation_year', '$current_company', '$job_title', '$created_at')");
    $new_id = $conn->insert_id;
    
    // GENERATE NOTIFICATION FOR THIS NEW ALUMNI
    $conn->query("INSERT INTO notifications (type, target_id, message) 
                  VALUES ('new_alumni', $new_id, 'New Alumni Registered: <b>$full_name</b> ($degree_program)')");
                  
    header('Location: index.php');
    exit;
}

// Post a Success Story
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_story'])) {
    $alumni_id = $_POST['alumni_id'];
    $story_text = mysqli_real_escape_string($conn, $_POST['story_text']);
    
    // Get Alumni Name for notification
    $al_q = $conn->query("SELECT full_name FROM alumni WHERE id = $alumni_id");
    $al_row = $al_q->fetch_assoc();
    $name = $al_row['full_name'];
    
    $conn->query("INSERT INTO alumni_stories (alumni_id, story_text) VALUES ('$alumni_id', '$story_text')");
    $new_story_id = $conn->insert_id;
    
    // GENERATE NOTIFICATION FOR THIS NEW STORY
    $conn->query("INSERT INTO notifications (type, target_id, message) 
                  VALUES ('new_story', $new_story_id, '<b>$name</b> shared a new success story!')");
                  
    header('Location: index.php');
    exit;
}

// Delete Alumni
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM alumni WHERE id = {$_GET['delete']}");
    header('Location: index.php');
    exit;
}

// ==========================================
// 2. ADVANCED FILTER LOGIC
// ==========================================
$where_clause = "WHERE 1=1";

if (isset($_GET['filter_degree']) && !empty($_GET['filter_degree'])) {
    $deg = mysqli_real_escape_string($conn, $_GET['filter_degree']);
    $where_clause .= " AND degree_program = '$deg'";
}
if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $yr = mysqli_real_escape_string($conn, $_GET['filter_year']);
    $where_clause .= " AND graduation_year = '$yr'";
}
if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search_query']);
    $where_clause .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR current_company LIKE '%$search%')";
}

// ==========================================
// 3. GET STATS & DATA
// ==========================================
$total = $conn->query("SELECT COUNT(*) FROM alumni")->fetch_row()[0];
$employed = $conn->query("SELECT COUNT(*) FROM alumni WHERE current_company != ''")->fetch_row()[0];
$degrees = $conn->query("SELECT COUNT(DISTINCT degree_program) FROM alumni")->fetch_row()[0];
$recent = $conn->query("SELECT COUNT(*) FROM alumni WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_row()[0];
$unemployed = $total - $employed;

// Smart Alumni of the Month
$alumniOfMonth = $conn->query("SELECT * FROM alumni ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

// Get Filtered Alumni
$alumni_query = "SELECT * FROM alumni $where_clause ORDER BY id DESC";
$alumni = $conn->query($alumni_query);

// Get Success Stories
$stories_query = "SELECT s.story_text, s.created_at, a.full_name, a.profile_pic 
                  FROM alumni_stories s 
                  JOIN alumni a ON s.alumni_id = a.id 
                  ORDER BY s.created_at DESC LIMIT 5";
$stories = $conn->query($stories_query);

// ==========================================
// 4. GET NOTIFICATION DATA (NEW LOGIC)
// ==========================================
$notif_query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10";
$notif_result = $conn->query($notif_query);
$notifications = [];
$unread_count = 0;

while($n = $notif_result->fetch_assoc()) {
    if($n['is_read'] == 0) $unread_count++;
    $notifications[] = $n;
}

// ==========================================
// 5. CHARTS DATA
// ==========================================
$degreeData = $conn->query("SELECT degree_program, COUNT(*) as c FROM alumni GROUP BY degree_program")->fetch_all();
$yearData = $conn->query("SELECT graduation_year, COUNT(*) as c FROM alumni GROUP BY graduation_year ORDER BY graduation_year")->fetch_all();

$dLabels = []; $dCounts = [];
foreach($degreeData as $d) { $dLabels[] = $d[0]; $dCounts[] = $d[1]; }
$yLabels = []; $yCounts = [];
foreach($yearData as $y) { $yLabels[] = $y[0]; $yCounts[] = $y[1]; }

$alumni_list = $conn->query("SELECT id, full_name FROM alumni ORDER BY full_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Enterprise Alumni Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; transition: all 0.3s; }
        body.dark-mode { background: #1a1a2e; color: #eee; }
        body.dark-mode .stat-card, body.dark-mode .chart-card, body.dark-mode .table-container, 
        body.dark-mode .header, body.dark-mode .welcome-card, body.dark-mode .modal-content, 
        body.dark-mode .achievement-card, body.dark-mode .filter-card, body.dark-mode .story-card,
        body.dark-mode .notif-dropdown { background: #16213e; color: #eee; }
        
        /* Sidebar */
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100%; background: #1e3c72; color: white; padding: 30px 20px; transition: all 0.3s; z-index: 100; }
        body.dark-mode .sidebar { background: #0f0f23; }
        .sidebar h2 { text-align: center; margin-bottom: 40px; }
        .nav-item { padding: 12px 20px; margin: 8px 0; border-radius: 12px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 12px; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.2); transform: translateX(5px); }
        .nav-item a { color: white; text-decoration: none; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; }
        .logout-btn a { color: white; text-decoration: none; }
        
        /* Layout */
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px 30px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .dark-toggle, .clock-widget { background: #1e3c72; color: white; border: none; padding: 10px 15px; border-radius: 50px; cursor: pointer; }
        
        /* Notifications */
        .notif-wrapper { position: relative; display: inline-block; }
        .notif-btn { background: #dc3545; color: white; border: none; padding: 10px 18px; border-radius: 50px; cursor: pointer; transition: 0.2s; }
        .notif-btn:hover { transform: scale(1.05); }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: #ffc107; color: black; border-radius: 50%; padding: 2px 8px; font-size: 11px; font-weight: bold; border: 2px solid white; }
        .notif-dropdown { display: none; position: absolute; right: 0; top: 55px; width: 350px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); padding: 0; overflow: hidden; z-index: 200; }
        .notif-dropdown.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from {opacity:0; transform: translateY(-10px);} to {opacity:1; transform: translateY(0);} }
        .notif-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; font-weight: 600; }
        body.dark-mode .notif-header { background: #0f0f23; border-bottom: 1px solid #333; }
        .notif-item { padding: 12px 20px; border-bottom: 1px solid #f0f0f0; display: flex; gap: 15px; align-items: flex-start; transition: 0.2s; cursor: pointer; }
        .notif-item:hover { background: #f9f9f9; }
        body.dark-mode .notif-item:hover { background: #1a1a3e; }
        .notif-item.read { background: #fcfcfc; color: #888; }
        body.dark-mode .notif-item.read { background: #12122a; color: #888; }
        .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
        .notif-text { font-size: 14px; line-height: 1.4; }
        .notif-text b { color: #1e3c72; }
        body.dark-mode .notif-text b { color: #667eea; }
        .notif-time { display: block; font-size: 11px; color: #999; margin-top: 3px; }
        .notif-footer { padding: 12px; text-align: center; background: #f8f9fa; font-size: 13px; color: #667eea; cursor: pointer; }
        body.dark-mode .notif-footer { background: #0f0f23; }
        .dot-unread { display: inline-block; width: 8px; height: 8px; background: #dc3545; border-radius: 50%; margin-right: 5px; }

        /* Welcome & Stats */
        .welcome-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.3s; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stat-card:hover { transform: translateY(-5px); }
        
        /* Charts */
        .charts-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .chart-card { background: white; padding: 20px; border-radius: 20px; }
        .achievement-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        
        .story-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; border-left: 5px solid #667eea; }
        .story-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .story-time { font-size: 12px; color: #999; }
        
        .filter-card { background: white; border-radius: 15px; padding: 15px 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .filter-card select, .filter-card input { padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px; background: white; outline: none; }
        .table-container { background: white; border-radius: 20px; padding: 20px; overflow-x: auto; }
        .table-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .btn-add, .btn-import { background: #1e3c72; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block;}
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; font-weight: 600; color: #555; }
        body.dark-mode th { background: #1a1a3e; color: #ccc; }
        .profile-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .edit-btn, .delete-btn, .whatsapp-btn, .email-btn, .idcard-btn { padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; margin: 2px; font-size: 12px;}
        .edit-btn { background: #ffc107; color: #333; }
        .delete-btn { background: #dc3545; color: white; }
        .whatsapp-btn { background: #25D366; color: white; }
        .email-btn { background: #007bff; color: white; }
        .idcard-btn { background: #6c5ce7; color: white; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; }
        .modal-content input, .modal-content select, .modal-content textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .close { float: right; font-size: 25px; cursor: pointer; }
        
        .mobile-menu-btn { display: none; position: fixed; top: 15px; left: 15px; z-index: 101; background: #1e3c72; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 250px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 70px 15px 15px 15px; }
            .mobile-menu-btn { display: block; }
            .charts-row { grid-template-columns: 1fr; }
            .notif-dropdown { width: 300px; right: -50px; }
        }
        .footer { text-align: center; padding: 20px; color: #999; margin-top: 30px; }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    
    <div class="sidebar">
        <h2><i class="fas fa-graduation-cap"></i> Alumni Portal</h2>
        <div class="nav-item active"><i class="fas fa-tachometer-alt"></i><a href="#">Dashboard</a></div>
        <div class="nav-item" onclick="openModal('addAlumniModal')"><i class="fas fa-user-plus"></i><a href="#">Add Alumni</a></div>
        <div class="nav-item" onclick="openModal('addStoryModal')"><i class="fas fa-pen-fancy"></i><a href="#">Post Story</a></div>
        <div class="nav-item"><a href="send_bulk_email.php" target="_blank"><i class="fas fa-envelope"></i> Bulk Email</a></div>
        <div class="logout-btn"><i class="fas fa-sign-out-alt"></i><a href="auth/logout.php">Logout</a></div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Enterprise Alumni Hub</h1>
            <div style="display: flex; gap: 10px; align-items: center;">
                
                <!-- CLICKABLE NOTIFICATIONS -->
                <div class="notif-wrapper">
                    <button class="notif-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i> 
                        <span class="notif-badge"><?php echo $unread_count; ?></span>
                    </button>
                    
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span>Notifications</span>
                            <span style="color:#999; font-size:12px;"><?php echo $unread_count; ?> Unread</span>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if(count($notifications) > 0): ?>
                                <?php foreach($notifications as $notif): ?>
                                    <a href="mark_notification_read.php?id=<?php echo $notif['id']; ?>" style="text-decoration: none; color: inherit;">
                                        <div class="notif-item <?php echo ($notif['is_read'] == 1) ? 'read' : ''; ?>">
                                            <div class="notif-icon" style="background: <?php echo ($notif['type'] == 'new_alumni') ? '#2a5298' : '#11998e'; ?>;">
                                                <i class="fas <?php echo ($notif['type'] == 'new_alumni') ? 'fa-user-plus' : 'fa-pen-fancy'; ?>"></i>
                                            </div>
                                            <div class="notif-text">
                                                <?php if($notif['is_read'] == 0): ?><span class="dot-unread"></span><?php endif; ?>
                                                <?php echo $notif['message']; ?>
                                                <span class="notif-time"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 20px; text-align: center; color: #999;">
                                    <i class="fas fa-check-circle" style="font-size: 24px; color: #11998e;"></i>
                                    <p style="margin-top: 10px;">All caught up!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="notif-footer" onclick="toggleNotifications()">Mark all as read</div>
                    </div>
                </div>

                <div class="clock-widget" id="clock"><i class="far fa-clock"></i> Loading...</div>
                <button class="dark-toggle" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
                <div>Welcome, <?php echo $_SESSION['username']; ?></div>
            </div>
        </div>
        
        <div class="welcome-card">
            <div>
                <h2><i class="fas fa-chalkboard-user"></i> Alumni Management System</h2>
                <p>Connecting past, present & future with AI Insights</p>
            </div>
            <div><i class="fas fa-users fa-4x"></i></div>
        </div>
        
        <div class="stats">
            <div class="stat-card"><div><h3>Total Alumni</h3><div style="font-size: 32px; font-weight: bold;"><?php echo $total; ?></div></div><i class="fas fa-users fa-3x"></i></div>
            <div class="stat-card"><div><h3>Employed</h3><div style="font-size: 32px; font-weight: bold;"><?php echo $employed; ?></div></div><i class="fas fa-briefcase fa-3x"></i></div>
            <div class="stat-card"><div><h3>Programs</h3><div style="font-size: 32px; font-weight: bold;"><?php echo $degrees; ?></div></div><i class="fas fa-graduation-cap fa-3x"></i></div>
            <div class="stat-card"><div><h3>New This Month</h3><div style="font-size: 32px; font-weight: bold;"><?php echo $recent; ?></div></div><i class="fas fa-calendar-alt fa-3x"></i></div>
        </div>
        
        <?php if($alumniOfMonth): ?>
        <div class="achievement-card">
            <div>
                <h3><i class="fas fa-trophy"></i> 🌟 Alumni of the Month</h3>
                <p><strong><?php echo $alumniOfMonth['full_name']; ?></strong> - <?php echo $alumniOfMonth['degree_program']; ?> (<?php echo $alumniOfMonth['graduation_year']; ?>)</p>
                <p><i class="fas fa-briefcase"></i> <?php echo $alumniOfMonth['job_title'] . ' at ' . $alumniOfMonth['current_company']; ?></p>
            </div>
            <div><i class="fas fa-crown fa-4x"></i></div>
        </div>
        <?php endif; ?>
        
        <!-- 3 GRAPHS ROW -->
        <div class="charts-row">
            <div class="chart-card"><h3>Alumni by Degree</h3><canvas id="degreeChart"></canvas></div>
            <div class="chart-card"><h3>Enrollment (Yearly)</h3><canvas id="yearChart"></canvas></div>
            <div class="chart-card"><h3>Employment Status %</h3><canvas id="employmentChart"></canvas></div>
        </div>

        <!-- STORIES -->
        <div id="stories_section" class="achievement-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); margin-bottom: 20px;">
            <div>
                <h3><i class="fas fa-star"></i> Recent Alumni Success Stories</h3>
                <p>Celebrating achievements of our graduates!</p>
            </div>
            <button class="btn-add" style="background: white; color: #11998e;" onclick="openModal('addStoryModal')"><i class="fas fa-plus"></i> Add Story</button>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <?php if ($stories->num_rows > 0): ?>
                <?php while($story = $stories->fetch_assoc()): ?>
                <div class="story-card">
                    <div class="story-header">
                        <div style="width:35px; height:35px; background:#667eea; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">
                            <?php echo strtoupper(substr($story['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 style="margin:0;"><?php echo $story['full_name']; ?></h5>
                            <span class="story-time"><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($story['created_at'])); ?></span>
                        </div>
                    </div>
                    <p style="margin: 10px 0 0 0; font-style: italic; color: #555; border-left: 3px solid #11998e; padding-left: 10px;">
                        "<?php echo htmlspecialchars($story['story_text']); ?>"
                    </p>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="story-card" style="grid-column: 1/-1; text-align:center; color:#999; border-left-color: #ddd;">
                    No success stories shared yet. Be the first to inspire!
                </div>
            <?php endif; ?>
        </div>
        
        <!-- TABLE -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Alumni Records</h3>
                <div>
                    <button class="btn-add" onclick="openModal('addAlumniModal')"><i class="fas fa-plus"></i> Add</button>
                    <button class="btn-add" onclick="exportToExcel()"><i class="fas fa-download"></i> Export</button>
                    <a href="send_bulk_email.php" target="_blank" class="btn-add" style="background: #dc3545;"><i class="fas fa-envelope"></i> Bulk Email</a>
                </div>
            </div>
            
            <div class="filter-card">
                <i class="fas fa-filter" style="color:#667eea;"></i>
                <span style="font-weight: 500; margin-right: 10px;">Filter By:</span>
                
                <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 10px; width: 100%;">
                    <input type="text" name="search_query" placeholder="Search Name, Email..." value="<?php echo isset($_GET['search_query']) ? $_GET['search_query'] : ''; ?>" style="flex:1; min-width: 150px;">
                    
                    <select name="filter_degree">
                        <option value="">All Degrees</option>
                        <option value="AI" <?php if(isset($_GET['filter_degree']) && $_GET['filter_degree'] == 'AI') echo 'selected'; ?>>AI</option>
                        <option value="BBA" <?php if(isset($_GET['filter_degree']) && $_GET['filter_degree'] == 'BBA') echo 'selected'; ?>>BBA</option>
                        <option value="BSCS" <?php if(isset($_GET['filter_degree']) && $_GET['filter_degree'] == 'BSCS') echo 'selected'; ?>>BSCS</option>
                    </select>
                    
                    <select name="filter_year">
                        <option value="">All Years</option>
                        <option value="2024" <?php if(isset($_GET['filter_year']) && $_GET['filter_year'] == '2024') echo 'selected'; ?>>2024</option>
                        <option value="2025" <?php if(isset($_GET['filter_year']) && $_GET['filter_year'] == '2025') echo 'selected'; ?>>2025</option>
                        <option value="2026" <?php if(isset($_GET['filter_year']) && $_GET['filter_year'] == '2026') echo 'selected'; ?>>2026</option>
                        <option value="2027" <?php if(isset($_GET['filter_year']) && $_GET['filter_year'] == '2027') echo 'selected'; ?>>2027</option>
                    </select>
                    
                    <button type="submit" class="btn-add" style="padding: 8px 15px;"><i class="fas fa-search"></i> Apply</button>
                    <a href="index.php" style="text-decoration: none; padding: 8px 15px; background: #eee; border-radius: 8px; color: #333;">Reset</a>
                </form>
            </div>
            
            <table id="alumniTable">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Degree</th>
                        <th>Year</th>
                        <th>Placement</th>
                        <th>Actions</th>
                        <th>Connect</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alumni->num_rows > 0): ?>
                        <?php while($row = $alumni->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if(!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])): ?>
                                    <img src="uploads/<?php echo $row['profile_pic']; ?>" class="profile-img">
                                <?php else: ?>
                                    <div style="width:45px; height:45px; background:#667eea; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['degree_program']; ?></td>
                            <td><?php echo $row['graduation_year']; ?></td>
                            <td><?php echo $row['job_title'] . ' at ' . $row['current_company']; ?></td>
                            <td>
                                <button class="edit-btn" onclick="editAlumni(<?php echo $row['id']; ?>)">Edit</button>
                                <button class="delete-btn" onclick="deleteAlumni(<?php echo $row['id']; ?>)">Del</button>
                            </td>
                            <td>
                                <a href="https://wa.me/?text=Hi%20<?php echo urlencode($row['full_name']); ?>%20from%20Alumni%20Portal!" target="_blank">
                                    <button class="whatsapp-btn"><i class="fab fa-whatsapp"></i></button>
                                </a>
                                <a href="mailto:<?php echo $row['email']; ?>">
                                    <button class="email-btn"><i class="fas fa-envelope"></i></button>
                                </a>
                                
                                <!-- ✅ NEW ID CARD BUTTON ADDED HERE -->
                                <a href="id_card.php?id=<?php echo $row['id']; ?>" target="_blank">
                                    <button class="idcard-btn"><i class="fas fa-id-card"></i> Card</button>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding: 20px; color:#999;">No alumni records found matching your filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="footer">© 2026 Department of Computer Science | 🚀 Enterprise Alumni System</div>
    </div>
    
    <!-- Add Alumni Modal -->
    <div id="addAlumniModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addAlumniModal')">&times;</span>
            <h2><i class="fas fa-user-graduate"></i> Add Alumni</h2>
            <form method="POST">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="degree_program" placeholder="Degree (e.g., AI, BBA)" required>
                <input type="number" name="graduation_year" placeholder="Year" required>
                <input type="text" name="current_company" placeholder="Company">
                <input type="text" name="job_title" placeholder="Job Title">
                <button type="submit" name="add_alumni" style="width:100%; background:#1e3c72; color:white; border:none; padding:12px; border-radius:8px; font-size:16px; margin-top:10px;">Save Alumni</button>
            </form>
        </div>
    </div>

    <!-- Add Story Modal -->
    <div id="addStoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addStoryModal')">&times;</span>
            <h2><i class="fas fa-pen-fancy"></i> Share Success Story</h2>
            <form method="POST">
                <label>Select Alumni:</label>
                <select name="alumni_id" required>
                    <option value="">-- Select Alumni --</option>
                    <?php while($al = $alumni_list->fetch_assoc()): ?>
                        <option value="<?php echo $al['id']; ?>"><?php echo $al['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
                <label>Write Story:</label>
                <textarea name="story_text" rows="4" placeholder="e.g. I got a job at Google today!" required></textarea>
                <button type="submit" name="post_story" style="width:100%; background:#11998e; color:white; border:none; padding:12px; border-radius:8px; font-size:16px; margin-top:10px;">Publish Story</button>
            </form>
        </div>
    </div>
    
    <script>
        function toggleSidebar() { document.querySelector('.sidebar').classList.toggle('open'); }
        
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }
        if(localStorage.getItem('darkMode') === 'true') document.body.classList.add('dark-mode');

        document.addEventListener('keydown', function(event) { if (event.key === 'd' || event.key === 'D') toggleDarkMode(); });
        
        function toggleNotifications() {
            document.getElementById('notifDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const wrapper = document.querySelector('.notif-wrapper');
            const dropdown = document.getElementById('notifDropdown');
            if (!wrapper.contains(event.target) && dropdown.classList.contains('show')) dropdown.classList.remove('show');
        });
        
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        
        function editAlumni(id) { window.location.href = 'edit.php?id=' + id; }
        function deleteAlumni(id) { if(confirm('Are you sure you want to delete this alumni?')) window.location.href = '?delete=' + id; }
        
        function exportToExcel() {
            let table = document.getElementById('alumniTable');
            let html = table.outerHTML;
            let link = document.createElement('a');
            link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            link.download = 'alumni_data.xls';
            link.click();
        }
        
        function updateClock() {
            let now = new Date();
            document.getElementById('clock').innerHTML = '<i class="far fa-clock"></i> ' + now.toLocaleString();
        }
        setInterval(updateClock, 1000); updateClock();
        
        window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; }
        
        // CHARTS
        new Chart(document.getElementById('degreeChart'), {
            type: 'bar', data: { labels: <?php echo json_encode($dLabels); ?>, datasets: [{ label: 'Alumni', data: <?php echo json_encode($dCounts); ?>, backgroundColor: '#2a5298', borderRadius: 5 }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        new Chart(document.getElementById('yearChart'), {
            type: 'line', data: { labels: <?php echo json_encode($yLabels); ?>, datasets: [{ label: 'Enrollment', data: <?php echo json_encode($yCounts); ?>, borderColor: '#f093fb', backgroundColor: 'rgba(240, 147, 251, 0.1)', fill: true, tension: 0.4 }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        new Chart(document.getElementById('employmentChart'), {
            type: 'pie', data: { labels: ['Employed', 'Unemployed'], datasets: [{ data: [<?php echo $employed; ?>, <?php echo $unemployed; ?>], backgroundColor: ['#38ef7d', '#f5576c'], borderWidth: 0 }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>
</html>