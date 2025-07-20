<?php
require_once '../../config/db.php';

// Handle Add
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    if ($name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO business (name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Add Branch
if (isset($_POST['add_branch'])) {
    $business_id = intval($_POST['business_id']);
    $branch_name = trim($_POST['branch_name']);
    $branch_location = trim($_POST['branch_location']);
    $google_maps_link = trim($_POST['google_maps_link']);
    if ($business_id && $branch_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO branch (business_id, name, location, google_maps_link) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isss', $business_id, $branch_name, $branch_location, $google_maps_link);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Edit
if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    if ($id && $name) {
        $stmt = mysqli_prepare($conn, "UPDATE business SET name = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $name, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Delete
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    if ($id) {
        $stmt = mysqli_prepare($conn, "UPDATE business SET deleted_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Edit Branch
if (isset($_POST['edit_branch'])) {
    $branch_id = intval($_POST['branch_id']);
    $branch_name = trim($_POST['branch_name']);
    $branch_location = trim($_POST['branch_location']);
    $google_maps_link = trim($_POST['google_maps_link']);
    if ($branch_id && $branch_name) {
        $stmt = mysqli_prepare($conn, "UPDATE branch SET name = ?, location = ?, google_maps_link = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sssi', $branch_name, $branch_location, $google_maps_link, $branch_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Delete Branch
if (isset($_POST['delete_branch'])) {
    $branch_id = intval($_POST['branch_id']);
    if ($branch_id) {
        $stmt = mysqli_prepare($conn, "UPDATE branch SET deleted_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $branch_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: business.php');
        exit;
    }
}
// Handle Add Hostel
if (isset($_POST['add_hostel'])) {
    $business_id = intval($_POST['business_id']);
    $branch_id = intval($_POST['branch_id']);
    $hostel_name = trim($_POST['hostel_name']);
    $hostel_address = trim($_POST['hostel_address']);
    $hostel_description = trim($_POST['hostel_description']);
    $owner_id = 1; // You may want to set this dynamically
    $image_path = null;
    if (isset($_FILES['hostel_image']) && $_FILES['hostel_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['hostel_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('hostel_', true) . '.' . $ext;
        $target = 'assets/images/hostels/' . $filename;
        if (move_uploaded_file($_FILES['hostel_image']['tmp_name'], $target)) {
            $image_path = $target;
        }
    }
    if ($business_id && $branch_id && $hostel_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO hostels (business_id, branch_id, owner_id, name, address, description) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiisss', $business_id, $branch_id, $owner_id, $hostel_name, $hostel_address, $hostel_description);
        mysqli_stmt_execute($stmt);
        $hostel_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        if ($image_path && $hostel_id) {
            $stmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $hostel_id, $image_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: business.php');
        exit;
    }
}
// Fetch all businesses
$query = "SELECT * FROM business WHERE deleted_at IS NULL";
$result = mysqli_query($conn, $query);
// Fetch all branches grouped by business_id
$branches_by_business = [];
$branch_query = "SELECT * FROM branch WHERE deleted_at IS NULL";
$branch_result = mysqli_query($conn, $branch_query);
while ($branch = mysqli_fetch_assoc($branch_result)) {
    $branches_by_business[$branch['business_id']][] = $branch;
}
// Fetch all hostels grouped by branch_id
$hostels_by_branch = [];
$hostel_query = "SELECT h.*, hi.image_path FROM hostels h LEFT JOIN hostel_images hi ON h.id = hi.hostel_id AND hi.deleted_at IS NULL WHERE h.deleted_at IS NULL";
$hostel_result = mysqli_query($conn, $hostel_query);
while ($hostel = mysqli_fetch_assoc($hostel_result)) {
    $hostels_by_branch[$hostel['branch_id']][] = $hostel;
}
// Helper function to convert Google Maps link to embed URL
function getGoogleMapsEmbedUrl($link) {
    // If it's already an embed link, return as is
    if (strpos($link, 'embed') !== false) return $link;
    // Try to extract coordinates from a standard maps link
    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $matches)) {
        $lat = $matches[1];
        $lng = $matches[2];
        return "https://www.google.com/maps?q={$lat},{$lng}&output=embed";
    }
    // If it's a place link, try to convert
    if (preg_match('/\/place\/([^\/]+)/', $link, $matches)) {
        $place = urlencode($matches[1]);
        return "https://www.google.com/maps?q={$place}&output=embed";
    }
    // Fallback: just use the link as a search
    return "https://www.google.com/maps?q=" . urlencode($link) . "&output=embed";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Business</title>
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
                <h2 class="mb-0"><i class="bi bi-building"></i> Businesses</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Add Business</button>
            </div>
            <div class="row g-4">
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card business-card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-2"><i class="bi bi-building me-2"></i><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="card-text mb-1"><small class="text-muted">Created: <?= htmlspecialchars($row['created_at']) ?></small></p>
                                <p class="card-text mb-3"><small class="text-muted">Updated: <?= htmlspecialchars($row['updated_at']) ?></small></p>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addBranchModal<?= $row['id'] ?>"><i class="bi bi-plus"></i> Add Branch</button>
                                </div>
                                <!-- Branches List -->
                                <?php if (!empty($branches_by_business[$row['id']])): ?>
                                    <hr>
                                    <h6 class="mt-2 mb-1">Branches:</h6>
                                    <ul class="list-group list-group-flush mb-2">
                                        <?php foreach ($branches_by_business[$row['id']] as $branch): ?>
                                            <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= htmlspecialchars($branch['name']) ?></strong>
                                                    <?php if ($branch['location']): ?>
                                                        <span class="text-muted">(<?= htmlspecialchars($branch['location']) ?>)</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($branch['google_maps_link'])): ?>
                                                        <a href="<?= htmlspecialchars($branch['google_maps_link'] ?? '') ?>" target="_blank" class="ms-2" title="View on Google Maps"><i class="bi bi-geo-alt-fill text-primary"></i></a>
                                                        <div class="mt-2 mb-2" style="width:100%; max-width:350px; height:200px;">
                                                            <iframe
                                                                src="<?= htmlspecialchars(getGoogleMapsEmbedUrl($branch['google_maps_link'] ?? '')) ?>"
                                                                width="100%" height="100%" style="border:0; border-radius:8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $branch['id'] ?>"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteBranchModal<?= $branch['id'] ?>"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </li>
                                            <!-- Edit Branch Modal -->
                                            <div class="modal fade" id="editBranchModal<?= $branch['id'] ?>" tabindex="-1" aria-labelledby="editBranchModalLabel<?= $branch['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog">
                                                <div class="modal-content">
                                                  <form method="post" action="">
                                                    <div class="modal-header">
                                                      <h5 class="modal-title" id="editBranchModalLabel<?= $branch['id'] ?>">Edit Branch</h5>
                                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                      <input type="hidden" name="branch_id" value="<?= $branch['id'] ?>">
                                                      <div class="mb-3">
                                                        <label for="branch_name_edit<?= $branch['id'] ?>" class="form-label">Branch Name</label>
                                                        <input type="text" class="form-control" id="branch_name_edit<?= $branch['id'] ?>" name="branch_name" value="<?= htmlspecialchars($branch['name']) ?>" required>
                                                      </div>
                                                      <div class="mb-3">
                                                        <label for="branch_location_edit<?= $branch['id'] ?>" class="form-label">Location</label>
                                                        <input type="text" class="form-control" id="branch_location_edit<?= $branch['id'] ?>" name="branch_location" value="<?= htmlspecialchars($branch['location']) ?>">
                                                      </div>
                                                      <div class="mb-3">
                                                        <label for="google_maps_link_edit<?= $branch['id'] ?>" class="form-label">Google Maps Link</label>
                                                        <input type="url" class="form-control" id="google_maps_link_edit<?= $branch['id'] ?>" name="google_maps_link" value="<?= htmlspecialchars($branch['google_maps_link'] ?? '') ?>" placeholder="https://maps.google.com/...">
                                                      </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                      <button type="submit" name="edit_branch" class="btn btn-warning">Save changes</button>
                                                    </div>
                                                  </form>
                                                </div>
                                              </div>
                                            </div>
                                            <!-- Delete Branch Modal -->
                                            <div class="modal fade" id="deleteBranchModal<?= $branch['id'] ?>" tabindex="-1" aria-labelledby="deleteBranchModalLabel<?= $branch['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content modal-confirm">
                                                  <form method="post" action="">
                                                    <div class="modal-body">
                                                      <i class="bi bi-exclamation-triangle-fill"></i>
                                                      <h5 class="modal-title mb-3" id="deleteBranchModalLabel<?= $branch['id'] ?>">Delete Branch</h5>
                                                      <input type="hidden" name="branch_id" value="<?= $branch['id'] ?>">
                                                      <p>Are you sure you want to delete <strong><?= htmlspecialchars($branch['name']) ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer justify-content-center">
                                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                      <button type="submit" name="delete_branch" class="btn btn-danger">Delete</button>
                                                    </div>
                                                  </form>
                                                </div>
                                              </div>
                                            </div>
                                            <!-- Hostels List -->
                                            <?php if (!empty($hostels_by_branch[$branch['id']])): ?>
                                                <div class="mt-2 mb-2">
                                                    <h6>Hostels:</h6>
                                                    <div class="row g-2">
                                                        <?php foreach ($hostels_by_branch[$branch['id']] as $hostel): ?>
                                                            <div class="col-12">
                                                                <div class="card mb-2 p-2 flex-row align-items-center" style="max-width: 350px;">
                                                                    <?php if (!empty($hostel['image_path'])): ?>
                                                                        <img src="<?= htmlspecialchars($hostel['image_path']) ?>" alt="Hostel Image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-right: 10px;">
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?= htmlspecialchars($hostel['name']) ?></strong><br>
                                                                        <small><?= htmlspecialchars($hostel['address']) ?></small><br>
                                                                        <span class="text-muted small"><?= htmlspecialchars($hostel['description']) ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-info mt-2" data-bs-toggle="modal" data-bs-target="#addHostelModal<?= $branch['id'] ?>"><i class="bi bi-plus-circle"></i> Add Hostel</button>
                                            <!-- Add Hostel Modal -->
                                            <div class="modal fade" id="addHostelModal<?= $branch['id'] ?>" tabindex="-1" aria-labelledby="addHostelModalLabel<?= $branch['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog">
                                                <div class="modal-content">
                                                  <form method="post" action="" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                      <h5 class="modal-title" id="addHostelModalLabel<?= $branch['id'] ?>">Add Hostel to <?= htmlspecialchars($branch['name']) ?></h5>
                                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                      <input type="hidden" name="business_id" value="<?= $branch['business_id'] ?>">
                                                      <input type="hidden" name="branch_id" value="<?= $branch['id'] ?>">
                                                      <div class="mb-3">
                                                        <label for="hostel_name<?= $branch['id'] ?>" class="form-label">Hostel Name</label>
                                                        <input type="text" class="form-control" id="hostel_name<?= $branch['id'] ?>" name="hostel_name" required>
                                                      </div>
                                                      <div class="mb-3">
                                                        <label for="hostel_address<?= $branch['id'] ?>" class="form-label">Address</label>
                                                        <input type="text" class="form-control" id="hostel_address<?= $branch['id'] ?>" name="hostel_address">
                                                      </div>
                                                      <div class="mb-3">
                                                        <label for="hostel_description<?= $branch['id'] ?>" class="form-label">Description</label>
                                                        <textarea class="form-control" id="hostel_description<?= $branch['id'] ?>" name="hostel_description"></textarea>
                                                      </div>
                                                      <div class="mb-3">
                                                        <label for="hostel_image<?= $branch['id'] ?>" class="form-label">Hostel Image</label>
                                                        <input type="file" class="form-control" id="hostel_image<?= $branch['id'] ?>" name="hostel_image" accept="image/*">
                                                      </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                      <button type="submit" name="add_hostel" class="btn btn-info">Add Hostel</button>
                                                    </div>
                                                  </form>
                                                </div>
                                              </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post" action="">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editModalLabel<?= $row['id'] ?>">Edit Business</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="id" value="<?= $row['id'] ?>">
                              <div class="mb-3">
                                <label for="name<?= $row['id'] ?>" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name<?= $row['id'] ?>" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content modal-confirm">
                          <form method="post" action="">
                            <div class="modal-body">
                              <i class="bi bi-exclamation-triangle-fill"></i>
                              <h5 class="modal-title mb-3" id="deleteModalLabel<?= $row['id'] ?>">Delete Business</h5>
                              <input type="hidden" name="id" value="<?= $row['id'] ?>">
                              <p>Are you sure you want to delete <strong><?= htmlspecialchars($row['name']) ?></strong>?</p>
                            </div>
                            <div class="modal-footer justify-content-center">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <!-- Add Branch Modal -->
                    <div class="modal fade" id="addBranchModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="addBranchModalLabel<?= $row['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post" action="">
                            <div class="modal-header">
                              <h5 class="modal-title" id="addBranchModalLabel<?= $row['id'] ?>">Add Branch to <?= htmlspecialchars($row['name']) ?></h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="business_id" value="<?= $row['id'] ?>">
                              <div class="mb-3">
                                <label for="branch_name<?= $row['id'] ?>" class="form-label">Branch Name</label>
                                <input type="text" class="form-control" id="branch_name<?= $row['id'] ?>" name="branch_name" required>
                              </div>
                              <div class="mb-3">
                                <label for="branch_location<?= $row['id'] ?>" class="form-label">Location</label>
                                <input type="text" class="form-control" id="branch_location<?= $row['id'] ?>" name="branch_location">
                              </div>
                              <div class="mb-3">
                                <label for="google_maps_link<?= $row['id'] ?>" class="form-label">Google Maps Link</label>
                                <input type="url" class="form-control" id="google_maps_link<?= $row['id'] ?>" name="google_maps_link" value="<?= htmlspecialchars($row['google_maps_link'] ?? '') ?>" placeholder="https://maps.google.com/...">
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="submit" name="add_branch" class="btn btn-success">Add Branch</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <!-- Add Modal -->
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" action="">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addModalLabel">Add Business</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
