<?php
// flash.php - Simple flash/redirect page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2;url=modules/auth/login.php">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="text-center">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h3 class="mb-2">Redirecting to Login...</h3>
        <p class="text-muted">You will be redirected shortly. If not, <a href="modules/auth/login.php">click here</a>.</p>
    </div>
</body>
</html> 