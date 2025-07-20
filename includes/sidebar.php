<!-- Responsive Sidebar using Bootstrap -->
<style>
    .sidebar-nav {
        transition: all 0.3s ease;
    }
    
    .nav-link {
        padding: 0.75rem 1rem;
        margin-bottom: 0.25rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .nav-link:hover {
        background-color: #e9ecef;
        transform: translateX(2px);
    }
    
    .nav-link.active {
        background-color: #0d6efd;
        color: white !important;
    }
    
    .nav-link.active:hover {
        background-color: #0b5ed7;
    }
    
    .nav-link i {
        width: 1.2rem;
        text-align: center;
        flex-shrink: 0;
    }
    
    .sidebar-brand {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 0.5rem;
    }
    
    .sidebar-footer {
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
        margin-top: auto;
    }
    
    .user-avatar {
        transition: transform 0.2s ease;
    }
    
    .user-avatar:hover {
        transform: scale(1.1);
    }
    
    .dropdown-toggle::after {
        margin-left: auto;
    }
    
    /* Responsive improvements */
    @media (max-width: 991.98px) {
        .sidebar-nav {
            width: 100%;
            height: 100%;
        }
        
        .nav-link {
            font-size: 0.95rem;
        }
    }
    
    @media (max-width: 576px) {
        .nav-link {
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .nav-link i {
            width: 1rem;
        }
        
        .fs-4 {
            font-size: 1.1rem !important;
        }
    }
    
    /* Smooth scrolling for long menu */
    .sidebar-nav {
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #6c757d transparent;
    }
    
    .sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }
    
    .sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .sidebar-nav::-webkit-scrollbar-thumb {
        background-color: #6c757d;
        border-radius: 2px;
    }
    
    .sidebar-nav::-webkit-scrollbar-thumb:hover {
        background-color: #495057;
    }
</style>

<div class="sidebar-nav d-flex flex-column flex-shrink-0 bg-light h-100">
    <!-- Brand Section -->
    <div class="sidebar-brand">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none">
            <i class="bi bi-building-fill me-2 text-primary fs-5"></i>
            <span class="fs-4 fw-bold">Hostel System</span>
        </a>
    </div>
    
    <!-- Navigation Menu -->
    <div class="flex-grow-1 px-3 py-2">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="../dashboard/index.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="business.php" class="nav-link link-dark">
                    <i class="bi bi-building"></i>
                    <span>Business</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../branches.php" class="nav-link link-dark">
                    <i class="bi bi-diagram-3"></i>
                    <span>Branches</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../roles.php" class="nav-link link-dark">
                    <i class="bi bi-person-badge"></i>
                    <span>Roles</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../permissions.php" class="nav-link link-dark">
                    <i class="bi bi-shield-lock"></i>
                    <span>Permissions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../users.php" class="nav-link link-dark">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../hostels.php" class="nav-link link-dark">
                    <i class="bi bi-house-door"></i>
                    <span>Hostels</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="rooms.php" class="nav-link link-dark">
                    <i class="bi bi-door-open"></i>
                    <span>Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../bookings.php" class="nav-link link-dark">
                    <i class="bi bi-calendar-check"></i>
                    <span>Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../payments.php" class="nav-link link-dark">
                    <i class="bi bi-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../allocations.php" class="nav-link link-dark">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Allocations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../audit_logs.php" class="nav-link link-dark">
                    <i class="bi bi-journal-text"></i>
                    <span>Audit Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../hostel_images.php" class="nav-link link-dark">
                    <i class="bi bi-image"></i>
                    <span>Hostel Images</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../room_images.php" class="nav-link link-dark">
                    <i class="bi bi-images"></i>
                    <span>Room Images</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../notifications.php" class="nav-link link-dark">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../reviews.php" class="nav-link link-dark">
                    <i class="bi bi-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../amenities.php" class="nav-link link-dark">
                    <i class="bi bi-list-check"></i>
                    <span>Amenities</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../sessions.php" class="nav-link link-dark">
                    <i class="bi bi-clock-history"></i>
                    <span>Sessions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../payment_gateways.php" class="nav-link link-dark">
                    <i class="bi bi-cash-stack"></i>
                    <span>Payment Gateways</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../transactions.php" class="nav-link link-dark">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../support_tickets.php" class="nav-link link-dark">
                    <i class="bi bi-headset"></i>
                    <span>Support Tickets</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../ticket_replies.php" class="nav-link link-dark">
                    <i class="bi bi-chat-dots"></i>
                    <span>Ticket Replies</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../documents.php" class="nav-link link-dark">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Documents</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../discounts.php" class="nav-link link-dark">
                    <i class="bi bi-percent"></i>
                    <span>Discounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../maintenance_requests.php" class="nav-link link-dark">
                    <i class="bi bi-tools"></i>
                    <span>Maintenance</span>
                </a>
            </li>
        </ul>
    </div>
    
    <!-- User Section -->
    <div class="sidebar-footer px-3 pb-3">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle p-2 rounded" 
               id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false" 
               style="background-color: rgba(13, 110, 253, 0.1);">
                <img src="https://ui-avatars.com/api/?name=User&background=0d6efd&color=ffffff" 
                     alt="user" width="32" height="32" class="rounded-circle me-2 user-avatar">
                <div class="d-flex flex-column">
                    <strong class="text-primary">User</strong>
                    <small class="text-muted">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser">
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="../profile.php">
                        <i class="bi bi-person-circle me-2"></i>
                        Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="../settings.php">
                        <i class="bi bi-gear me-2"></i>
                        Settings
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center text-danger" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Sign out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">