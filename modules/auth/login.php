<?php
// Login form and authentication logic
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if ($email && $password) {
        $stmt = mysqli_prepare($conn, "
            SELECT id, name, email, password, user_type, permissions_json
            FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $uname, $uemail, $hash, $utype, $permJson);
        $fetched = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($fetched && password_verify($password, $hash)) {
            $_SESSION['user_id'] = (int) $uid;
            $_SESSION['user_name'] = $uname;
            $_SESSION['user_email'] = $uemail;
            $_SESSION['user_type'] = $utype ?? 'student';
            $_SESSION['owner_permissions'] = auth_merge_owner_permissions($permJson);

            $type = $_SESSION['user_type'];
            if ($type === 'admin') {
                header('Location: ../dashboard/index.php');
                exit;
            }
            if ($type === 'hostel_owner') {
                header('Location: ../dashboard/owner_hostels.php');
                exit;
            }
            header('Location: ../dashboard/no_access.php');
            exit;
        }
        $error = 'Invalid email or password.';
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
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card p-4 shadow" style="min-width: 350px;">
        <h3 class="mb-3 text-center">Login</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <div class="mt-3 text-center">
            <a href="register.php">Don't have an account? Register</a>
        </div>
    </div>
</body>
</html>
