<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_owner();
auth_refresh_session_from_db($conn, (int) $_SESSION['user_id']);

$uid = (int) $_SESSION['user_id'];

function owner_image_path(?string $imagePath): ?string
{
    if (empty($imagePath)) {
        return null;
    }
    if (strpos($imagePath, 'assets/') === 0) {
        return '../../' . $imagePath;
    }
    if (strpos($imagePath, '../../') === 0) {
        return $imagePath;
    }
    return '../../' . $imagePath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_my_hostel']) && auth_owner_can('edit_hostel')) {
    $hostel_id = (int) ($_POST['hostel_id'] ?? 0);
    $hostel_name = trim($_POST['hostel_name'] ?? '');
    $hostel_address = trim($_POST['hostel_address'] ?? '');
    $hostel_description = trim($_POST['hostel_description'] ?? '');

    $chk = mysqli_prepare($conn, 'SELECT id FROM hostels WHERE id = ? AND owner_id = ? AND deleted_at IS NULL');
    mysqli_stmt_bind_param($chk, 'ii', $hostel_id, $uid);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    $ok = mysqli_stmt_num_rows($chk) === 1;
    mysqli_stmt_close($chk);

    if ($ok && $hostel_name && $hostel_id) {
        $stmt = mysqli_prepare($conn, 'UPDATE hostels SET name = ?, address = ?, description = ? WHERE id = ? AND owner_id = ?');
        mysqli_stmt_bind_param($stmt, 'sssii', $hostel_name, $hostel_address, $hostel_description, $hostel_id, $uid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: owner_hostels.php');
        exit;
    }
}

$canView = auth_owner_can('view_hostels');
$hostels = [];
if ($canView) {
    $sql = "
        SELECT h.*,
            (SELECT image_path FROM hostel_images hi WHERE hi.hostel_id = h.id AND hi.deleted_at IS NULL LIMIT 1) AS image_path,
            b.name AS business_name, br.name AS branch_name
        FROM hostels h
        LEFT JOIN business b ON h.business_id = b.id
        LEFT JOIN branch br ON h.branch_id = br.id
        WHERE h.owner_id = " . $uid . " AND h.deleted_at IS NULL
        ORDER BY h.id DESC
    ";
    $res = mysqli_query($conn, $sql);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $hostels[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My hostels</title>
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
        <span class="navbar-brand fw-bold">My hostels</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#oc"><span class="navbar-toggler-icon"></span></button>
    </div>
</nav>
<div class="offcanvas offcanvas-start" tabindex="-1" id="oc"><div class="offcanvas-body p-0"><?php include '../../includes/sidebar_owner.php'; ?></div></div>

<div class="main-offset">
    <div class="container-fluid">
        <h2 class="mb-4"><i class="bi bi-building"></i> My hostels</h2>
        <?php if (!$canView): ?>
            <div class="alert alert-warning">You do not have permission to view hostels. Contact an administrator.</div>
        <?php elseif (count($hostels) === 0): ?>
            <p class="text-muted">No hostels are assigned to your account yet. An administrator must assign you as the owner on each hostel.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($hostels as $hostel): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <?php $img = owner_image_path($hostel['image_path'] ?? null); ?>
                            <?php if ($img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top" alt="" style="height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($hostel['name']); ?></h5>
                                <p class="card-text small text-muted mb-1"><?php echo htmlspecialchars($hostel['business_name'] ?? ''); ?> · <?php echo htmlspecialchars($hostel['branch_name'] ?? ''); ?></p>
                                <p class="card-text small"><?php echo htmlspecialchars($hostel['address'] ?? ''); ?></p>
                                <?php if (auth_owner_can('edit_hostel')): ?>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#em<?php echo (int) $hostel['id']; ?>">Edit</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if (auth_owner_can('edit_hostel')): ?>
                    <div class="modal fade" id="em<?php echo (int) $hostel['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit hostel</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="hostel_id" value="<?php echo (int) $hostel['id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" name="hostel_name" class="form-control" value="<?php echo htmlspecialchars($hostel['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" name="hostel_address" class="form-control" value="<?php echo htmlspecialchars($hostel['address'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="hostel_description" class="form-control" rows="3"><?php echo htmlspecialchars($hostel['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="edit_my_hostel" class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
