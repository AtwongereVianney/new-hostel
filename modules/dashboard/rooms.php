<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_admin();

// Handle Add Room
if (isset($_POST['add_room'])) {
    $business_id = intval($_POST['business_id']);
    $branch_id = intval($_POST['branch_id']);
    $hostel_id = intval($_POST['hostel_id']);
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    $room_price = floatval($_POST['room_price']);
    $room_status = trim($_POST['room_status']);
    $image_path = null;
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('room_', true) . '.' . $ext;
        $target = 'assets/images/rooms/' . $filename;
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }
    if ($business_id && $branch_id && $hostel_id && $room_number && $room_price) {
        $stmt = mysqli_prepare($conn, "INSERT INTO rooms (business_id, branch_id, hostel_id, room_number, type, price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiissds', $business_id, $branch_id, $hostel_id, $room_number, $room_type, $room_price, $room_status);
        mysqli_stmt_execute($stmt);
        $room_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        if ($image_path && $room_id) {
            $stmt = mysqli_prepare($conn, "INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $room_id, $image_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: rooms.php');
        exit;
    }
}
// Handle Edit Room
if (isset($_POST['edit_room'])) {
    $room_id = intval($_POST['room_id']);
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    $room_price = floatval($_POST['room_price']);
    $room_status = trim($_POST['room_status']);
    $image_path = null;
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('room_', true) . '.' . $ext;
        $target = 'assets/images/rooms/' . $filename;
        if (move_uploaded_file($_FILES['room_image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }
    if ($room_id && $room_number) {
        $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_number = ?, type = ?, price = ?, status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ssdsi', $room_number, $room_type, $room_price, $room_status, $room_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($image_path) {
            // Soft delete old images
            $stmt = mysqli_prepare($conn, "UPDATE room_images SET deleted_at = NOW() WHERE room_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $room_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            // Insert new image
            $stmt = mysqli_prepare($conn, "INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $room_id, $image_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: rooms.php');
        exit;
    }
}
// Handle Delete Room
if (isset($_POST['delete_room'])) {
    $room_id = intval($_POST['room_id']);
    if ($room_id) {
        $stmt = mysqli_prepare($conn, "UPDATE rooms SET deleted_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $room_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Soft delete images
        $stmt = mysqli_prepare($conn, "UPDATE room_images SET deleted_at = NOW() WHERE room_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $room_id);
mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: rooms.php');
        exit;
    }
}
// Fetch all rooms with images, hostel, business, and branch info
$rooms = [];
$query = "SELECT r.*, ri.image_path, h.name AS hostel_name, b.name AS business_name, br.name AS branch_name FROM rooms r LEFT JOIN room_images ri ON r.id = ri.room_id AND ri.deleted_at IS NULL LEFT JOIN hostels h ON r.hostel_id = h.id LEFT JOIN business b ON r.business_id = b.id LEFT JOIN branch br ON r.branch_id = br.id WHERE r.deleted_at IS NULL ORDER BY r.id DESC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}
// Fetch all hostels for the hostel dropdown, including business_id and branch_id
$all_hostels = [];
$hostels_result = mysqli_query($conn, "SELECT h.id, h.name, h.business_id, h.branch_id, b.name AS business_name, br.name AS branch_name FROM hostels h LEFT JOIN business b ON h.business_id = b.id LEFT JOIN branch br ON h.branch_id = br.id WHERE h.deleted_at IS NULL");
while ($h = mysqli_fetch_assoc($hostels_result)) {
    $all_hostels[] = $h;
}
// Fetch all businesses and branches for display (not for selection)
$all_businesses = [];
$businesses_result = mysqli_query($conn, "SELECT id, name FROM business WHERE deleted_at IS NULL");
while ($b = mysqli_fetch_assoc($businesses_result)) {
    $all_businesses[$b['id']] = $b['name'];
}
$all_branches = [];
$branches_result = mysqli_query($conn, "SELECT id, name FROM branch WHERE deleted_at IS NULL");
while ($br = mysqli_fetch_assoc($branches_result)) {
    $all_branches[$br['id']] = $br['name'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rooms</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .business-content {
            margin-left: 250px;
            height: 100vh;
            overflow-y: auto;
            padding: 2rem 1rem;
        }
        @media (max-width: 991.98px) {
            .sidebar-fixed {
                display: none;
            }
            .business-content {
                margin-left: 0;
                padding-top: 4rem;
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        @media (max-width: 576px) {
            .business-content {
                padding: 4rem 0.5rem 1rem 0.5rem;
            }
        }
        .business-card {
            min-width: 250px;
            max-width: 350px;
            margin: 0 auto 1.5rem auto;
        }
        .card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .modal-confirm {
            text-align: center;
            padding: 2rem 1rem;
        }
        .modal-confirm .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
        }
        .modal-confirm .modal-body i {
            font-size: 2.5rem;
            color: #dc3545;
            margin-bottom: 1rem;
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
    <div class="business-content">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-door-open"></i> Rooms</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="bi bi-plus-circle"></i> Add Room</button>
            </div>
            <div class="row g-4">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm mb-3 room-card">
                            <?php if (!empty($room['image_path'])): ?>
                                <img src="<?= htmlspecialchars($room['image_path']) ?>" class="card-img-top" alt="Room Image" style="height: 140px; object-fit: cover;">
                            <?php endif; ?>
                    <div class="card-body">
                                <h5 class="card-title mb-2">Room <?= htmlspecialchars($room['room_number']) ?></h5>
                                <p class="card-text mb-1"><small class="text-muted">Hostel: <?= htmlspecialchars($room['hostel_name']) ?> | Business: <?= htmlspecialchars($room['business_name']) ?> | Branch: <?= htmlspecialchars($room['branch_name']) ?></small></p>
                                <p class="card-text mb-1"><small class="text-muted">Type: <?= htmlspecialchars($room['type']) ?> | Price: <?= htmlspecialchars($room['price']) ?> | Status: <?= htmlspecialchars($room['status']) ?></small></p>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRoomModal<?= $room['id'] ?>"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRoomModal<?= $room['id'] ?>"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoomModal<?= $room['id'] ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- View Room Modal -->
                    <div class="modal fade" id="viewRoomModal<?= $room['id'] ?>" tabindex="-1" aria-labelledby="viewRoomModalLabel<?= $room['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewRoomModalLabel<?= $room['id'] ?>">Room Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body text-center">
                            <?php if (!empty($room['image_path'])): ?>
                              <img src="<?= htmlspecialchars($room['image_path']) ?>" alt="Room Image" style="width: 100%; max-width: 320px; height: auto; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;">
                            <?php endif; ?>
                            <h5>Room <?= htmlspecialchars($room['room_number']) ?></h5>
                            <p class="mb-1"><strong>Hostel:</strong> <?= htmlspecialchars($room['hostel_name']) ?></p>
                            <p class="mb-1"><strong>Business:</strong> <?= htmlspecialchars($room['business_name']) ?></p>
                            <p class="mb-1"><strong>Branch:</strong> <?= htmlspecialchars($room['branch_name']) ?></p>
                            <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($room['type']) ?></p>
                            <p class="mb-1"><strong>Price:</strong> <?= htmlspecialchars($room['price']) ?></p>
                            <p class="mb-0"><strong>Status:</strong> <?= htmlspecialchars($room['status']) ?></p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Edit Room Modal -->
                    <div class="modal fade" id="editRoomModal<?= $room['id'] ?>" tabindex="-1" aria-labelledby="editRoomModalLabel<?= $room['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post" action="" enctype="multipart/form-data">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editRoomModalLabel<?= $room['id'] ?>">Edit Room</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                              <div class="mb-3">
                                <label for="room_number_edit<?= $room['id'] ?>" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="room_number_edit<?= $room['id'] ?>" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required>
                              </div>
                              <div class="mb-3">
                                <label for="room_type_edit<?= $room['id'] ?>" class="form-label">Type</label>
                                <input type="text" class="form-control" id="room_type_edit<?= $room['id'] ?>" name="room_type" value="<?= htmlspecialchars($room['type']) ?>">
                              </div>
                              <div class="mb-3">
                                <label for="room_price_edit<?= $room['id'] ?>" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="room_price_edit<?= $room['id'] ?>" name="room_price" value="<?= htmlspecialchars($room['price']) ?>" required>
                              </div>
                              <div class="mb-3">
                                <label for="room_status_edit<?= $room['id'] ?>" class="form-label">Status</label>
                                <input type="text" class="form-control" id="room_status_edit<?= $room['id'] ?>" name="room_status" value="<?= htmlspecialchars($room['status']) ?>">
                              </div>
                              <div class="mb-3">
                                <label for="room_image_edit<?= $room['id'] ?>" class="form-label">Change Image (optional)</label>
                                <input type="file" class="form-control" id="room_image_edit<?= $room['id'] ?>" name="room_image" accept="image/*">
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="submit" name="edit_room" class="btn btn-warning">Save changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <!-- Delete Room Modal -->
                    <div class="modal fade" id="deleteRoomModal<?= $room['id'] ?>" tabindex="-1" aria-labelledby="deleteRoomModalLabel<?= $room['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content modal-confirm">
                          <form method="post" action="">
                            <div class="modal-body">
                              <i class="bi bi-exclamation-triangle-fill"></i>
                              <h5 class="modal-title mb-3" id="deleteRoomModalLabel<?= $room['id'] ?>">Delete Room</h5>
                              <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                              <p>Are you sure you want to delete <strong><?= htmlspecialchars($room['room_number']) ?></strong>?</p>
                            </div>
                            <div class="modal-footer justify-content-center">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="delete_room" class="btn btn-danger">Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Add Room Modal -->
            <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addRoomModalLabel">Add Room</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label for="hostel_id" class="form-label">Hostel</label>
                        <select class="form-select" id="hostel_id" name="hostel_id" required>
                          <option value="">Select Hostel</option>
                          <?php foreach ($all_hostels as $h): ?>
                            <option value="<?= $h['id'] ?>" data-business="<?= $h['business_id'] ?>" data-branch="<?= $h['branch_id'] ?>">
                              <?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['business_name']) ?> / <?= htmlspecialchars($h['branch_name']) ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="business_id" class="form-label">Business</label>
                        <select class="form-select" id="business_id" name="business_id" disabled required>
                          <option value="">Select Business</option>
                          <?php foreach ($all_businesses as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id" disabled required>
                          <option value="">Select Branch</option>
                          <?php foreach ($all_branches as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" required>
                      </div>
                      <div class="mb-3">
                        <label for="room_type" class="form-label">Type</label>
                        <input type="text" class="form-control" id="room_type" name="room_type">
                      </div>
                      <div class="mb-3">
                        <label for="room_price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="room_price" name="room_price" required>
                      </div>
                      <div class="mb-3">
                        <label for="room_status" class="form-label">Status</label>
                        <input type="text" class="form-control" id="room_status" name="room_status" value="vacant">
                      </div>
                      <div class="mb-3">
                        <label for="room_image" class="form-label">Room Image</label>
                        <input type="file" class="form-control" id="room_image" name="room_image" accept="image/*">
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
                    </div>
                  </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // When hostel is selected, set business and branch dropdowns
    const hostelSelect = document.getElementById('hostel_id');
    const businessSelect = document.getElementById('business_id');
    const branchSelect = document.getElementById('branch_id');
    if (hostelSelect && businessSelect && branchSelect) {
      hostelSelect.addEventListener('change', function() {
        const selected = hostelSelect.options[hostelSelect.selectedIndex];
        const businessId = selected.getAttribute('data-business');
        const branchId = selected.getAttribute('data-branch');
        businessSelect.value = businessId || '';
        branchSelect.value = branchId || '';
      });
    }
    </script>
</body>
</html>
