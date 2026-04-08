<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$on = htmlspecialchars($_SESSION['user_name'] ?? 'Owner');
?>
<style>
    .sidebar-nav { transition: all 0.3s ease; }
    .nav-link { padding: 0.75rem 1rem; margin-bottom: 0.25rem; border-radius: 0.375rem; display: flex; align-items: center; gap: 0.75rem; }
    .nav-link:hover { background-color: #e9ecef; }
    .nav-link.active { background-color: #0d6efd; color: white !important; }
    .sidebar-brand { padding: 1rem; border-bottom: 1px solid #dee2e6; margin-bottom: 0.5rem; }
</style>
<div class="sidebar-nav d-flex flex-column flex-shrink-0 bg-light h-100">
    <div class="sidebar-brand">
        <span class="fs-5 fw-bold"><i class="bi bi-house-door text-primary"></i> My hostels</span>
    </div>
    <div class="flex-grow-1 px-3 py-2">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="owner_hostels.php" class="nav-link link-dark<?php echo basename($_SERVER['PHP_SELF']) === 'owner_hostels.php' ? ' active' : ''; ?>">
                    <i class="bi bi-building"></i><span>Hostels</span>
                </a>
            </li>
            <?php if (!empty($_SESSION['owner_permissions']['manage_rooms'])): ?>
            <li class="nav-item">
                <a href="owner_rooms.php" class="nav-link link-dark<?php echo basename($_SERVER['PHP_SELF']) === 'owner_rooms.php' ? ' active' : ''; ?>">
                    <i class="bi bi-door-open"></i><span>Rooms</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (!empty($_SESSION['owner_permissions']['view_bookings'])): ?>
            <li class="nav-item">
                <a href="owner_bookings.php" class="nav-link link-dark<?php echo basename($_SERVER['PHP_SELF']) === 'owner_bookings.php' ? ' active' : ''; ?>">
                    <i class="bi bi-calendar-check"></i><span>Bookings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="px-3 pb-3 border-top pt-3">
        <small class="text-muted d-block mb-2"><?php echo $on; ?></small>
        <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm w-100">Sign out</a>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
