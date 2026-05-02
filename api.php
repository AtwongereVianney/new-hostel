<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mailer.php';
ensureHostelExtendedColumns($conn);
ensureBookingsExtendedColumns($conn);
ensureSystemSettingsTable($conn);

// IMPORTANT: Replace this with your LIVE Flutterwave Secret Key (FLWSECK-XXXX)
define('FLW_SECRET_KEY', 'FLWSECK_TEST-sandbox-secret-key-placeholder');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path ?? '', '/');

// Robust endpoint detection: works even if the app lives under a parent path
// like "/mmu-hostel%20solutions/new-hostel/api.php/hostels".
$endpoint = '';
$parts = explode('/', trim($path, '/'));
$idx = array_search('api.php', $parts, true);
if ($idx !== false) {
    $endpoint = $parts[$idx + 1] ?? '';
}
$endpoint = ltrim(trim($endpoint), '/');

// Route the request
switch ($endpoint) {
    case 'hostels':
        handleHostels($method, $conn);
        break;
    case 'bookings':
        handleBookings($method, $conn);
        break;
    case 'rooms':
        handleRooms($method, $conn);
        break;
    case 'users':
        handleUsers($method, $conn);
        break;
    case 'login':
        handleLogin($method, $conn);
        break;
    case 'roles':
        handleRoles($method, $conn);
        break;
    case 'permissions':
        handlePermissions($method, $conn);
        break;
    case 'pay':
        handlePayment($method, $conn);
        break;
    case 'verify-payment':
        handlePaymentVerification($method, $conn);
        break;
    case 'booking-approval':
        handleBookingApproval($method, $conn);
        break;
    case 'settings':
        handleSettings($method, $conn);
        break;
    case 'support':
        handleSupportRequest($method, $conn);
        break;
    case 'role-permissions':
        handleRolePermissions($method, $conn);
        break;
    case '':
        echo json_encode(['status' => 'API is running', 'endpoints' => ['hostels', 'bookings', 'users', 'roles', 'permissions', 'login', 'booking-approval', 'pay', 'verify-payment', 'settings', 'support']]);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'endpoint' => $endpoint, 'path' => $path]);
        break;
}

function handleLogin($method, $conn) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim((string)($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');
    if ($email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $stmt = mysqli_prepare($conn, "
        SELECT u.id, u.name, u.email, u.phone, u.password, u.user_type, u.role_id, u.permissions_json, u.deleted_at,
               r.name AS role_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.email = ? AND u.deleted_at IS NULL
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$user || !empty($user['deleted_at']) || !password_verify($password, (string)$user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        return;
    }

    $permissions = json_decode($user['permissions_json'] ?? '{}', true);
    if (!is_array($permissions)) $permissions = [];

    $assignedHostels = [];
    if (($user['user_type'] ?? '') === 'hostel_owner') {
        $ownerId = (int)$user['id'];
        $hRes = mysqli_query($conn, "
            SELECT id
            FROM hostels
            WHERE owner_id = {$ownerId} AND deleted_at IS NULL
            ORDER BY id ASC
        ");
        while ($hRes && ($h = mysqli_fetch_assoc($hRes))) {
            $assignedHostels[] = (int)$h['id'];
        }
    }

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'user_type' => $user['user_type'] ?? 'student',
            'role_id' => isset($user['role_id']) ? (int)$user['role_id'] : null,
            'role_name' => $user['role_name'] ?? '',
            'permissions' => $permissions,
            'assigned_hostel_ids' => $assignedHostels,
        ]
    ]);
}

function handleBookingApproval($method, $conn) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim((string)($data['email'] ?? ''));
    $studentName = trim((string)($data['studentName'] ?? 'Student'));
    $regNo = trim((string)($data['regNo'] ?? ''));
    $hostelName = trim((string)($data['hostelName'] ?? ''));
    $roomNumber = trim((string)($data['roomNumber'] ?? ''));

    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Student email is required']);
        return;
    }

    $plainPassword = 'MMU' . rand(1000, 9999) . '!';
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $userId = 0;

    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($check, 's', $email);
    mysqli_stmt_execute($check);
    $res = mysqli_stmt_get_result($check);
    $existing = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($check);

    if ($existing) {
        $userId = (int)$existing['id'];
        $up = mysqli_prepare($conn, "UPDATE users SET name = ?, user_type = 'student', password = ?, deleted_at = NULL WHERE id = ?");
        mysqli_stmt_bind_param($up, 'ssi', $studentName, $hash, $userId);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    } else {
        $businessId = 1;
        $branchId = 1;
        $phone = '';
        $roleId = null;
        $permissionsJson = json_encode([]);
        $ins = mysqli_prepare($conn, "
            INSERT INTO users (business_id, branch_id, name, email, password, phone, role_id, user_type, permissions_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'student', ?)
        ");
        mysqli_stmt_bind_param($ins, 'iissssis', $businessId, $branchId, $studentName, $email, $hash, $phone, $roleId, $permissionsJson);
        mysqli_stmt_execute($ins);
        $userId = (int)mysqli_insert_id($conn);
        mysqli_stmt_close($ins);
    }

    $mailRes = mmu_send_student_credentials_email($email, $studentName, $hostelName, $roomNumber, $regNo, $plainPassword);
    $mailSent = !empty($mailRes['success']);

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'email_sent' => (bool)$mailSent,
        'email_error' => $mailSent ? null : ($mailRes['error'] ?? 'Unknown PHPMailer error'),
        'email' => $email,
    ]);
}

function handleSupportRequest($method, $conn) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim((string)($data['email'] ?? ''));
    $name = trim((string)($data['name'] ?? 'User'));
    $subject = trim((string)($data['subject'] ?? 'No Subject'));
    $message = trim((string)($data['message'] ?? ''));

    if ($email === '' || $message === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email and message are required']);
        return;
    }

    $sysEmailRes = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'supportEmail'");
    $sysEmailRow = $sysEmailRes ? mysqli_fetch_assoc($sysEmailRes) : null;
    $supportEmail = ($sysEmailRow && !empty($sysEmailRow['setting_value'])) ? $sysEmailRow['setting_value'] : 'devSupport@mmu.ac.ug';

    $mailRes = mmu_send_support_ticket_email($email, $name, $subject, $message, $supportEmail);
    $mailSent = !empty($mailRes['success']);

    echo json_encode([
        'success' => !!$mailSent,
        'email_sent' => (bool)$mailSent,
        'error' => $mailSent ? null : ($mailRes['error'] ?? 'Unknown PHPMailer error'),
    ]);
}

function ensureHostelExtendedColumns($conn) {
    $needed = [
        "gender VARCHAR(20) NULL",
        "distance VARCHAR(120) NULL",
        "manager_phone VARCHAR(25) NULL",
        "rating DECIMAL(3,1) NULL",
        "amenities_json TEXT NULL",
        "location_lat VARCHAR(30) NULL",
        "location_lng VARCHAR(30) NULL"
    ];
    foreach ($needed as $colDef) {
        $col = explode(' ', $colDef)[0];
        $res = mysqli_query($conn, "SHOW COLUMNS FROM hostels LIKE '{$col}'");
        if (!$res || mysqli_num_rows($res) === 0) {
            mysqli_query($conn, "ALTER TABLE hostels ADD COLUMN {$colDef}");
        }
    }
}

function ensureBookingsExtendedColumns($conn) {
    $needed = [
        "reference_no VARCHAR(30) NULL",
        "reg_no VARCHAR(60) NULL",
        "course VARCHAR(150) NULL",
        "semester VARCHAR(50) NULL",
        "academic_year VARCHAR(30) NULL",
        "balance_paid DECIMAL(10,2) DEFAULT 0",
        "student_phone VARCHAR(50) NULL"
    ];
    foreach ($needed as $colDef) {
        $col = explode(' ', $colDef)[0];
        $res = mysqli_query($conn, "SHOW COLUMNS FROM bookings LIKE '{$col}'");
        if (!$res || mysqli_num_rows($res) === 0) {
            mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN {$colDef}");
        }
    }
    // Ensure UNIQUE index on reference_no if it doesn't exist
    $res = mysqli_query($conn, "SHOW INDEX FROM bookings WHERE Column_name = 'reference_no'");
    if ($res && mysqli_num_rows($res) === 0) {
        // We only add UNIQUE if columns are populated or if table is empty to avoid collisions
        // For simplicity in this env, we just try to add it.
        mysqli_query($conn, "ALTER TABLE bookings ADD UNIQUE (reference_no)");
    }
}

/**
 * Decode base64 room image from JSON sync, validate, store under assets/images/rooms/, link in room_images.
 * @param array $uploadPayload ['base64' => string, 'filename' => string]
 * @return ?string Relative path e.g. assets/images/rooms/room_xxx.jpg or null on failure
 */
function mmu_save_room_image_upload($conn, $roomId, $uploadPayload) {
    $roomId = (int)$roomId;
    if ($roomId <= 0 || !is_array($uploadPayload)) {
        return null;
    }
    $raw = trim((string)($uploadPayload['base64'] ?? ''));
    if ($raw === '') {
        return null;
    }
    if (preg_match('/^data:image\/[\w+]+;base64,(.+)$/i', $raw, $m)) {
        $raw = $m[1];
    }
    $raw = preg_replace('/\s+/', '', $raw);
    $bin = base64_decode($raw, true);
    if ($bin === false || strlen($bin) < 32) {
        return null;
    }
    if (strlen($bin) > 2 * 1024 * 1024) {
        return null;
    }
    $info = @getimagesizefromstring($bin);
    if ($info === false) {
        return null;
    }
    $mime = $info['mime'] ?? '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? null;
    if ($ext === null) {
        return null;
    }

    $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '', (string)($uploadPayload['filename'] ?? 'room.jpg'));
    $fnExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($fnExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $filename = 'room.' . $ext;
    }

    $safeName = 'room_' . uniqid('', true) . '.' . $ext;
    $relPath = 'assets/images/rooms/' . $safeName;
    $fullPath = __DIR__ . '/' . $relPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
    }
    if (file_put_contents($fullPath, $bin) === false) {
        return null;
    }

    $stmt = mysqli_prepare($conn, "UPDATE room_images SET deleted_at = NOW() WHERE room_id = ? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $roomId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'is', $roomId, $relPath);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $relPath;
}

/**
 * Decode base64 hostel image from JSON sync, validate, store under assets/images/hostels/, link in hostel_images.
 * @param array $uploadPayload ['base64' => string, 'filename' => string]
 * @return ?string Relative path e.g. assets/images/hostels/hostel_xxx.jpg or null on failure
 */
function mmu_save_hostel_image_upload($conn, $hostelId, $uploadPayload) {
    $hostelId = (int)$hostelId;
    if ($hostelId <= 0 || !is_array($uploadPayload)) {
        return null;
    }
    $raw = trim((string)($uploadPayload['base64'] ?? ''));
    if ($raw === '') {
        return null;
    }
    // Handle data URL prefix if present
    if (preg_match('/^data:image\/[\w+]+;base64,(.+)$/i', $raw, $m)) {
        $raw = $m[1];
    }
    $raw = preg_replace('/\s+/', '', $raw);
    $bin = base64_decode($raw, true);
    if ($bin === false || strlen($bin) < 32) {
        return null;
    }
    if (strlen($bin) > 5 * 1024 * 1024) { // 5MB limit for hostels
        return null;
    }
    $info = @getimagesizefromstring($bin);
    if ($info === false) {
        return null;
    }
    $mime = $info['mime'] ?? '';
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? null;
    if ($ext === null) {
        return null;
    }

    $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '', (string)($uploadPayload['filename'] ?? 'hostel.jpg'));
    $fnExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($fnExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $filename = 'hostel.' . $ext;
    }

    $safeName = 'hostel_' . uniqid('', true) . '.' . $ext;
    $relPath = 'assets/images/hostels/' . $safeName;
    $fullPath = __DIR__ . '/' . $relPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }
    }
    if (file_put_contents($fullPath, $bin) === false) {
        return null;
    }

    // Soft delete old images for this hostel
    $stmt = mysqli_prepare($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = ? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $hostelId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Insert new image
    $stmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'is', $hostelId, $relPath);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $relPath;
}

function handleRoles($method, $conn) {
    switch ($method) {
        case 'GET':
            $rows = [];
            $res = mysqli_query($conn, "
                SELECT id, name, business_id, branch_id
                FROM roles
                WHERE deleted_at IS NULL
                ORDER BY name ASC
            ");
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $rows[] = [
                    'id' => (int)$r['id'],
                    'name' => $r['name'] ?? '',
                    'business_id' => (int)($r['business_id'] ?? 0),
                    'branch_id' => (int)($r['branch_id'] ?? 0),
                ];
            }
            echo json_encode($rows);
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $name = trim((string)($data['name'] ?? ''));
            $businessId = (int)($data['business_id'] ?? 1);
            $branchId = (int)($data['branch_id'] ?? 1);
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Role name is required']);
                return;
            }
            $stmt = mysqli_prepare($conn, "INSERT INTO roles (business_id, branch_id, name) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iis', $businessId, $branchId, $name);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'id' => (int)mysqli_insert_id($conn)]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not create role']);
            }
            mysqli_stmt_close($stmt);
            break;
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid id']);
                return;
            }
            $stmt = mysqli_prepare($conn, "UPDATE roles SET deleted_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not delete role']);
            }
            mysqli_stmt_close($stmt);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}


function handlePermissions($method, $conn) {
    switch ($method) {
        case 'GET':
            $rows = [];
            $res = mysqli_query($conn, "
                SELECT id, name, business_id, branch_id
                FROM permissions
                WHERE deleted_at IS NULL
                ORDER BY name ASC
            ");
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $rows[] = [
                    'id' => (int)$r['id'],
                    'name' => $r['name'] ?? '',
                    'business_id' => (int)($r['business_id'] ?? 0),
                    'branch_id' => (int)($r['branch_id'] ?? 0),
                ];
            }
            echo json_encode($rows);
            break;
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $businessId = (int)($data['business_id'] ?? 1);
            $branchId = (int)($data['branch_id'] ?? 1);

            // Bulk seed mode: { seed_defaults: true }
            if (!empty($data['seed_defaults'])) {
                $defaultPermissions = [
                    // Dashboard & analytics
                    'view_dashboard', 'view_reports', 'export_reports',
                    // Hostel management
                    'view_hostels', 'create_hostel', 'edit_hostel', 'delete_hostel',
                    'assign_hostel_owner', 'view_hostel_details',
                    // Room management
                    'view_rooms', 'create_room', 'edit_room', 'delete_room', 'manage_rooms',
                    'update_room_status', 'release_room', 'confirm_room_payment',
                    // Booking management
                    'view_bookings', 'create_booking', 'edit_booking', 'cancel_booking', 'manage_bookings',
                    'confirm_booking', 'verify_booking_payment',
                    // User & manager management
                    'view_users', 'create_user', 'edit_user', 'delete_user',
                    'activate_user', 'suspend_user', 'manage_users',
                    'view_managers', 'create_manager', 'edit_manager', 'delete_manager', 'manage_managers',
                    // Role & permission administration
                    'view_roles', 'create_role', 'edit_role', 'delete_role', 'assign_role',
                    'view_permissions', 'create_permission', 'edit_permission', 'delete_permission', 'assign_permission',
                    // Payments
                    'view_payments', 'initiate_payment', 'verify_payment', 'refund_payment', 'manage_payments',
                    // Audit & security
                    'view_audit_logs', 'manage_security_settings',
                    // System setup
                    'manage_business_settings', 'manage_branch_settings', 'system_admin',
                ];

                $added = 0;
                foreach ($defaultPermissions as $permName) {
                    $nameEsc = mysqli_real_escape_string($conn, $permName);
                    $check = mysqli_query($conn, "
                        SELECT id FROM permissions
                        WHERE business_id = {$businessId}
                          AND branch_id = {$branchId}
                          AND name = '{$nameEsc}'
                          AND deleted_at IS NULL
                        LIMIT 1
                    ");
                    if ($check && mysqli_num_rows($check) > 0) {
                        continue;
                    }
                    $stmt = mysqli_prepare($conn, "INSERT INTO permissions (business_id, branch_id, name) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'iis', $businessId, $branchId, $permName);
                    if (mysqli_stmt_execute($stmt)) {
                        $added++;
                    }
                    mysqli_stmt_close($stmt);
                }
                echo json_encode([
                    'success' => true,
                    'seeded' => count($defaultPermissions),
                    'added' => $added,
                    'skipped_existing' => count($defaultPermissions) - $added,
                ]);
                return;
            }

            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Permission name is required']);
                return;
            }
            $stmt = mysqli_prepare($conn, "INSERT INTO permissions (business_id, branch_id, name) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'iis', $businessId, $branchId, $name);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true, 'id' => (int)mysqli_insert_id($conn)]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not create permission']);
            }
            mysqli_stmt_close($stmt);
            break;
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid id']);
                return;
            }
            $stmt = mysqli_prepare($conn, "UPDATE permissions SET deleted_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not delete permission']);
            }
            mysqli_stmt_close($stmt);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleRooms($method, $conn) {
    switch ($method) {
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid room id']);
                return;
            }
            // 1. Soft-delete the room
            mysqli_query($conn, "UPDATE rooms SET deleted_at = NOW() WHERE id = $id");
            // 2. Soft-delete room images
            mysqli_query($conn, "UPDATE room_images SET deleted_at = NOW() WHERE room_id = $id");

            echo json_encode(['success' => true]);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleUsers($method, $conn) {
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id > 0) {
                $stmt = mysqli_prepare($conn, "
                    SELECT u.id, u.name, u.email, u.phone, u.user_type, u.role_id, u.permissions_json, u.deleted_at, u.created_at,
                           r.name AS role_name
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.id = ? AND u.deleted_at IS NULL
                    LIMIT 1
                ");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = $res ? mysqli_fetch_assoc($res) : null;
                mysqli_stmt_close($stmt);
                if (!$row) {
                    http_response_code(404);
                    echo json_encode(['error' => 'User not found']);
                    return;
                }

                $assignedHostels = [];
                if (($row['user_type'] ?? '') === 'hostel_owner') {
                    $ownerId = (int)$row['id'];
                    $hRes = mysqli_query($conn, "
                        SELECT id
                        FROM hostels
                        WHERE owner_id = {$ownerId} AND deleted_at IS NULL
                        ORDER BY id ASC
                    ");
                    while ($hRes && ($h = mysqli_fetch_assoc($hRes))) {
                        $assignedHostels[] = (int)$h['id'];
                    }
                }

                echo json_encode([
                    'id' => (int)$row['id'],
                    'name' => $row['name'] ?? '',
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'role' => $row['user_type'] ?? 'hostel_owner',
                    'role_id' => isset($row['role_id']) ? (int)$row['role_id'] : null,
                    'role_name' => $row['role_name'] ?? '',
                    'status' => empty($row['deleted_at']) ? 'active' : 'suspended',
                    'permissions' => json_decode($row['permissions_json'] ?? '{}', true) ?: [],
                    'assigned_hostel_ids' => $assignedHostels,
                    'created_at' => $row['created_at'] ?? null,
                ]);
                return;
            }

            $role = $_GET['role'] ?? 'hostel_owner';
            $roleEsc = mysqli_real_escape_string($conn, $role);
            $whereRole = ($roleEsc === 'all') ? '1=1' : "u.user_type = '{$roleEsc}'";
            $result = mysqli_query($conn, "
                SELECT u.id, u.name, u.email, u.phone, u.user_type, u.role_id, u.permissions_json, u.deleted_at, u.created_at,
                       r.name AS role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE {$whereRole} AND u.deleted_at IS NULL
                ORDER BY u.created_at DESC
            ");

            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'] ?? '',
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'role' => $row['user_type'] ?? 'hostel_owner',
                    'role_id' => isset($row['role_id']) ? (int)$row['role_id'] : null,
                    'role_name' => $row['role_name'] ?? '',
                    'status' => empty($row['deleted_at']) ? 'active' : 'suspended',
                    'permissions' => json_decode($row['permissions_json'] ?? '{}', true) ?: [],
                    'created_at' => $row['created_at'] ?? null,
                ];
            }
            echo json_encode($users);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data']);
                return;
            }

            $name = trim((string)($data['name'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $phone = trim((string)($data['phone'] ?? ''));
            $password = (string)($data['password'] ?? '');
            $role = trim((string)($data['role'] ?? 'hostel_owner'));
            $roleId = isset($data['role_id']) && (int)$data['role_id'] > 0 ? (int)$data['role_id'] : null;
            $businessId = (int)($data['business_id'] ?? 1);
            $branchId = (int)($data['branch_id'] ?? 1);
            $permissions = $data['permissions'] ?? [
                'view_hostels' => true,
                'edit_hostel' => true,
                'manage_rooms' => true,
                'view_bookings' => true,
                'manage_bookings' => false,
            ];

            if ($name === '' || $email === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Name and email are required']);
                return;
            }
            if ($password === '') {
                $password = 'MMU' . rand(1000, 9999) . '!';
            }
            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 6 characters']);
                return;
            }

            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($check, 's', $email);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);
            $exists = mysqli_stmt_num_rows($check) > 0;
            mysqli_stmt_close($check);
            if ($exists) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                return;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $permJson = json_encode($permissions);
            $stmt = mysqli_prepare($conn, "
                INSERT INTO users (business_id, branch_id, name, email, password, phone, role_id, user_type, permissions_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt, 'iissssiss', $businessId, $branchId, $name, $email, $hash, $phone, $roleId, $role, $permJson);
            if (mysqli_stmt_execute($stmt)) {
                $id = mysqli_insert_id($conn);
                
                if (isset($data['assigned_hostel_ids']) && is_array($data['assigned_hostel_ids'])) {
                     foreach ($data['assigned_hostel_ids'] as $hId) {
                         $hId = (int)$hId;
                         if ($hId > 0) {
                             $upd = mysqli_prepare($conn, "UPDATE hostels SET owner_id = ? WHERE id = ?");
                             mysqli_stmt_bind_param($upd, 'ii', $id, $hId);
                             mysqli_stmt_execute($upd);
                             mysqli_stmt_close($upd);
                         }
                     }
                }
                $mailRes = mmu_send_manager_credentials_email($email, $name, $role, $password);
                $mailSent = !empty($mailRes['success']);
                
                echo json_encode([
                    'success' => true,
                    'id' => (int)$id,
                    'temporary_password' => ($data['password'] ?? '') === '' ? $password : null,
                    'email_sent' => $mailSent,
                    'email_error' => $mailSent ? null : ($mailRes['error'] ?? 'Unknown PHPMailer error'),
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Could not create user',
                    'db_error' => mysqli_stmt_error($stmt) ?: mysqli_error($conn),
                ]);
            }
            mysqli_stmt_close($stmt);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid id']);
                return;
            }
            $status = trim((string)($data['status'] ?? ''));
            $hasStatus = in_array($status, ['active', 'suspended'], true);
            $hasAccessUpdate = array_key_exists('role_id', $data) || array_key_exists('permissions', $data) || array_key_exists('user_type', $data);
            $hasProfileUpdate = array_key_exists('name', $data) || array_key_exists('email', $data) || array_key_exists('phone', $data) || array_key_exists('password', $data);

            if (!$hasStatus && !$hasAccessUpdate && !$hasProfileUpdate) {
                http_response_code(400);
                echo json_encode(['error' => 'Nothing to update']);
                return;
            }

            // Profile updates (name, email, phone, password)
            if ($hasProfileUpdate) {
                $fields = []; $types = ''; $vals = [];
                if (array_key_exists('name', $data) && trim($data['name']) !== '') {
                    $fields[] = 'name = ?'; $types .= 's'; $vals[] = trim($data['name']);
                }
                if (array_key_exists('email', $data) && trim($data['email']) !== '') {
                    $fields[] = 'email = ?'; $types .= 's'; $vals[] = trim($data['email']);
                }
                if (array_key_exists('phone', $data)) {
                    $fields[] = 'phone = ?'; $types .= 's'; $vals[] = trim($data['phone'] ?? '');
                }
                if (!empty($data['password']) && strlen($data['password']) >= 6) {
                    $fields[] = 'password = ?'; $types .= 's'; $vals[] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                if (!empty($fields)) {
                    $types .= 'i'; $vals[] = $id;
                    $stmt = mysqli_prepare($conn, 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, $types, ...$vals);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }


            if ($hasStatus) {
                if ($status === 'active') {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET deleted_at = NULL WHERE id = ?");
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET deleted_at = NOW() WHERE id = ?");
                }
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            if ($hasAccessUpdate) {
                $roleId = null;
                if (array_key_exists('role_id', $data) && (int)$data['role_id'] > 0) {
                    $roleId = (int)$data['role_id'];
                }
                $userType = trim((string)($data['user_type'] ?? ''));
                if ($userType === '') $userType = 'student';
                $permissions = $data['permissions'] ?? [];
                $permJson = json_encode($permissions);

                $stmt = mysqli_prepare($conn, "UPDATE users SET role_id = ?, user_type = ?, permissions_json = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'issi', $roleId, $userType, $permJson, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                if (isset($data['assigned_hostel_ids']) && is_array($data['assigned_hostel_ids'])) {
                    $adminId = 1;
                    $adminRes = mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'admin' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                    if ($adminRes && $row = mysqli_fetch_assoc($adminRes)) {
                        $adminId = (int)$row['id'];
                    }
                    
                    $upd1 = mysqli_prepare($conn, "UPDATE hostels SET owner_id = ? WHERE owner_id = ?");
                    mysqli_stmt_bind_param($upd1, 'ii', $adminId, $id);
                    mysqli_stmt_execute($upd1);
                    mysqli_stmt_close($upd1);
                    
                    foreach ($data['assigned_hostel_ids'] as $hId) {
                        $hId = (int)$hId;
                        if ($hId > 0) {
                            $upd = mysqli_prepare($conn, "UPDATE hostels SET owner_id = ? WHERE id = ?");
                            mysqli_stmt_bind_param($upd, 'ii', $id, $hId);
                            mysqli_stmt_execute($upd);
                            mysqli_stmt_close($upd);
                        }
                    }
                }
            }

            if (isset($data['bulk_sync_role_id']) && (int)$data['bulk_sync_role_id'] > 0) {
                $syncRoleId = (int)$data['bulk_sync_role_id'];
                $permissions = $data['permissions'] ?? [];
                $permJson = json_encode($permissions);

                $stmt = mysqli_prepare($conn, "UPDATE users SET permissions_json = ? WHERE role_id = ? AND deleted_at IS NULL");
                mysqli_stmt_bind_param($stmt, 'si', $permJson, $syncRoleId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                echo json_encode(['success' => true, 'message' => 'Synced permissions to all users in role']);
                return;
            }

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid id']);
                return;
            }
            // Reassign hostels to admin before deleting
            $adminId = 1;
            $adminRes = mysqli_query($conn, "SELECT id FROM users WHERE user_type = 'admin' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            if ($adminRes && $row = mysqli_fetch_assoc($adminRes)) {
                $adminId = (int)$row['id'];
            }
            $upd = mysqli_prepare($conn, "UPDATE hostels SET owner_id = ? WHERE owner_id = ? AND deleted_at IS NULL");
            mysqli_stmt_bind_param($upd, 'ii', $adminId, $id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $stmt = mysqli_prepare($conn, "UPDATE users SET deleted_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not delete user']);
            }
            mysqli_stmt_close($stmt);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleHostels($method, $conn) {
    switch ($method) {
        case 'GET':
            // Get all hostels
            $result = mysqli_query($conn, "
                SELECT h.*, hi.image_path, u.phone AS owner_phone
                FROM hostels h
                LEFT JOIN hostel_images hi ON h.id = hi.hostel_id AND hi.deleted_at IS NULL
                LEFT JOIN users u ON h.owner_id = u.id
                WHERE h.deleted_at IS NULL
                ORDER BY h.id DESC
            ");

            $hostels = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $hostelId = $row['id'];

                // Check if hostel already exists in array
                if (!isset($hostels[$hostelId])) {
                    $hostels[$hostelId] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'description' => $row['description'],
                        'business_id' => (int)$row['business_id'],
                        'branch_id' => (int)$row['branch_id'],
                        'owner_id' => (int)$row['owner_id'],
                        'managerPhone' => ($row['manager_phone'] ?? '') !== '' ? $row['manager_phone'] : ($row['owner_phone'] ?? null),
                        'gender' => $row['gender'] ?? null,
                        'distance' => $row['distance'] ?? null,
                        'rating' => isset($row['rating']) ? (float)$row['rating'] : null,
                        'amenities' => json_decode($row['amenities_json'] ?? '[]', true) ?: [],
                        'location' => [
                            'address' => $row['address'] ?? '',
                            'lat' => $row['location_lat'] ?? '',
                            'lng' => $row['location_lng'] ?? '',
                        ],
                        'images' => [],
                        'rooms'  => []
                    ];
                }

                // Add image if exists
                if ($row['image_path']) {
                    $hostels[$hostelId]['images'][] = $row['image_path'];
                }
            }

            // Attach rooms from the database to each hostel
            foreach ($hostels as $hostelId => &$h) {
                $roomResult = mysqli_query($conn, "
                    SELECT r.id, r.room_number, r.type, r.price, r.status,
                        (SELECT ri.image_path FROM room_images ri
                         WHERE ri.room_id = r.id AND ri.deleted_at IS NULL
                         ORDER BY ri.id ASC LIMIT 1) AS image_path
                    FROM rooms r
                    WHERE r.hostel_id = " . (int)$hostelId . " AND r.deleted_at IS NULL
                    ORDER BY r.id ASC
                ");

                while ($roomRow = mysqli_fetch_assoc($roomResult)) {
                    $dbStatus = strtolower(trim((string)($roomRow['status'] ?? '')));
                    $frontStatus = $dbStatus;
                    if ($dbStatus === 'vacant') {
                        $frontStatus = 'available';
                    } elseif ($dbStatus === 'occupied') {
                        $frontStatus = 'booked';
                    }

                    $imgPath = isset($roomRow['image_path']) ? trim((string)$roomRow['image_path']) : '';
                    $h['rooms'][] = [
                        'id' => (int)$roomRow['id'],
                        'number' => $roomRow['room_number'],
                        'type' => $roomRow['type'] ?? '',
                        'price' => (float)$roomRow['price'],
                        'status' => $frontStatus,
                        'image' => $imgPath !== '' ? $imgPath : null,
                        // Frontend expects these optional fields; DB schema doesn't store them.
                        'confirmationFee' => 50000,
                        'floor' => null,
                        'bookedBy' => null,
                        'regNo' => null,
                    ];
                }
            }
            unset($h); // break reference

            echo json_encode(array_values($hostels));
            break;

        case 'POST':
            // Add new hostel
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data']);
                return;
            }

            $businessId = $data['business_id'] ?? 1;
            $branchId = $data['branch_id'] ?? 1;
            $ownerId = $data['owner_id'] ?? 1;
            $hostelId = isset($data['id']) ? (int)$data['id'] : null;
            $address = $data['address'] ?? (($data['location']['address'] ?? null) ?: '');
            $description = $data['description'] ?? '';
            $gender = $data['gender'] ?? null;
            $distance = $data['distance'] ?? null;
            $managerPhone = $data['managerPhone'] ?? null;
            $rating = isset($data['rating']) && $data['rating'] !== '' ? (float)$data['rating'] : null;
            $amenitiesJson = json_encode($data['amenities'] ?? []);
            $lat = $data['location']['lat'] ?? null;
            $lng = $data['location']['lng'] ?? null;

            if ($hostelId !== null && $hostelId > 0) {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO hostels (id, business_id, branch_id, owner_id, name, address, description, gender, distance, manager_phone, rating, amenities_json, location_lat, location_lng)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        business_id = VALUES(business_id),
                        branch_id = VALUES(branch_id),
                        owner_id = VALUES(owner_id),
                        name = VALUES(name),
                        address = VALUES(address),
                        description = VALUES(description),
                        gender = VALUES(gender),
                        distance = VALUES(distance),
                        manager_phone = VALUES(manager_phone),
                        rating = VALUES(rating),
                        amenities_json = VALUES(amenities_json),
                        location_lat = VALUES(location_lat),
                        location_lng = VALUES(location_lng)
                ");
                mysqli_stmt_bind_param($stmt, 'iiiissssssdsss', $hostelId, $businessId, $branchId, $ownerId, $data['name'], $address, $description, $gender, $distance, $managerPhone, $rating, $amenitiesJson, $lat, $lng);
            } else {
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO hostels (business_id, branch_id, owner_id, name, address, description, gender, distance, manager_phone, rating, amenities_json, location_lat, location_lng)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmt, 'iiissssssdsss',
                    $businessId, $branchId, $ownerId, $data['name'], $address, $description,
                    $gender, $distance, $managerPhone, $rating, $amenitiesJson, $lat, $lng
                );
            }

            if (mysqli_stmt_execute($stmt)) {
                $hostelId = ($hostelId !== null && $hostelId > 0) ? $hostelId : mysqli_insert_id($conn);

                // Handle image removal if requested
                if (!empty($data['delete_images'])) {
                    $delStmt = mysqli_prepare($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = ? AND deleted_at IS NULL");
                    mysqli_stmt_bind_param($delStmt, 'i', $hostelId);
                    mysqli_stmt_execute($delStmt);
                    mysqli_stmt_close($delStmt);
                }

                // Handle base64 image upload if provided
                if ($hostelId > 0 && !empty($data['image_upload']) && is_array($data['image_upload'])) {
                    mmu_save_hostel_image_upload($conn, $hostelId, $data['image_upload']);
                }

                // Handle existing image paths if provided
                if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                    // Soft-delete old image rows first to prevent duplicate accumulation
                    $delOld = mysqli_prepare($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = ? AND deleted_at IS NULL");
                    mysqli_stmt_bind_param($delOld, 'i', $hostelId);
                    mysqli_stmt_execute($delOld);
                    mysqli_stmt_close($delOld);

                    foreach ($data['images'] as $imagePath) {
                        $imagePath = trim((string)$imagePath);
                        if ($imagePath === '') continue;
                        $imgStmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
                        mysqli_stmt_bind_param($imgStmt, 'is', $hostelId, $imagePath);
                        mysqli_stmt_execute($imgStmt);
                        mysqli_stmt_close($imgStmt);
                    }
                }

                // Upsert rooms if provided by the frontend
                if (isset($data['rooms']) && is_array($data['rooms'])) {
                    foreach ($data['rooms'] as $room) {
                        if (!is_array($room)) continue;

                        $roomId = isset($room['id']) ? (int)$room['id'] : null;
                        $roomNumber = $room['number'] ?? ($room['room_number'] ?? null);
                        if (!$roomNumber) continue;

                        $type = $room['type'] ?? '';
                        $price = $room['price'] ?? 0;

                        $frontStatus = strtolower(trim((string)($room['status'] ?? 'available')));
                        $dbStatus = $frontStatus;
                        if ($frontStatus === 'available') {
                            $dbStatus = 'vacant';
                        } elseif ($frontStatus === 'booked') {
                            $dbStatus = 'occupied';
                        } elseif ($frontStatus === 'pending') {
                            $dbStatus = 'pending';
                        }

                        if ($roomId !== null && $roomId > 0) {
                            $rStmt = mysqli_prepare($conn, "
                                INSERT INTO rooms (id, business_id, branch_id, hostel_id, room_number, type, price, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    hostel_id = VALUES(hostel_id),
                                    room_number = VALUES(room_number),
                                    type = VALUES(type),
                                    price = VALUES(price),
                                    status = VALUES(status)
                            ");
                            // Params: roomId, businessId, branchId, hostelId, roomNumber, type, price, status
                            mysqli_stmt_bind_param($rStmt, 'iiiissds', $roomId, $businessId, $branchId, $hostelId, $roomNumber, $type, $price, $dbStatus);
                        } else {
                            $rStmt = mysqli_prepare($conn, "
                                INSERT INTO rooms (business_id, branch_id, hostel_id, room_number, type, price, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            // Params: businessId, branchId, hostelId, roomNumber, type, price, status
                            mysqli_stmt_bind_param($rStmt, 'iiissds', $businessId, $branchId, $hostelId, $roomNumber, $type, $price, $dbStatus);
                        }

                        mysqli_stmt_execute($rStmt);
                        $actualRoomId = 0;
                        if ($roomId !== null && $roomId > 0) {
                            $actualRoomId = $roomId;
                        } else {
                            $actualRoomId = (int)mysqli_insert_id($conn);
                        }
                        mysqli_stmt_close($rStmt);

                        if ($actualRoomId > 0 && !empty($room['image_upload']) && is_array($room['image_upload'])) {
                            mmu_save_room_image_upload($conn, $actualRoomId, $room['image_upload']);
                        }
                    }
                }

                echo json_encode(['id' => $hostelId, 'success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add hostel']);
            }

            mysqli_stmt_close($stmt);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $hostelId = (int)($data['id'] ?? 0);
            $ownerId = (int)($data['owner_id'] ?? 0);
            if ($hostelId <= 0 || $ownerId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid hostel/owner id']);
                return;
            }

            $stmt = mysqli_prepare($conn, "UPDATE hostels SET owner_id = ? WHERE id = ? AND deleted_at IS NULL");
            mysqli_stmt_bind_param($stmt, 'ii', $ownerId, $hostelId);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not assign hostel owner']);
            }
            mysqli_stmt_close($stmt);
            break;
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $hostelId = (int)($data['id'] ?? 0);
            if ($hostelId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid hostel id']);
                return;
            }
            // 1. Soft-delete the hostel
            mysqli_query($conn, "UPDATE hostels SET deleted_at = NOW() WHERE id = $hostelId");
            // 2. Soft-delete child rooms
            mysqli_query($conn, "UPDATE rooms SET deleted_at = NOW() WHERE hostel_id = $hostelId");
            // 3. Soft-delete room images (via rooms belonging to the hostel)
            mysqli_query($conn, "UPDATE room_images SET deleted_at = NOW() WHERE room_id IN (SELECT id FROM rooms WHERE hostel_id = $hostelId)");
            // 4. Soft-delete hostel images
            mysqli_query($conn, "UPDATE hostel_images SET deleted_at = NOW() WHERE hostel_id = $hostelId");

            echo json_encode(['success' => true]);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handleBookings($method, $conn) {
    switch ($method) {
        case 'GET':
            // Get all bookings with student details
            $result = mysqli_query($conn, "
                SELECT
                    b.id,
                    b.user_id,
                    b.room_id,
                    b.status,
                    b.start_date,
                    b.end_date,
                    b.created_at,
                    b.reference_no,
                    b.reg_no,
                    b.course,
                    b.semester,
                    b.academic_year,
                    b.balance_paid,
                    b.student_phone AS booking_phone,
                    r.hostel_id,
                    r.room_number,
                    h.name AS hostel_name,
                    u.name AS student_name,
                    u.email AS student_email,
                    u.phone AS student_phone
                FROM bookings b
                INNER JOIN rooms r ON b.room_id = r.id
                INNER JOIN hostels h ON r.hostel_id = h.id
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.deleted_at IS NULL
                ORDER BY b.start_date DESC, b.created_at DESC
            ");

            $bookings = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $bookings[] = [
                    'id'          => (int)$row['id'],
                    'reference_no' => $row['reference_no'] ?: null,
                    'userId'      => isset($row['user_id']) ? (int)$row['user_id'] : null,
                    'hostelId'    => (int)$row['hostel_id'],
                    'roomId'      => (int)$row['room_id'],
                    'studentName' => $row['student_name'] ?: 'Unknown Student',
                    'regNo'       => $row['reg_no'] ?: '', 
                    'course'      => $row['course'] ?: '',
                    'semester'    => $row['semester'] ?: '',
                    'year'        => $row['academic_year'] ?: '',
                    'status'      => $row['status'] ?: 'pending',
                    'date'        => $row['start_date'],
                    'email'       => $row['student_email'] ?: '',
                    'phone'       => $row['booking_phone'] ?: ($row['student_phone'] ?: ''),
                    'balancePaid' => (float)($row['balance_paid'] ?? 0),
                    'hostelName'  => $row['hostel_name'] ?: '',
                    'roomNumber'  => $row['room_number'] ?: '',
                ];
            }

            echo json_encode($bookings);
            break;

        case 'POST':
            // Add new booking
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data']);
                return;
            }

            // First, check if user exists or create one
            $userId = null;
            $studentEmail = trim((string)($data['email'] ?? $data['student_email'] ?? ''));
            $studentName  = trim((string)($data['studentName'] ?? $data['student_name'] ?? $data['fullname'] ?? 'Unknown Student'));
            $studentPhone = trim((string)($data['phone'] ?? $data['student_phone'] ?? ''));
            if ($studentEmail === '') {
                // Frontend may submit booking without email; use a deterministic default.
                $studentEmail = 'student@mmu.ac.ug';
            }

            if ($studentEmail) {
                $userResult = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $studentEmail) . "' AND deleted_at IS NULL LIMIT 1");
                if ($userRow = mysqli_fetch_assoc($userResult)) {
                    $userId = $userRow['id'];
                }
            }

            // Create user if doesn't exist
            if (!$userId) {
                $defaultPassword = password_hash('password123', PASSWORD_DEFAULT); // Default password
                $userStmt = mysqli_prepare($conn, "
                    INSERT INTO users (business_id, branch_id, name, email, password, phone)
                    VALUES (1, 1, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($userStmt, 'ssss', $studentName, $studentEmail, $defaultPassword, $studentPhone);
                mysqli_stmt_execute($userStmt);
                $userId = mysqli_insert_id($conn);
                mysqli_stmt_close($userStmt);
            }

            // Find an available room or use the specified one
            $hostelId = isset($data['hostelId']) ? (int)$data['hostelId'] : ((isset($data['hostel_id']) ? (int)$data['hostel_id'] : 0));
            $roomId = isset($data['roomId']) ? (int)$data['roomId'] : ((isset($data['room_id']) ? (int)$data['room_id'] : 0));
            if ($roomId <= 0 && $hostelId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing hostelId/roomId']);
                return;
            }

            if (!$roomId) {
                // Find first available room in the hostel
                $roomResult = mysqli_query($conn, "
                    SELECT id FROM rooms
                    WHERE hostel_id = " . (int)$hostelId . "
                    AND status IN ('vacant','available')
                    AND deleted_at IS NULL
                    LIMIT 1
                ");
                if ($roomRow = mysqli_fetch_assoc($roomResult)) {
                    $roomId = $roomRow['id'];
                }
            }

            if (!$roomId) {
                http_response_code(400);
                echo json_encode(['error' => 'No available rooms']);
                return;
            }

            // Create booking
            $status = trim((string)($data['status'] ?? 'pending'));
            $checkIn = (string)($data['date'] ?? $data['check_in'] ?? date('Y-m-d'));
            $checkOut = (string)($data['check_out'] ?? $data['end_date'] ?? date('Y-m-d', strtotime('+1 month', strtotime($checkIn))));

            $existingId = null;
            $found = false;

            $bookingIdFromClient = isset($data['id']) ? $data['id'] : null;

            if ($bookingIdFromClient && is_numeric($bookingIdFromClient)) {
                $up = mysqli_prepare($conn, "SELECT id FROM bookings WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                mysqli_stmt_bind_param($up, 'i', $bookingIdFromClient);
                mysqli_stmt_execute($up);
                mysqli_stmt_bind_result($up, $existingId);
                $found = mysqli_stmt_fetch($up);
                mysqli_stmt_close($up);
            }

            if (!$found) {
                // Fallback to user+room+start_date matching
                $up = mysqli_prepare($conn, "
                    SELECT id FROM bookings
                    WHERE business_id = 1 AND branch_id = 1
                      AND user_id = ?
                      AND room_id = ?
                      AND start_date = ?
                      AND deleted_at IS NULL
                    LIMIT 1
                ");
                mysqli_stmt_bind_param($up, 'iis', $userId, $roomId, $checkIn);
                mysqli_stmt_execute($up);
                mysqli_stmt_bind_result($up, $existingId);
                $found = mysqli_stmt_fetch($up);
                mysqli_stmt_close($up);
            }

            if ($found) {
                $stmt = mysqli_prepare($conn, "
                    UPDATE bookings
                    SET status = ?, end_date = ?, reg_no = ?, course = ?, semester = ?, academic_year = ?, balance_paid = ?, student_phone = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND deleted_at IS NULL
                ");
                $reg = $data['regNo'] ?? '';
                $crs = $data['course'] ?? '';
                $sem = $data['semester'] ?? '';
                $yr  = $data['year'] ?? '';
                $bal = $data['balancePaid'] ?? 0;
                $sph = $data['phone'] ?? '';
                mysqli_stmt_bind_param($stmt, 'ssssssdsi', $status, $checkOut, $reg, $crs, $sem, $yr, $bal, $sph, $existingId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $bookingId = (int)$existingId;
            } else {
                // Generate a unique reference number
                $refNo = 'BK-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
                
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO bookings (business_id, branch_id, user_id, room_id, status, start_date, end_date, reference_no, reg_no, course, semester, academic_year, balance_paid, student_phone)
                    VALUES (1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $reg = $data['regNo'] ?? '';
                $crs = $data['course'] ?? '';
                $sem = $data['semester'] ?? '';
                $yr  = $data['year'] ?? '';
                $bal = $data['balancePaid'] ?? 0;
                $sph = $data['phone'] ?? '';
                mysqli_stmt_bind_param($stmt, 'iissssssssds', $userId, $roomId, $status, $checkIn, $checkOut, $refNo, $reg, $crs, $sem, $yr, $bal, $sph);
                
                if (mysqli_stmt_execute($stmt)) {
                    $bookingId = mysqli_insert_id($conn);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to add booking', 'db_error' => mysqli_error($conn)]);
                    mysqli_stmt_close($stmt);
                    return;
                }
                mysqli_stmt_close($stmt);
            }

            // Update room status (map frontend statuses to DB statuses)
            $roomDbStatus = 'occupied';
            if ($status === 'pending') {
                $roomDbStatus = 'pending';
            }
            mysqli_query($conn, "UPDATE rooms SET status = '" . mysqli_real_escape_string($conn, $roomDbStatus) . "' WHERE id = " . (int)$roomId . " AND deleted_at IS NULL");

            echo json_encode(['id' => (int)$bookingId, 'success' => true]);
            break;
        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid booking id']);
                return;
            }
            $stmt = mysqli_prepare($conn, "UPDATE bookings SET deleted_at = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Could not delete booking']);
            }
            mysqli_stmt_close($stmt);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function handlePayment($method, $conn) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['phone']) || !isset($data['amount']) || !isset($data['tx_ref'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment data']);
        return;
    }

    // Format phone for Uganda (Flutterwave prefers 256... over 07...)
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    if (strpos($phone, '256') !== 0 && strpos($phone, '0') === 0) {
        $phone = '256' . substr($phone, 1);
    }

    $payload = [
        "tx_ref" => $data['tx_ref'],
        "amount" => $data['amount'],
        "currency" => "UGX",
        "email" => $data['email'] ?? "student@mmu.ac.ug",
        "phone_number" => $phone,
        "fullname" => $data['fullname'] ?? "MMU Student",
        "network" => $data['network'] ?? "MTN"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/charges?type=mobile_money_uganda");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . FLW_SECRET_KEY,
        "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'API Error: ' . $err]);
    } else {
        // Return exactly what Flutterwave responds with (e.g. {"status":"success", ...})
        echo $response;
    }
}

function handlePaymentVerification($method, $conn) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $tx_ref = $_GET['tx_ref'] ?? '';
    if (!$tx_ref) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing tx_ref']);
        return;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=" . urlencode($tx_ref));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . FLW_SECRET_KEY,
        "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'API Error']);
    } else {
        $resObj = json_decode($response, true);
        if (isset($resObj['data']) && isset($resObj['data']['status'])) {
            echo json_encode([
                'status' => $resObj['data']['status'], // "successful", "failed", or "pending"
                'transaction_id' => $resObj['data']['id'] ?? ''
            ]);
        } else {
            echo json_encode(['status' => 'pending']);
        }
    }
}

function ensureSystemSettingsTable($conn) {
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
}

function handleSettings($method, $conn) {
    if ($method === 'GET') {
        $res = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
        return;
    }

    if ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data payload']);
            return;
        }

        foreach ($data as $key => $value) {
            $k = mysqli_real_escape_string($conn, $key);
            $v = mysqli_real_escape_string($conn, (string)$value);
            mysqli_query($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value = '$v'");
        }
        echo json_encode(['success' => true]);
        return;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed for settings endpoint']);
}

function handleRolePermissions($method, $conn) {
    $businessId = 1; $branchId = 1;
    switch ($method) {
        case 'GET':
            $roleId = (int)($_GET['role_id'] ?? 0);
            $permId = (int)($_GET['permission_id'] ?? 0);

            if ($roleId > 0) {
                $rows = [];
                $res = mysqli_query($conn, "SELECT p.id, p.name FROM permission_role pr JOIN permissions p ON pr.permission_id = p.id WHERE pr.role_id = {$roleId} AND pr.deleted_at IS NULL AND p.deleted_at IS NULL");
                while ($res && ($r = mysqli_fetch_assoc($res))) { $rows[] = ['id' => (int)$r['id'], 'name' => $r['name']]; }
                echo json_encode($rows);
            } elseif ($permId > 0) {
                $rows = [];
                $res = mysqli_query($conn, "SELECT r.id, r.name FROM permission_role pr JOIN roles r ON pr.role_id = r.id WHERE pr.permission_id = {$permId} AND pr.deleted_at IS NULL AND r.deleted_at IS NULL");
                while ($res && ($r = mysqli_fetch_assoc($res))) { $rows[] = ['id' => (int)$r['id'], 'name' => $r['name']]; }
                echo json_encode($rows);
            } else {
                http_response_code(400); echo json_encode(['error' => 'role_id or permission_id required']);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $roleId = (int)($data['role_id'] ?? 0);
            $permId = (int)($data['permission_id'] ?? 0);

            if ($roleId > 0) {
                $permIds = $data['permission_ids'] ?? [];
                mysqli_query($conn, "UPDATE permission_role SET deleted_at = NOW() WHERE role_id = {$roleId} AND deleted_at IS NULL");
                foreach ($permIds as $pId) {
                    $pId = (int)$pId;
                    if ($pId > 0) {
                        $stmt = mysqli_prepare($conn, "INSERT INTO permission_role (role_id, permission_id, business_id, branch_id) VALUES (?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, 'iiii', $roleId, $pId, $businessId, $branchId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                echo json_encode(['success' => true]);
            } elseif ($permId > 0) {
                $roleIds = $data['role_ids'] ?? [];
                mysqli_query($conn, "UPDATE permission_role SET deleted_at = NOW() WHERE permission_id = {$permId} AND deleted_at IS NULL");
                foreach ($roleIds as $rId) {
                    $rId = (int)$rId;
                    if ($rId > 0) {
                        $stmt = mysqli_prepare($conn, "INSERT INTO permission_role (role_id, permission_id, business_id, branch_id) VALUES (?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, 'iiii', $rId, $permId, $businessId, $branchId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400); echo json_encode(['error' => 'role_id or permission_id required']);
            }
            break;

        default:
            http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}
?>