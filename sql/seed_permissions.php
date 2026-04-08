<?php
require_once __DIR__ . '/../config/db.php';

$permissions = [
    'view_dashboard', 'view_reports', 'export_reports',
    'view_hostels', 'create_hostel', 'edit_hostel', 'delete_hostel',
    'assign_hostel_owner', 'view_hostel_details',
    'view_rooms', 'create_room', 'edit_room', 'delete_room', 'manage_rooms',
    'update_room_status', 'release_room', 'confirm_room_payment',
    'view_bookings', 'create_booking', 'edit_booking', 'cancel_booking', 'manage_bookings',
    'confirm_booking', 'verify_booking_payment',
    'view_users', 'create_user', 'edit_user', 'delete_user',
    'activate_user', 'suspend_user', 'manage_users',
    'view_managers', 'create_manager', 'edit_manager', 'delete_manager', 'manage_managers',
    'view_roles', 'create_role', 'edit_role', 'delete_role', 'assign_role',
    'view_permissions', 'create_permission', 'edit_permission', 'delete_permission', 'assign_permission',
    'view_payments', 'initiate_payment', 'verify_payment', 'refund_payment', 'manage_payments',
    'view_audit_logs', 'manage_security_settings',
    'manage_business_settings', 'manage_branch_settings', 'system_admin',
];

$businessId = 1;
$branchId = 1;
$added = 0;

foreach ($permissions as $name) {
    $check = mysqli_prepare(
        $conn,
        "SELECT id FROM permissions WHERE business_id = ? AND branch_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1"
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
        "INSERT INTO permissions (business_id, branch_id, name) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert, 'iis', $businessId, $branchId, $name);
    if (mysqli_stmt_execute($insert)) {
        $added++;
    }
    mysqli_stmt_close($insert);
}

echo json_encode([
    'success' => true,
    'total' => count($permissions),
    'added' => $added,
    'skipped_existing' => count($permissions) - $added,
], JSON_PRETTY_PRINT) . PHP_EOL;

