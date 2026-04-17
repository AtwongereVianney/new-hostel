<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/mailer.php';
auth_require_admin();

$defaults = auth_default_owner_permissions();
$message = '';
$error = '';

function mh_collect_permissions(array $defaults): array
{
    $out = [];
    foreach (array_keys($defaults) as $key) {
        $out[$key] = isset($_POST['perm_' . $key]);
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_owner'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $perms = mh_collect_permissions($defaults);
        
        if ($password === '') {
            $password = 'MMU' . rand(1000, 9999) . '!';
        }

        if (!$name || !$email || strlen($password) < 6) {
            $error = 'Name, email, and password (min 6 characters) are required.';
        } else {
            $check = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($check, 's', $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            $exists = mysqli_stmt_num_rows($check) > 0;
            mysqli_stmt_close($check);

            if ($exists) {
                $error = 'That email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $permJson = json_encode($perms);
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO users (business_id, branch_id, name, email, password, phone, user_type, permissions_json)
                    VALUES (1, 1, ?, ?, ?, ?, 'hostel_owner', ?)
                ");
                mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hash, $phone, $permJson);
                if (mysqli_stmt_execute($stmt)) {
                    $new_user_id = mysqli_insert_id($conn);
                    $message = 'Hostel owner account created.';
                    
                    if (!empty($_POST['assigned_hostels']) && is_array($_POST['assigned_hostels'])) {
                        $hostel_ids = array_map('intval', $_POST['assigned_hostels']);
                        $ids_csv = implode(',', $hostel_ids);
                        if (!empty($ids_csv)) {
                            mysqli_query($conn, "UPDATE hostels SET owner_id = $new_user_id WHERE id IN ($ids_csv)");
                        }
                    }
                    
                    $mailRes = mmu_send_manager_credentials_email($email, $name, 'Hostel Owner', $password);
                    if (empty($mailRes['success'])) {
                        $error = 'Account created, but failed to send credentials email: ' . ($mailRes['error'] ?? 'Unknown error');
                    } else {
                        $message .= ' Credentials have been emailed to the owner.';
                    }
                } else {
                    $error = 'Could not create account.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    if (isset($_POST['update_owner'])) {
        $id = (int) ($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $perms = mh_collect_permissions($defaults);

        if (!$id || !$name || !$email) {
            $error = 'Invalid update.';
        } else {
            $permJson = json_encode($perms);
            if ($newPass !== '' && strlen($newPass) >= 6) {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "
                    UPDATE users SET name = ?, email = ?, phone = ?, permissions_json = ?, password = ?
                    WHERE id = ? AND user_type = 'hostel_owner' AND deleted_at IS NULL
                ");
                mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $phone, $permJson, $hash, $id);
            } else {
                $stmt = mysqli_prepare($conn, "
                    UPDATE users SET name = ?, email = ?, phone = ?, permissions_json = ?
                    WHERE id = ? AND user_type = 'hostel_owner' AND deleted_at IS NULL
                ");
                mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $phone, $permJson, $id);
            }
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Owner updated.';
                
                $adminRes = mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'admin' ORDER BY id ASC LIMIT 1");
                $adminId = 1;
                if ($adminRes && $adminRow = mysqli_fetch_assoc($adminRes)) {
                    $adminId = (int)$adminRow['id'];
                }
                
                mysqli_query($conn, "UPDATE hostels SET owner_id = $adminId WHERE owner_id = $id AND deleted_at IS NULL");
                
                if (!empty($_POST['assigned_hostels']) && is_array($_POST['assigned_hostels'])) {
                    $hostel_ids = array_map('intval', $_POST['assigned_hostels']);
                    $ids_csv = implode(',', $hostel_ids);
                    if (!empty($ids_csv)) {
                        mysqli_query($conn, "UPDATE hostels SET owner_id = $id WHERE id IN ($ids_csv)");
                    }
                }
            } else {
                $error = 'Could not update (email may be in use).';
            }
            mysqli_stmt_close($stmt);
        }
    }

    if (isset($_POST['delete_owner'])) {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id) {
            $hq = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM hostels WHERE owner_id = ' . (int) $id . ' AND deleted_at IS NULL');
            $crow = mysqli_fetch_assoc($hq);
            if ((int) ($crow['c'] ?? 0) > 0) {
                $error = 'Reassign or remove hostels from this owner before deleting the account.';
            } else {
                $stmt = mysqli_prepare($conn, 'UPDATE users SET deleted_at = NOW() WHERE id = ? AND user_type = ?');
                $ut = 'hostel_owner';
                mysqli_stmt_bind_param($stmt, 'is', $id, $ut);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Owner account removed.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

$owners = [];
$res = mysqli_query($conn, "
    SELECT id, name, email, phone, permissions_json, created_at
    FROM users
    WHERE user_type = 'hostel_owner' AND deleted_at IS NULL
    ORDER BY name
");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $owners[] = $row;
}

$all_hostels = [];
$res_hostels = mysqli_query($conn, "SELECT id, name, owner_id FROM hostels WHERE deleted_at IS NULL ORDER BY name");
while ($res_hostels && ($row_h = mysqli_fetch_assoc($res_hostels))) {
    $all_hostels[] = $row_h;
}

$permLabels = [
    'view_hostels' => 'View own hostels',
    'edit_hostel' => 'Edit hostel details',
    'manage_rooms' => 'Manage rooms',
    'view_bookings' => 'View bookings',
    'manage_bookings' => 'Manage booking status',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel owners</title>
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
<div class="sidebar-fixed d-none d-lg-block"><?php include '../../includes/sidebar.php'; ?></div>
<nav class="navbar navbar-light bg-light d-lg-none fixed-top">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Hostel System</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#oc"><span class="navbar-toggler-icon"></span></button>
    </div>
</nav>
<div class="offcanvas offcanvas-start" tabindex="-1" id="oc"><div class="offcanvas-body p-0"><?php include '../../includes/sidebar.php'; ?></div></div>

<div class="main-offset">
    <div class="container-fluid">
        <h2 class="mb-4"><i class="bi bi-person-badge"></i> Hostel owners</h2>
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Create owner account</div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Full name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email (login)</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Permissions</label>
                        <?php foreach ($permLabels as $key => $label): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="perm_<?php echo htmlspecialchars($key); ?>" id="c_<?php echo htmlspecialchars($key); ?>"
                                    <?php echo !empty($defaults[$key]) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="c_<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Assign Hostels</label>
                        <select name="assigned_hostels[]" class="form-select" multiple size="3">
                            <?php foreach ($all_hostels as $h): ?>
                                <option value="<?php echo (int)$h['id']; ?>">
                                    <?php echo htmlspecialchars($h['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple. You can also assign these later.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="create_owner" class="btn btn-primary">Create account</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Existing owners</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Assigned Hostels</th>
                                <th>Permissions</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($owners as $o): ?>
                            <?php
                            $p = auth_merge_owner_permissions($o['permissions_json']);
                            $psum = implode(', ', array_keys(array_filter($p)));
                            $assigned_h = [];
                            foreach ($all_hostels as $h) {
                                if ((int)$h['owner_id'] === (int)$o['id']) {
                                    $assigned_h[] = $h['name'];
                                }
                            }
                            $assigned_names = implode(', ', $assigned_h);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($o['name']); ?></td>
                                <td><?php echo htmlspecialchars($o['email']); ?></td>
                                <td><?php echo htmlspecialchars($o['phone'] ?? ''); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($assigned_names ?: 'None'); ?></small></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($psum ?: 'none'); ?></small></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#edit<?php echo (int) $o['id']; ?>">
                                            <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline ms-1">Edit</span>
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this owner account? Hostels must be reassigned first.');">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $o['id']; ?>">
                                            <button type="submit" name="delete_owner" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> <span class="d-none d-sm-inline ms-1">Delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($owners) === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No hostel owners yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php foreach ($owners as $o): ?>
        <div class="modal fade" id="edit<?php echo (int) $o['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit owner</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?php echo (int) $o['id']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($o['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($o['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($o['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New password (optional)</label>
                                    <input type="password" name="new_password" class="form-control" minlength="6" placeholder="Leave blank to keep">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Permissions</label>
                                    <?php
                                    $op = auth_merge_owner_permissions($o['permissions_json']);
                                    foreach ($permLabels as $key => $label):
                                        $fid = 'e' . (int) $o['id'] . '_' . $key;
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="perm_<?php echo htmlspecialchars($key); ?>" id="<?php echo htmlspecialchars($fid); ?>"
                                                <?php echo !empty($op[$key]) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="<?php echo htmlspecialchars($fid); ?>"><?php echo htmlspecialchars($label); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Assigned Hostels</label>
                                    <select name="assigned_hostels[]" class="form-select" multiple size="3">
                                        <?php foreach ($all_hostels as $h): ?>
                                            <option value="<?php echo (int)$h['id']; ?>" <?php echo ((int)$h['owner_id'] === (int)$o['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($h['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="submit" name="delete_owner" class="btn btn-outline-danger" onclick="return confirm('Remove this owner account? Hostels must be reassigned first.');">Delete account</button>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="update_owner" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
