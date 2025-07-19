<?php
// Registration form and logic for students
require_once '../../config/db.php';

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    if ($name && $email && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $conn = $mysqli;
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = 'Email already registered.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $business_id = 1; // Default or get from context
                $branch_id = 1;   // Default or get from context
                $stmt2 = mysqli_prepare($conn, "INSERT INTO users (business_id, branch_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, 'iisss', $business_id, $branch_id, $name, $email, $hashed_password);
                if (mysqli_stmt_execute($stmt2)) {
                    $success = 'Registration successful! Redirecting to login...';
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                mysqli_stmt_close($stmt2);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card p-4 shadow" style="min-width: 350px;">
        <h3 class="mb-3 text-center">Register</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <div class="mt-3 text-center">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html> 