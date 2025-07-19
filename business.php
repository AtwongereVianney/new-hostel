<?php
require_once 'config/db.php';

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
// Fetch all businesses
$query = "SELECT * FROM business WHERE deleted_at IS NULL";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
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
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 