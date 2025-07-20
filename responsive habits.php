<?php
// Your PHP logic here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page Title</title>
    
    <!-- ESSENTIAL RESPONSIVE FRAMEWORK -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- UNIVERSAL RESPONSIVE STYLES -->
    <style>
        /* === CORE RESPONSIVE SETUP === */
        html, body {
            height: 100%;
        }
        
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        /* === RESPONSIVE CARD SYSTEM === */
        .responsive-card {
            min-width: 250px;
            max-width: 350px;
            margin: 0 auto 1.5rem auto;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        
        .responsive-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        /* === FLEXIBLE BUTTON LAYOUT === */
        .card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* === RESPONSIVE GRID IMPROVEMENTS === */
        .responsive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        /* === MOBILE-FIRST RESPONSIVE BREAKPOINTS === */
        /* Mobile First (default) */
        .main-content {
            padding: 1rem;
        }
        
        /* Tablet and up */
        @media (min-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .responsive-card {
                min-width: 280px;
            }
        }
        
        /* Desktop and up */
        @media (min-width: 992px) {
            .main-content {
                padding: 2rem;
            }
            
            .responsive-card {
                min-width: 300px;
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
            }
            
            .responsive-card {
                min-width: 200px;
                margin-bottom: 1rem;
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .btn-sm {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }
        
        /* === RESPONSIVE TEXT AND SPACING === */
        .responsive-title {
            font-size: clamp(1.25rem, 4vw, 2rem);
        }
        
        .responsive-text {
            font-size: clamp(0.875rem, 2vw, 1rem);
        }
        
        /* === SMOOTH INTERACTIONS === */
        .btn, .card, .nav-link {
            transition: all 0.2s ease-in-out;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        /* === RESPONSIVE MODALS === */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-header {
                padding: 0.75rem;
            }
            
            .modal-body {
                padding: 0.75rem;
            }
            
            .modal-footer {
                padding: 0.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
        }
        
        /* === RESPONSIVE TABLES === */
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 768px) {
            .table-responsive-custom table {
                font-size: 0.875rem;
            }
            
            .table-responsive-custom th,
            .table-responsive-custom td {
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }
        }
        
        /* === RESPONSIVE FORMS === */
        @media (max-width: 576px) {
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-control {
                font-size: 1rem; /* Prevents zoom on iOS */
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                border-radius: 0.375rem !important;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- YOUR PAGE CONTENT USING RESPONSIVE PATTERNS -->
    <div class="container-fluid">
        <div class="main-content">
            
            <!-- RESPONSIVE HEADER PATTERN -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="responsive-title mb-0">
                    <i class="bi bi-your-icon"></i> Page Title
                </h2>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle"></i> 
                        <span class="d-none d-sm-inline">Add Item</span>
                    </button>
                </div>
            </div>
            
            <!-- RESPONSIVE GRID PATTERN -->
            <div class="row g-4">
                <!-- Use Bootstrap's responsive grid classes -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card responsive-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-your-icon me-2"></i>
                                Card Title
                            </h5>
                            <p class="card-text responsive-text mb-3">
                                Your content here...
                            </p>
                            <div class="card-actions">
                                <button class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                    <span class="d-none d-sm-inline ms-1">Edit</span>
                                </button>
                                <button class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                    <span class="d-none d-sm-inline ms-1">Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Repeat for more cards -->
            </div>
            
            <!-- RESPONSIVE TABLE PATTERN -->
            <div class="table-responsive-custom mt-4">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Column 1</th>
                            <th>Column 2</th>
                            <th class="d-none d-md-table-cell">Column 3</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Your table rows -->
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
    
    <!-- RESPONSIVE MODAL PATTERN -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Field Name</label>
                            <input type="text" class="form-control" name="field" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ESSENTIAL RESPONSIVE SCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- OPTIONAL: RESPONSIVE UTILITIES SCRIPT -->
    <script>
        // Auto-hide text on small screens for buttons
        function updateResponsiveElements() {
            const screenWidth = window.innerWidth;
            const hideTextElements = document.querySelectorAll('.d-none.d-sm-inline');
            
            if (screenWidth < 576) {
                // Additional mobile optimizations can go here
            }
        }
        
        // Run on load and resize
        window.addEventListener('load', updateResponsiveElements);
        window.addEventListener('resize', updateResponsiveElements);
    </script>
</body>
</html>