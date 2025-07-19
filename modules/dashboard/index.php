<?php
// dashboard/index.php
session_start();
// Optionally, check if user is logged in
// if (!isset($_SESSION['user_id'])) { header('Location: ../modules/auth/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background: #f8f9fa;
            min-height: 100vh;
            overflow: hidden;
        }
        .sidebar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            overflow-y: auto;
            z-index: 1030;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .dashboard-content {
            margin-left: 250px;
            height: 100vh;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        @media (max-width: 991.98px) {
            .sidebar-fixed {
                display: none;
            }
            .dashboard-content {
                margin-left: 0;
                padding-top: 4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar (fixed and scrollable on desktop) -->
    <div class="sidebar-fixed d-none d-lg-block">
        <?php include '../../includes/sidebar.php'; ?>
    </div>
    <!-- Top Navbar for mobile -->
    <nav class="navbar navbar-light bg-light d-lg-none fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">Hostel System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <!-- Offcanvas Sidebar for mobile -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <?php include '../../includes/sidebar.php'; ?>
        </div>
    </div>
    <!-- Main Content (scrolls independently from sidebar) -->
    <div class="dashboard-content">
        <h2 class="mb-4">Welcome to your Dashboard</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-building"></i> Hostels</h5>
                        <p class="card-text">Manage and view all hostels.</p>
                        <a href="#" class="btn btn-light btn-sm">View Hostels</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-door-closed"></i> Rooms</h5>
                        <p class="card-text">Check room status and availability.</p>
                        <a href="#" class="btn btn-light btn-sm">View Rooms</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-calendar-check"></i> Bookings</h5>
                        <p class="card-text">View and manage bookings.</p>
                        <a href="#" class="btn btn-light btn-sm">View Bookings</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-currency-dollar"></i> Payments</h5>
                        <p class="card-text">Track and manage payments.</p>
                        <a href="#" class="btn btn-light btn-sm">View Payments</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-secondary mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-people"></i> Users</h5>
                        <p class="card-text">Manage user accounts and roles.</p>
                        <a href="#" class="btn btn-light btn-sm">View Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-life-preserver"></i> Support</h5>
                        <p class="card-text">Access support and help resources.</p>
                        <a href="#" class="btn btn-light btn-sm">Get Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
