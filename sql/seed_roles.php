<?php
require_once __DIR__ . '/../config/db.php';

$roles = [
    'super_admin',
    'admin',
    'hostel_manager',
    'booking_officer',
    'finance_officer',
    'student',
];

$businessId = 1;
$branchId = 1;
$added = 0;

foreach ($roles as $name) {
    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM roles WHERE business_id = ? AND branch_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1"
    );
    mysqli_stmt_bind_param($check, 'iis', $businessId, $branchId, $name);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    $exists = mysqli_stmt_num_rows($check) > 0;
    mysqli_stmt_close($check);

    if ($exists) {
        continue;
    }

    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO roles (business_id, branch_id, name) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert, 'iis', $businessId, $branchId, $name);
    if (mysqli_stmt_execute($insert)) {
        $added++;
    }
    mysqli_stmt_close($insert);
}

echo json_encode([
    'success' => true,
    'total' => count($roles),
    'added' => $added,
    'skipped_existing' => count($roles) - $added,
], JSON_PRETTY_PRINT) . PHP_EOL;

