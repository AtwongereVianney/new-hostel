<?php
// Simple Bootstrap landing page
session_start();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMU Hostel Solutions</title>
    <!-- Use CDN for better look and feel if local assets are missing -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .hero-section {
            background: white;
            padding: 4rem 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .btn-custom {
            padding: 0.8rem 2rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-8 hero-section">
                <i class="bi bi-building-check text-primary mb-4" style="font-size: 4rem;"></i>
                <h1 class="display-4 fw-bold mb-3">MMU Hostel Solutions</h1>
                <p class="lead text-muted mb-5">Modernizing the hostel booking experience. Find, book, and manage your stay with ease.</p>
                
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="../modules/auth/login.php" class="btn btn-primary btn-lg btn-custom">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Login to System
                    </a>
                    <a href="../modules/dashboard/index.php" class="btn btn-outline-secondary btn-lg btn-custom">
                        <i class="bi bi-speedometer2 me-2"></i> Go to Dashboard
                    </a>
                </div>
                
                <div class="mt-5 pt-4 border-top">
                    <p class="text-muted small mb-0">Experience the modernized MMU Hostel Management System.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 