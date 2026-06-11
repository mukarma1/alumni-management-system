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

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM alumni WHERE id = $id");
$row = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $degree_program = $_POST['degree_program'];
    $graduation_year = $_POST['graduation_year'];
    $current_company = $_POST['current_company'];
    $job_title = $_POST['job_title'];
    
    $conn->query("UPDATE alumni SET 
        full_name = '$full_name',
        email = '$email',
        degree_program = '$degree_program',
        graduation_year = '$graduation_year',
        current_company = '$current_company',
        job_title = '$job_title'
        WHERE id = $id");
    
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Alumni</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 25px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        h2 {
            margin-bottom: 25px;
            color: #1e3c72;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
        }
        input:focus {
            outline: none;
            border-color: #2a5298;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 15px;
        }
        .photo-box {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 20px;
        }
        .current-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: block;
            border: 3px solid #2a5298;
        }
        .upload-btn {
            background: #28a745;
            margin-top: 10px;
            width: auto;
            padding: 8px 20px;
        }
        .back {
            text-align: center;
            margin-top: 20px;
        }
        .back a {
            color: #2a5298;
            text-decoration: none;
        }
        .msg {
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            display: none;
        }
        .msg.show {
            display: block;
        }
        .msg.success {
            background: #d4edda;
            color: #155724;
        }
        .msg.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-edit"></i> Edit Alumni</h2>
        
        <div class="photo-box">
            <h4><i class="fas fa-camera"></i> Profile Photo</h4>
            <?php if(!empty($row['profile_pic']) && file_exists('uploads/' . $row['profile_pic'])): ?>
                <img src="uploads/<?php echo $row['profile_pic']; ?>" class="current-photo" id="currentPhoto">
            <?php else: ?>
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle fill='%23ddd' cx='50' cy='50' r='50'/%3E%3Ctext x='50' y='65' text-anchor='middle' fill='%23999' font-size='40'%3E👤%3C/text%3E%3C/svg%3E" class="current-photo" id="currentPhoto">
            <?php endif; ?>
            <input type="file" id="photoInput" accept="image/*" style="display: none;">
            <button type="button" class="upload-btn" onclick="document.getElementById('photoInput').click()">
                <i class="fas fa-upload"></i> Change Photo
            </button>
            <small style="display: block; margin-top: 5px;">Max 2MB (JPG, PNG)</small>
            <div id="uploadMsg" class="msg"></div>
        </div>
        
        <form method="POST">
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($row['full_name']); ?>" required>
            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
            <input type="text" name="degree_program" value="<?php echo htmlspecialchars($row['degree_program']); ?>" required>
            <input type="number" name="graduation_year" value="<?php echo $row['graduation_year']; ?>" required>
            <input type="text" name="current_company" value="<?php echo htmlspecialchars($row['current_company']); ?>" placeholder="Current Company">
            <input type="text" name="job_title" value="<?php echo htmlspecialchars($row['job_title']); ?>" placeholder="Job Title">
            <button type="submit">💾 Update Alumni</button>
        </form>
        
        <div class="back">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowed.includes(file.type)) {
                showMsg('Only JPG, PNG, GIF allowed', 'error');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) {
                showMsg('File must be less than 2MB', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('currentPhoto').src = event.target.result;
            };
            reader.readAsDataURL(file);
            
            const alumniId = <?php echo $id; ?>;
            const formData = new FormData();
            formData.append('profile_pic', file);
            formData.append('alumni_id', alumniId);
            
            fetch('upload_pic.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMsg('Photo uploaded successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMsg(data.error, 'error');
                }
            })
            .catch(() => showMsg('Upload failed', 'error'));
        });
        
        function showMsg(msg, type) {
            const div = document.getElementById('uploadMsg');
            div.innerHTML = msg;
            div.className = 'msg show ' + type;
            setTimeout(() => div.className = 'msg', 3000);
        }
    </script>
</body>
</html>