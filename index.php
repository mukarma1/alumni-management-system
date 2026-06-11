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

// Add Alumni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_alumni'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $degree_program = $_POST['degree_program'];
    $graduation_year = $_POST['graduation_year'];
    $current_company = $_POST['current_company'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    
    $conn->query("INSERT INTO alumni (full_name, email, degree_program, graduation_year, current_company, job_title) 
                  VALUES ('$full_name', '$email', '$degree_program', '$graduation_year', '$current_company', '$job_title')");
    header('Location: index.php');
    exit;
}

// Delete Alumni
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM alumni WHERE id = {$_GET['delete']}");
    header('Location: index.php');
    exit;
}

// Send WhatsApp
if (isset($_GET['whatsapp']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $result = $conn->query("SELECT * FROM alumni WHERE id = $id");
    $row = $result->fetch_assoc();
    $phone = $_GET['phone'] ?? '923001234567';
    $msg = "Dear " . $row['full_name'] . ", You are invited to Alumni Reunion on Dec 15, 2026!";
    header("Location: https://wa.me/$phone?text=" . urlencode($msg));
    exit;
}

// Get stats
$total = $conn->query("SELECT COUNT(*) FROM alumni")->fetch_row()[0];
$employed = $conn->query("SELECT COUNT(*) FROM alumni WHERE current_company != ''")->fetch_row()[0];
$degrees = $conn->query("SELECT COUNT(DISTINCT degree_program) FROM alumni")->fetch_row()[0];
$recent = $conn->query("SELECT COUNT(*) FROM alumni WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_row()[0];

// Get Alumni of the Month (random)
$alumniOfMonth = $conn->query("SELECT * FROM alumni ORDER BY RAND() LIMIT 1")->fetch_assoc();

$alumni = $conn->query("SELECT * FROM alumni ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✨ Smart Alumni Portal ✨</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            transition: all 0.3s;
        }
        body.dark-mode {
            background: #1a1a2e;
            color: #eee;
        }
        body.dark-mode .stat-card,
        body.dark-mode .chart-card,
        body.dark-mode .table-container,
        body.dark-mode .header,
        body.dark-mode .welcome-card,
        body.dark-mode .modal-content,
        body.dark-mode .achievement-card {
            background: #16213e;
            color: #eee;
        }
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 20px;
            transition: all 0.3s;
            z-index: 100;
        }
        body.dark-mode .sidebar {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 100%);
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 40px;
        }
        .nav-item {
            padding: 12px 20px;
            margin: 8px 0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        .nav-item a {
            color: white;
            text-decoration: none;
        }
        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            background: rgba(255,255,255,0.15);
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logout-btn a {
            color: white;
            text-decoration: none;
        }
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .dark-toggle, .clock-widget {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50px;
            cursor: pointer;
        }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
            cursor: pointer;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 20px;
        }
        .achievement-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            overflow-x: auto;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .btn-add, .btn-import {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f8f9fa; }
        .profile-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }
        .edit-btn, .delete-btn, .whatsapp-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 3px;
        }
        .edit-btn { background: #ffc107; }
        .delete-btn { background: #dc3545; color: white; }
        .whatsapp-btn { background: #25D366; color: white; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
        }
        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .close {
            float: right;
            font-size: 25px;
            cursor: pointer;
        }
        .photo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 10px auto;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 101;
            background: #1e3c72;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 70px 15px 15px 15px; }
            .mobile-menu-btn { display: block; }
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #999;
            margin-top: 30px;
        }
        .badge {
            display: inline-block;
            background: gold;
            color: #333;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    
    <div class="sidebar">
        <h2><i class="fas fa-graduation-cap"></i> Alumni Portal</h2>
        <div class="nav-item active"><i class="fas fa-tachometer-alt"></i><a href="#">Dashboard</a></div>
        <div class="nav-item" onclick="openModal()"><i class="fas fa-user-plus"></i><a href="#">Add Alumni</a></div>
        <div class="nav-item" onclick="openImportModal()"><i class="fas fa-file-upload"></i><a href="#">Bulk Import</a></div>
        <div class="logout-btn"><i class="fas fa-sign-out-alt"></i><a href="auth/logout.php">Logout</a></div>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> Smart Alumni Dashboard</h1>
            <div style="display: flex; gap: 10px;">
                <div class="clock-widget" id="clock"><i class="far fa-clock"></i> Loading...</div>
                <button class="dark-toggle" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </div>
        </div>
        
        <div class="welcome-card">
            <div>
                <h2><i class="fas fa-chalkboard-user"></i> Alumni Management System</h2>
                <p>Connecting past, present & future</p>
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
        
        <div class="charts-row">
            <div class="chart-card"><h3>Alumni by Degree</h3><canvas id="degreeChart"></canvas></div>
            <div class="chart-card"><h3>Alumni by Year</h3><canvas id="yearChart"></canvas></div>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list"></i> Alumni Records</h3>
                <div>
                    <button class="btn-add" onclick="openModal()"><i class="fas fa-plus"></i> Add</button>
                    <button class="btn-add" onclick="exportToExcel()"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            <div class="search-box"><input type="text" id="searchInput" placeholder="🔍 Search alumni..." onkeyup="searchTable()"></div>
            
            <table id="alumniTable">
                <thead><tr><th>Photo</th><th>Name</th><th>Email</th><th>Degree</th><th>Year</th><th>Placement</th><th>Actions</th><th>Connect</th></tr></thead>
                <tbody>
                    <?php while($row = $alumni->fetch_assoc()): ?>
                    <tr>
                        <td>
    <?php if(!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])): ?>
        <img src="uploads/<?php echo $row['profile_pic']; ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
    <?php else: ?>
        <i class="fas fa-user-circle" style="font-size: 40px; color: #667eea;"></i>
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
                            <button class="whatsapp-btn" onclick="sendWhatsApp(<?php echo $row['id']; ?>)"><i class="fab fa-whatsapp"></i></button>
                            <button class="whatsapp-btn" style="background:#6c5ce7;" onclick="downloadCard(<?php echo $row['id']; ?>)"><i class="fas fa-id-card"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="footer">© 2026 Department of Computer Science | ✨ Smart Alumni System with AI Features ✨</div>
    </div>
    
    <!-- Add Modal -->
    <div id="alumniModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-user-graduate"></i> Add Alumni</h2>
            <form method="POST">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="degree_program" placeholder="Degree" required>
                <input type="number" name="graduation_year" placeholder="Year" required>
                <input type="text" name="current_company" placeholder="Company">
                <input type="text" name="job_title" placeholder="Job Title">
                <div class="photo-preview" id="photoPreview"><i class="fas fa-user fa-3x"></i></div>
                <input type="file" id="profilePic" accept="image/*" style="margin: 10px 0;">
                <input type="hidden" id="alumniId">
                <button type="submit" name="add_alumni">Save Alumni</button>
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
        
        function openModal() { document.getElementById('alumniModal').style.display = 'flex'; }
        function closeModal() { document.getElementById('alumniModal').style.display = 'none'; }
        
        function editAlumni(id) { window.location.href = 'edit.php?id=' + id; }
        function deleteAlumni(id) { if(confirm('Delete?')) window.location.href = '?delete=' + id; }
        function sendWhatsApp(id) { window.location.href = '?whatsapp=1&id=' + id; }
        function downloadCard(id) { window.open('id_card.php?id=' + id, '_blank'); }
        
        function searchTable() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toLowerCase();
            let rows = document.querySelectorAll('#alumniTable tbody tr');
            rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
        }
        
        function exportToExcel() {
            let table = document.getElementById('alumniTable');
            let html = table.outerHTML;
            let link = document.createElement('a');
            link.href = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            link.download = 'alumni_data.xls';
            link.click();
        }
        
        // Real-time clock
        function updateClock() {
            let now = new Date();
            document.getElementById('clock').innerHTML = '<i class="far fa-clock"></i> ' + now.toLocaleString();
        }
        setInterval(updateClock, 1000);
        updateClock();
        
        // Profile picture upload
        document.getElementById('profilePic')?.addEventListener('change', function(e) {
            let file = e.target.files[0];
            if(file) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('photoPreview').innerHTML = '<img src="'+event.target.result+'" style="width:100%; height:100%; object-fit:cover;">';
                };
                reader.readAsDataURL(file);
                
                let alumniId = document.getElementById('alumniId').value;
                if(alumniId) {
                    let formData = new FormData();
                    formData.append('profile_pic', file);
                    formData.append('alumni_id', alumniId);
                    fetch('upload_pic.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => { if(data.success) alert('Photo uploaded!'); });
                }
            }
        });
        
        window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; }
        
        // Charts
        <?php
        $degreeData = $conn->query("SELECT degree_program, COUNT(*) as c FROM alumni GROUP BY degree_program")->fetch_all();
        $yearData = $conn->query("SELECT graduation_year, COUNT(*) as c FROM alumni GROUP BY graduation_year ORDER BY graduation_year")->fetch_all();
        $dLabels = []; $dCounts = [];
        foreach($degreeData as $d) { $dLabels[] = $d[0]; $dCounts[] = $d[1]; }
        $yLabels = []; $yCounts = [];
        foreach($yearData as $y) { $yLabels[] = $y[0]; $yCounts[] = $y[1]; }
        ?>
        new Chart(document.getElementById('degreeChart'), {
            type: 'bar',
            data: { labels: <?php echo json_encode($dLabels); ?>, datasets: [{ label: 'Alumni', data: <?php echo json_encode($dCounts); ?>, backgroundColor: '#2a5298' }] }
        });
        new Chart(document.getElementById('yearChart'), {
            type: 'line',
            data: { labels: <?php echo json_encode($yLabels); ?>, datasets: [{ label: 'Alumni', data: <?php echo json_encode($yCounts); ?>, borderColor: '#1e3c72', fill: true }] }
        });
    </script>
</body>
</html>