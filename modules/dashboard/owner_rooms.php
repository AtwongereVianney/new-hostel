<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_owner();
auth_refresh_session_from_db($conn, (int) $_SESSION['user_id']);

if (!auth_owner_can('manage_rooms')) {
    header('Location: owner_hostels.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$rooms = [];
$sql = "
    SELECT r.*, h.name AS hostel_name
    FROM rooms r
    INNER JOIN hostels h ON r.hostel_id = h.id
    WHERE h.owner_id = $uid AND r.deleted_at IS NULL AND h.deleted_at IS NULL
    ORDER BY h.name, r.room_number
";
$res = mysqli_query($conn, $sql);
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $rooms[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; min-height: 100vh; }
        .sidebar-fixed { position: fixed; top: 0; left: 0; height: 100vh; width: 250px; overflow-y: auto; z-index: 1030;
            background: #f8f9fa; border-right: 1px solid #dee2e6; }
        .main-offset { margin-left: 250px; min-height: 100vh; padding: 2rem 1rem; }
        @media (max-width: 991.98px) {
            .sidebar-fixed { display: none; }
            .main-offset { margin-left: 0; padding-top: 4rem; }
        }
    </style>
</head>
<body>
<div class="sidebar-fixed d-none d-lg-block"><?php include '../../includes/sidebar_owner.php'; ?></div>
<nav class="navbar navbar-light bg-light d-lg-none fixed-top">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">My rooms</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#oc"><span class="navbar-toggler-icon"></span></button>
    </div>
</nav>
<div class="offcanvas offcanvas-start" tabindex="-1" id="oc"><div class="offcanvas-body p-0"><?php include '../../includes/sidebar_owner.php'; ?></div></div>

<div class="main-offset">
    <div class="container-fluid">
        <h2 class="mb-4"><i class="bi bi-door-open"></i> Rooms</h2>
        <div class="table-responsive card shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Hostel</th>
                        <th>Room</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['hostel_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($r['type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['price']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r['status'] ?? ''); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rooms) === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No rooms yet for your hostels.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small mt-3">To add or change rooms, ask a system administrator or use the admin dashboard if you have access.</p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
