<?php
require_once '../../config/db.php';
$hostel_id = isset($_GET['hostel_id']) ? intval($_GET['hostel_id']) : 0;
$business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

$query = "SELECT * FROM rooms WHERE hostel_id = ? AND business_id = ? AND branch_id = ? AND deleted_at IS NULL";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'iii', $hostel_id, $business_id, $branch_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h2>Rooms</h2>
    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Room <?= htmlspecialchars($row['room_number']) ?></h5>
                        <p>Type: <?= htmlspecialchars($row['type']) ?></p>
                        <p>Price: <?= htmlspecialchars($row['price']) ?></p>
                        <p>Status: <?= htmlspecialchars($row['status']) ?></p>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>
