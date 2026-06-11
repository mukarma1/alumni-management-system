<?php
require_once 'config/database.php';
session_start();
if (!isset($_SESSION['user_id'])) 
{
    header('Location: auth/login.php');
    exit;
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $full_name = trim($_POST['full_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $degree_program = trim($_POST['degree_program']);
    $graduation_year = filter_var(trim($_POST['graduation_year']),
    FILTER_VALIDATE_INT);
    $current_company = trim($_POST['current_company']) ?: 'N/A';
    $job_title = trim($_POST['job_title']) ?: 'N/A';
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is
required.";
    if (!$graduation_year || $graduation_year < 1950 || $graduation_year > date('Y')
+2) {
        $errors[] = "Please enter a valid graduation year.";
    }
    if (empty($errors)) 
    {
    try {
        $sql = "INSERT INTO alumni (user_id, full_name, graduation_year,
       degree_program, current_company, job_title, email)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $full_name, $graduation_year,
       $degree_program, $current_company, $job_title, $email]);
        header('Location: index.php');
        exit;
        } catch (\PDOException $e) 
        {
            if ($e->getCode() == 23000) 
            {
                $errors[] = "This email profile already exists.";
            } 
            else 
            {
                $errors[] = "Database transaction fault occurred.";
            }
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<h2>Add New Alumni Record</h2>
<?php if(!empty($errors)): ?>
 <div style="color:red;">
 <ul><?php foreach($errors as $error) echo "<li>$error</li>"; ?></ul>
 </div>
<?php endif; ?>
<form action="create.php" method="POST">
 <label>Full Name *</label><input type="text" name="full_name" required>
 <label>Email Address *</label><input type="email" name="email" required>
 <label>Degree Program *</label><input type="text" name="degree_program" required>
 <label>Graduation Year *</label><input type="number" name="graduation_year"
required>
 <label>Current Company</label><input type="text" name="current_company">
 <label>Job Title</label><input type="text" name="job_title">
 <button type="submit">Save Profile</button>
 </form>
 <?php include 'includes/footer.php'; ?>
 