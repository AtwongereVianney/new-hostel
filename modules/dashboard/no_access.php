<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow p-4" style="max-width: 420px;">
        <h5 class="mb-2">No dashboard access</h5>
        <p class="text-muted mb-3">This area is for administrators and hostel owners. Student accounts use the public site.</p>
        <a href="../auth/logout.php" class="btn btn-primary">Sign out</a>
    </div>
</body>
</html>
