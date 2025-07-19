<!-- Sidebar using Bootstrap, responsive for both desktop and mobile (offcanvas) -->
<div class="sidebar-nav d-flex flex-column flex-shrink-0 p-3 bg-light h-100">
    <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <span class="fs-4 fw-bold">Hostel System</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="../dashboard/index.php" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li><a href="../business.php" class="nav-link link-dark"><i class="bi bi-building"></i> Business</a></li>
        <li><a href="../branches.php" class="nav-link link-dark"><i class="bi bi-diagram-3"></i> Branches</a></li>
        <li><a href="../roles.php" class="nav-link link-dark"><i class="bi bi-person-badge"></i> Roles</a></li>
        <li><a href="../permissions.php" class="nav-link link-dark"><i class="bi bi-shield-lock"></i> Permissions</a></li>
        <li><a href="../users.php" class="nav-link link-dark"><i class="bi bi-people"></i> Users</a></li>
        <li><a href="../hostels.php" class="nav-link link-dark"><i class="bi bi-house-door"></i> Hostels</a></li>
        <li><a href="../rooms.php" class="nav-link link-dark"><i class="bi bi-door-open"></i> Rooms</a></li>
        <li><a href="../bookings.php" class="nav-link link-dark"><i class="bi bi-calendar-check"></i> Bookings</a></li>
        <li><a href="../payments.php" class="nav-link link-dark"><i class="bi bi-credit-card"></i> Payments</a></li>
        <li><a href="../allocations.php" class="nav-link link-dark"><i class="bi bi-box-arrow-in-right"></i> Allocations</a></li>
        <li><a href="../audit_logs.php" class="nav-link link-dark"><i class="bi bi-journal-text"></i> Audit Logs</a></li>
        <li><a href="../hostel_images.php" class="nav-link link-dark"><i class="bi bi-image"></i> Hostel Images</a></li>
        <li><a href="../room_images.php" class="nav-link link-dark"><i class="bi bi-images"></i> Room Images</a></li>
        <li><a href="../notifications.php" class="nav-link link-dark"><i class="bi bi-bell"></i> Notifications</a></li>
        <li><a href="../reviews.php" class="nav-link link-dark"><i class="bi bi-star"></i> Reviews</a></li>
        <li><a href="../amenities.php" class="nav-link link-dark"><i class="bi bi-list-check"></i> Amenities</a></li>
        <li><a href="../sessions.php" class="nav-link link-dark"><i class="bi bi-clock-history"></i> Sessions</a></li>
        <li><a href="../payment_gateways.php" class="nav-link link-dark"><i class="bi bi-cash-stack"></i> Payment Gateways</a></li>
        <li><a href="../transactions.php" class="nav-link link-dark"><i class="bi bi-arrow-left-right"></i> Transactions</a></li>
        <li><a href="../support_tickets.php" class="nav-link link-dark"><i class="bi bi-headset"></i> Support Tickets</a></li>
        <li><a href="../ticket_replies.php" class="nav-link link-dark"><i class="bi bi-chat-dots"></i> Ticket Replies</a></li>
        <li><a href="../documents.php" class="nav-link link-dark"><i class="bi bi-file-earmark-text"></i> Documents</a></li>
        <li><a href="../discounts.php" class="nav-link link-dark"><i class="bi bi-percent"></i> Discounts</a></li>
        <li><a href="../maintenance_requests.php" class="nav-link link-dark"><i class="bi bi-tools"></i> Maintenance Requests</a></li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://ui-avatars.com/api/?name=User" alt="user" width="32" height="32" class="rounded-circle me-2">
            <strong>User</strong>
        </a>
        <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
            <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../auth/logout.php">Sign out</a></li>
        </ul>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"> 