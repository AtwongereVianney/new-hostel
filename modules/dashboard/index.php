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
        /* Responsive card styling similar to business cards */
        .dashboard-card {
            min-width: 250px;
            max-width: 350px;
            margin: 0 auto 1.5rem auto;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }
        @media (max-width: 991.98px) {
            .sidebar-fixed {
                display: none;
            }
            .dashboard-content {
                margin-left: 0;
                padding-top: 4rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        @media (max-width: 576px) {
            .dashboard-content {
                padding: 4rem 0.5rem 1rem 0.5rem;
            }
            .dashboard-card {
                min-width: 200px;
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
        <div class="container-fluid">
            <h2 class="mb-4">Welcome to your Dashboard</h2>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-primary dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-building me-2"></i> Hostels</h5>
                            <p class="card-text mb-3">Manage and view all hostels in your system.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> View Hostels</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-success dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-door-closed me-2"></i> Rooms</h5>
                            <p class="card-text mb-3">Check room status and availability.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> View Rooms</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-warning dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-calendar-check me-2"></i> Bookings</h5>
                            <p class="card-text mb-3">View and manage all bookings.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> View Bookings</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-info dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-currency-dollar me-2"></i> Payments</h5>
                            <p class="card-text mb-3">Track and manage all payments.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> View Payments</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-secondary dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-people me-2"></i> Users</h5>
                            <p class="card-text mb-3">Manage user accounts and roles.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> View Users</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card text-bg-danger dashboard-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="bi bi-life-preserver me-2"></i> Support</h5>
                            <p class="card-text mb-3">Access support and help resources.</p>
                            <div class="card-actions">
                                <a href="#" class="btn btn-light btn-sm"><i class="bi bi-eye me-1"></i> Get Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>