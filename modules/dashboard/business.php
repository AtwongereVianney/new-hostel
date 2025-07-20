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
    if ($business_id && $branch_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO branch (business_id, name, location) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iss', $business_id, $branch_name, $branch_location);
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
    if ($branch_id && $branch_name) {
        $stmt = mysqli_prepare($conn, "UPDATE branch SET name = ?, location = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $branch_name, $branch_location, $branch_id);
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
