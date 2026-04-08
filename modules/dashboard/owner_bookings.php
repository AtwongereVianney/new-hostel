<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_owner();
auth_refresh_session_from_db($conn, (int) $_SESSION['user_id']);

if (!auth_owner_can('view_bookings')) {
    header('Location: owner_hostels.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$rows = [];
$sql = "
    SELECT b.id, b.room_id, b.start_date, b.end_date, b.status, b.created_at,
           h.name AS hostel_name, r.room_number,
           u.name AS student_name, u.email AS student_email, u.phone AS student_phone
    FROM bookings b
    INNER JOIN rooms r ON b.room_id = r.id
    INNER JOIN hostels h ON r.hostel_id = h.id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE h.owner_id = $uid AND b.deleted_at IS NULL
    ORDER BY b.created_at DESC
";
$res = mysqli_query($conn, $sql);
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings</title>
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
        <span class="navbar-brand fw-bold">Bookings</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#oc"><span class="navbar-toggler-icon"></span></button>
    </div>
</nav>
<div class="offcanvas offcanvas-start" tabindex="-1" id="oc"><div class="offcanvas-body p-0"><?php include '../../includes/sidebar_owner.php'; ?></div></div>

<div class="main-offset">
    <div class="container-fluid">
        <h2 class="mb-4"><i class="bi bi-calendar-check"></i> Bookings</h2>
        <div class="table-responsive card shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Hostel</th>
                        <th>Room</th>
                        <th>Guest</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['hostel_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['room_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['student_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['student_email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['student_phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['start_date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($b['end_date'] ?? ''); ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($b['status'] ?? ''); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No bookings for your hostels yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
