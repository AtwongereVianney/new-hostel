<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
auth_require_admin();

// Add this function to handle image paths correctly
function getCorrectImagePath($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // If path starts with assets/, add ../../
    if (strpos($imagePath, 'assets/') === 0) {
        return '../../' . $imagePath;
    }
    
    // If path already has ../../, use as is
    if (strpos($imagePath, '../../') === 0) {
        return $imagePath;
    }
    
    // Default: assume it needs ../../
    return '../../' . $imagePath;
}

// Handle Add Hostel
if (isset($_POST['add_hostel'])) {
    $business_id = intval($_POST['business_id']);
    $branch_id = intval($_POST['branch_id']);
    $hostel_name = trim($_POST['hostel_name']);
    $hostel_address = trim($_POST['hostel_address']);
    $hostel_description = trim($_POST['hostel_description']);
    $owner_id = intval($_POST['owner_id'] ?? 1);
    $gender = trim($_POST['gender'] ?? 'Mixed');
    $distance = trim($_POST['distance'] ?? '');
    $manager_phone = trim($_POST['manager_phone'] ?? '');
    $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? floatval($_POST['rating']) : null;
    $location_lat = trim($_POST['location_lat'] ?? '');
    $location_lng = trim($_POST['location_lng'] ?? '');
    $amenities_arr = array_map('trim', explode(',', $_POST['amenities'] ?? 'Security,Water'));
    $amenities_json = json_encode(array_values(array_filter($amenities_arr)));

    $image_path = null;
    
    if (isset($_FILES['hostel_image']) && $_FILES['hostel_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['hostel_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('hostel_', true) . '.' . $ext;
        $target = '../../assets/images/hostels/' . $filename;
        $relative_path = 'assets/images/hostels/' . $filename; // Store relative path in DB
        
        // Create directory if it doesn't exist
        $upload_dir = dirname($target);
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if (move_uploaded_file($_FILES['hostel_image']['tmp_name'], $target)) {
            $image_path = $relative_path; // Store relative path
        }
    }
    
    if ($business_id && $branch_id && $hostel_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO hostels (business_id, branch_id, owner_id, name, address, description, gender, distance, manager_phone, rating, location_lat, location_lng, amenities_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiissssssdsss', $business_id, $branch_id, $owner_id, $hostel_name, $hostel_address, $hostel_description, $gender, $distance, $manager_phone, $rating, $location_lat, $location_lng, $amenities_json);
        mysqli_stmt_execute($stmt);
        $hostel_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        if ($image_path && $hostel_id) {
            $stmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $hostel_id, $image_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: hostels.php');
        exit;
    }
}

// Handle Edit Hostel
if (isset($_POST['edit_hostel'])) {
    $hostel_id = intval($_POST['hostel_id']);
    $hostel_name = trim($_POST['hostel_name']);
    $hostel_address = trim($_POST['hostel_address']);
    $hostel_description = trim($_POST['hostel_description']);
    
    $gender = trim($_POST['gender'] ?? 'Mixed');
    $distance = trim($_POST['distance'] ?? '');
    $manager_phone = trim($_POST['manager_phone'] ?? '');
    $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? floatval($_POST['rating']) : null;
    $location_lat = trim($_POST['location_lat'] ?? '');
    $location_lng = trim($_POST['location_lng'] ?? '');
    $amenities_arr = array_map('trim', explode(',', $_POST['amenities'] ?? 'Security,Water'));
    $amenities_json = json_encode(array_values(array_filter($amenities_arr)));
    
    $image_path = null;
    
    if (isset($_FILES['hostel_image']) && $_FILES['hostel_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['hostel_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('hostel_', true) . '.' . $ext;
        $target = '../../assets/images/hostels/' . $filename;
        $relative_path = 'assets/images/hostels/' . $filename;
        
        // Create directory if it doesn't exist
        $upload_dir = dirname($target);
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        if (move_uploaded_file($_FILES['hostel_image']['tmp_name'], $target)) {
            $image_path = $relative_path;
        }
    }
    
    $owner_id = intval($_POST['owner_id'] ?? 1);
    if ($hostel_id && $hostel_name) {
        $stmt = mysqli_prepare($conn, "UPDATE hostels SET name = ?, address = ?, description = ?, owner_id = ?, gender = ?, distance = ?, manager_phone = ?, rating = ?, location_lat = ?, location_lng = ?, amenities_json = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sssissssdssi', $hostel_name, $hostel_address, $hostel_description, $owner_id, $gender, $distance, $manager_phone, $rating, $location_lat, $location_lng, $amenities_json, $hostel_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($image_path) {
            // Soft delete old images
            $stmt = mysqli_prepare($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $hostel_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            // Insert new image
            $stmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $hostel_id, $image_path);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: hostels.php');
        exit;
    }
}

// Handle Delete Hostel
if (isset($_POST['delete_hostel'])) {
    $hostel_id = intval($_POST['hostel_id']);
    if ($hostel_id) {
        $stmt = mysqli_prepare($conn, "UPDATE hostels SET deleted_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $hostel_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Soft delete images
        $stmt = mysqli_prepare($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $hostel_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: hostels.php');
        exit;
    }
}

// Fetch all hostels with images, business, and branch info
$hostels = [];
$query = "SELECT h.*, hi.image_path, b.name AS business_name, br.name AS branch_name, ou.name AS owner_name, ou.email AS owner_email
FROM hostels h
LEFT JOIN hostel_images hi ON h.id = hi.hostel_id AND hi.deleted_at IS NULL
LEFT JOIN business b ON h.business_id = b.id
LEFT JOIN branch br ON h.branch_id = br.id
LEFT JOIN users ou ON h.owner_id = ou.id
WHERE h.deleted_at IS NULL ORDER BY h.id DESC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $hostels[] = $row;
}

// Fetch all businesses and all branches for dropdowns
$all_businesses = [];
$businesses_result = mysqli_query($conn, "SELECT id, name FROM business WHERE deleted_at IS NULL");
while ($b = mysqli_fetch_assoc($businesses_result)) {
    $all_businesses[] = $b;
}

$all_branches = [];
$branches_result = mysqli_query($conn, "SELECT id, business_id, name FROM branch WHERE deleted_at IS NULL");
while ($br = mysqli_fetch_assoc($branches_result)) {
    $all_branches[] = $br;
}

$owner_users = [];
$owners_result = mysqli_query($conn, "
    SELECT DISTINCT u.id, u.name, u.email FROM users u
    WHERE u.deleted_at IS NULL
      AND (u.user_type = 'hostel_owner' OR u.id IN (SELECT DISTINCT owner_id FROM hostels WHERE deleted_at IS NULL))
    ORDER BY u.name
");
while ($owners_result && ($ou = mysqli_fetch_assoc($owners_result))) {
    $owner_users[] = $ou;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hostels</title>
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
        .image-placeholder {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 2px dashed #dee2e6;
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <h2 class="responsive-title mb-0"><i class="bi bi-house-door"></i> Hostels</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHostelModal"><i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Add Hostel</span></button>
                    <a class="btn btn-outline-primary" href="./manage_hostel_owners.php">
                        <i class="bi bi-person-lines-fill"></i> <span>Create Owner</span>
                    </a>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($hostels as $hostel): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card responsive-card shadow-sm">
                            <?php 
                            $correctImagePath = getCorrectImagePath($hostel['image_path']);
                            if (!empty($correctImagePath)): 
                            ?>
                                <img src="<?= htmlspecialchars($correctImagePath) ?>" 
                                     class="card-img-top" 
                                     alt="Hostel Image" 
                                     style="height: 180px; object-fit: cover;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div class="image-placeholder" style="display: none; height: 180px; line-height: 140px;">
                                    <i class="bi bi-image" style="font-size: 2rem; color: #6c757d;"></i>
                                    <p class="mb-0 text-muted">Image not found</p>
                                </div>
                            <?php else: ?>
                                <div class="image-placeholder" style="height: 180px; line-height: 140px;">
                                    <i class="bi bi-image" style="font-size: 2rem; color: #6c757d;"></i>
                                    <p class="mb-0 text-muted">No image</p>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title mb-2 responsive-title"><?= htmlspecialchars($hostel['name']) ?></h5>
                                <p class="card-text responsive-text mb-1"><small class="text-muted">Business: <?= htmlspecialchars($hostel['business_name']) ?> | Branch: <?= htmlspecialchars($hostel['branch_name']) ?></small></p>
                                <p class="card-text responsive-text mb-1"><small class="text-muted">Owner: <?= htmlspecialchars(($hostel['owner_name'] ?? '') ?: '—') ?><?= !empty($hostel['owner_email']) ? ' (' . htmlspecialchars($hostel['owner_email']) . ')' : '' ?></small></p>
                                <p class="card-text responsive-text mb-1"><small class="text-muted">Address: <?= htmlspecialchars($hostel['address']) ?></small></p>
                                <p class="card-text responsive-text mb-2"><small class="text-muted">Description: <?= htmlspecialchars($hostel['description']) ?></small></p>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewHostelModal<?= $hostel['id'] ?>">
                                        <i class="bi bi-eye"></i> <span class="d-none d-sm-inline ms-1">View</span>
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editHostelModal<?= $hostel['id'] ?>">
                                        <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline ms-1">Edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteHostelModal<?= $hostel['id'] ?>">
                                        <i class="bi bi-trash"></i> <span class="d-none d-sm-inline ms-1">Delete</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- View Hostel Modal -->
                    <div class="modal fade" id="viewHostelModal<?= $hostel['id'] ?>" tabindex="-1" aria-labelledby="viewHostelModalLabel<?= $hostel['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewHostelModalLabel<?= $hostel['id'] ?>">Hostel Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body text-center">
                            <?php 
                            $correctImagePath = getCorrectImagePath($hostel['image_path']);
                            if (!empty($correctImagePath)): 
                            ?>
                              <img src="<?= htmlspecialchars($correctImagePath) ?>" 
                                   alt="Hostel Image" 
                                   style="width: 100%; max-width: 320px; height: auto; object-fit: cover; border-radius: 8px; margin-bottom: 1rem;"
                                   onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                              <div class="image-placeholder" style="display: none;">
                                  <i class="bi bi-image" style="font-size: 2rem; color: #6c757d;"></i>
                                  <p class="mb-0 text-muted">Image not found</p>
                              </div>
                            <?php else: ?>
                              <div class="image-placeholder">
                                  <i class="bi bi-image" style="font-size: 2rem; color: #6c757d;"></i>
                                  <p class="mb-0 text-muted">No image available</p>
                              </div>
                            <?php endif; ?>
                            <!-- Debug info (remove in production) -->
                            <?php if (!empty($hostel['image_path'])): ?>
                                <div class="alert alert-info" style="font-size: 0.8em;">
                                    <strong>Debug Info:</strong><br>
                                    Original Path: <?= htmlspecialchars($hostel['image_path']) ?><br>
                                    Corrected Path: <?= htmlspecialchars($correctImagePath) ?><br>
                                    File Exists: <?= file_exists(str_replace('../../', '', $correctImagePath ?: '')) ? 'YES' : 'NO' ?>
                                </div>
                            <?php endif; ?>
                            <h5><?= htmlspecialchars($hostel['name']) ?></h5>
                            <p class="mb-1"><strong>Business:</strong> <?= htmlspecialchars($hostel['business_name']) ?></p>
                            <p class="mb-1"><strong>Branch:</strong> <?= htmlspecialchars($hostel['branch_name']) ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($hostel['address']) ?></p>
                            <p class="mb-1"><strong>Description:</strong> <?= htmlspecialchars($hostel['description']) ?></p>
                            <hr class="my-2">
                            <p class="mb-1"><strong>Gender:</strong> <?= htmlspecialchars($hostel['gender'] ?? 'Mixed') ?></p>
                            <p class="mb-1"><strong>Distance:</strong> <?= htmlspecialchars((string)($hostel['distance'] ?? '—')) ?></p>
                            <p class="mb-1"><strong>Manager Phone:</strong> <?= htmlspecialchars((string)($hostel['manager_phone'] ?? '—')) ?></p>
                            <p class="mb-1"><strong>Rating:</strong> <?= htmlspecialchars((string)($hostel['rating'] ?? 'N/A')) ?> / 5.0</p>
                            <p class="mb-1"><strong>Amenities:</strong> 
                                <?php 
                                    $arr = json_decode($hostel['amenities_json'] ?? '[]', true);
                                    if(!is_array($arr)) $arr = [];
                                    echo htmlspecialchars(implode(', ', $arr) ?: 'None');
                                ?>
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Edit Hostel Modal -->
                    <div class="modal fade" id="editHostelModal<?= $hostel['id'] ?>" tabindex="-1" aria-labelledby="editHostelModalLabel<?= $hostel['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="post" action="" enctype="multipart/form-data">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editHostelModalLabel<?= $hostel['id'] ?>">Edit Hostel</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                              <div class="mb-3">
                                <label class="form-label">Hostel owner</label>
                                <select class="form-select" name="owner_id" required>
                                  <?php foreach ($owner_users as $ou): ?>
                                    <option value="<?= (int)$ou['id'] ?>" <?= ((int)($hostel['owner_id'] ?? 0) === (int)$ou['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ou['name'] . ' — ' . $ou['email']) ?></option>
                                  <?php endforeach; ?>
                                  <?php
                                  $curOid = (int) ($hostel['owner_id'] ?? 0);
                                  $ids = array_column($owner_users, 'id');
                                  if ($curOid && !in_array($curOid, array_map('intval', $ids), true)) {
                                      $ou = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT id, name, email FROM users WHERE id = ' . $curOid . ' LIMIT 1'));
                                      if ($ou) {
                                          echo '<option value="' . (int) $ou['id'] . '" selected>' . htmlspecialchars($ou['name'] . ' — ' . $ou['email']) . '</option>';
                                      }
                                  }
                                  ?>
                                </select>
                              </div>
                              <div class="mb-3">
                                <label for="hostel_name_edit<?= $hostel['id'] ?>" class="form-label">Hostel Name</label>
                                <input type="text" class="form-control" id="hostel_name_edit<?= $hostel['id'] ?>" name="hostel_name" value="<?= htmlspecialchars($hostel['name']) ?>" required>
                              </div>
                              <div class="mb-3">
                                <label for="hostel_address_edit<?= $hostel['id'] ?>" class="form-label">Address</label>
                                <input type="text" class="form-control" id="hostel_address_edit<?= $hostel['id'] ?>" name="hostel_address" value="<?= htmlspecialchars($hostel['address']) ?>">
                              </div>
                              <div class="mb-3">
                                <label for="hostel_description_edit<?= $hostel['id'] ?>" class="form-label">Description</label>
                                <textarea class="form-control" id="hostel_description_edit<?= $hostel['id'] ?>" name="hostel_description"><?= htmlspecialchars($hostel['description']) ?></textarea>
                              </div>
                              <div class="row">
                                <?php $curGender = $hostel['gender'] ?? 'Mixed'; ?>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="Mixed" <?= $curGender === 'Mixed' ? 'selected' : '' ?>>Mixed</option>
                                        <option value="Male" <?= $curGender === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $curGender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Distance</label>
                                    <input type="text" class="form-control" name="distance" value="<?= htmlspecialchars($hostel['distance'] ?? '') ?>" placeholder="e.g. 5 mins walk">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Manager Phone</label>
                                    <input type="text" class="form-control" name="manager_phone" value="<?= htmlspecialchars($hostel['manager_phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rating (0-5)</label>
                                    <input type="number" step="0.1" max="5" min="0" class="form-control" name="rating" value="<?= htmlspecialchars($hostel['rating'] ?? '') ?>" placeholder="0.0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude</label>
                                    <input type="text" class="form-control" name="location_lat" value="<?= htmlspecialchars($hostel['location_lat'] ?? '') ?>" placeholder="0.6591">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude</label>
                                    <input type="text" class="form-control" name="location_lng" value="<?= htmlspecialchars($hostel['location_lng'] ?? '') ?>" placeholder="30.2752">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Amenities</label>
                                    <?php 
                                        $arr = json_decode($hostel['amenities_json'] ?? '[]', true); 
                                        if(!is_array($arr)) $arr = [];
                                        $amenStr = implode(', ', $arr);
                                    ?>
                                    <input type="text" class="form-control" name="amenities" value="<?= htmlspecialchars($amenStr) ?>" placeholder="e.g. Security,Water,WiFi (Comma separated)">
                                </div>
                              </div>
                              <div class="mb-3">
                                <label for="hostel_image_edit<?= $hostel['id'] ?>" class="form-label">Change Image (optional)</label>
                                <input type="file" class="form-control" id="hostel_image_edit<?= $hostel['id'] ?>" name="hostel_image" accept="image/*">
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              <button type="submit" name="edit_hostel" class="btn btn-warning">Save changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                    <!-- Delete Hostel Modal -->
                    <div class="modal fade" id="deleteHostelModal<?= $hostel['id'] ?>" tabindex="-1" aria-labelledby="deleteHostelModalLabel<?= $hostel['id'] ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content modal-confirm">
                          <form method="post" action="">
                            <div class="modal-body">
                              <i class="bi bi-exclamation-triangle-fill"></i>
                              <h5 class="modal-title mb-3" id="deleteHostelModalLabel<?= $hostel['id'] ?>">Delete Hostel</h5>
                              <input type="hidden" name="hostel_id" value="<?= $hostel['id'] ?>">
                              <p>Are you sure you want to delete <strong><?= htmlspecialchars($hostel['name']) ?></strong>?</p>
                            </div>
                            <div class="modal-footer justify-content-center">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" name="delete_hostel" class="btn btn-danger">Delete</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Add Hostel Modal -->
            <div class="modal fade" id="addHostelModal" tabindex="-1" aria-labelledby="addHostelModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addHostelModalLabel">Add Hostel</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label for="business_id" class="form-label">Business</label>
                        <select class="form-select" id="business_id" name="business_id" required>
                          <option value="">Select Business</option>
                          <?php foreach ($all_businesses as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id" required>
                          <option value="">Select Branch</option>
                          <?php foreach ($all_branches as $br): ?>
                            <option value="<?= $br['id'] ?>" data-business="<?= $br['business_id'] ?>"><?= htmlspecialchars($br['name']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="owner_id" class="form-label">Hostel owner</label>
                        <select class="form-select" id="owner_id" name="owner_id" required>
                          <option value="">Select owner</option>
                          <?php foreach ($owner_users as $ou): ?>
                            <option value="<?= (int)$ou['id'] ?>"><?= htmlspecialchars($ou['name'] . ' — ' . $ou['email']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Create accounts under Hostel owners in the dashboard if empty.</small>
                      </div>
                      <div class="mb-3">
                        <label for="hostel_name" class="form-label">Hostel Name</label>
                        <input type="text" class="form-control" id="hostel_name" name="hostel_name" required>
                      </div>
                      <div class="mb-3">
                        <label for="hostel_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="hostel_address" name="hostel_address">
                      </div>
                      <div class="mb-3">
                        <label for="hostel_description" class="form-label">Description</label>
                        <textarea class="form-control" id="hostel_description" name="hostel_description"></textarea>
                      </div>
                      <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="Mixed">Mixed</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="distance" class="form-label">Distance</label>
                            <input type="text" class="form-control" id="distance" name="distance" placeholder="e.g. 5 mins walk">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="manager_phone" class="form-label">Manager Phone</label>
                            <input type="text" class="form-control" id="manager_phone" name="manager_phone">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="rating" class="form-label">Rating (0-5)</label>
                            <input type="number" step="0.1" max="5" min="0" class="form-control" id="rating" name="rating" placeholder="0.0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location_lat" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="location_lat" name="location_lat" placeholder="0.6591">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location_lng" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="location_lng" name="location_lng" placeholder="30.2752">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Amenities</label>
                            <input type="text" class="form-control" name="amenities" placeholder="e.g. Security,Water,WiFi (Comma separated)">
                        </div>
                      </div>
                      <div class="mb-3">
                        <label for="hostel_image" class="form-label">Hostel Image</label>
                        <input type="file" class="form-control" id="hostel_image" name="hostel_image" accept="image/*">
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" name="add_hostel" class="btn btn-primary">Add Hostel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Filter branches by selected business
    const businessSelect = document.getElementById('business_id');
    const branchSelect = document.getElementById('branch_id');
    if (businessSelect && branchSelect) {
      businessSelect.addEventListener('change', function() {
        const businessId = this.value;
        Array.from(branchSelect.options).forEach(option => {
          if (!option.value) return; // skip placeholder
          option.style.display = option.getAttribute('data-business') === businessId ? '' : 'none';
        });
        branchSelect.value = '';
      });
    }
    </script>
</body>
</html>