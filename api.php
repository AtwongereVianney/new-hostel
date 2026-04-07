<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/db.php';

// IMPORTANT: Replace this with your LIVE Flutterwave Secret Key (FLWSECK-XXXX)
define('FLW_SECRET_KEY', 'FLWSECK_TEST-sandbox-secret-key-placeholder');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Remove query string and decode
$request = strtok($request, '?');

// Handle different URL patterns
if (strpos($request, '/new-hostel/api.php/') === 0) {
    $request = str_replace('/new-hostel/api.php/', '', $request);
} elseif (strpos($request, '/api.php/') === 0) {
    $request = str_replace('/api.php/', '', $request);
} elseif ($request === '/new-hostel/api.php' || $request === '/api.php') {
    $request = '';
}

// Route the request
switch ($request) {
    case 'hostels':
    case '/hostels':
        handleHostels($method, $conn);
        break;
    case 'bookings':
    case '/bookings':
        handleBookings($method, $conn);
        break;
    case 'login':
    case '/login':
        handleLogin($method, $conn);
        break;
    case 'users':
    case '/users':
        handleUsers($method, $conn);
        break;
    case 'pay':
    case '/pay':
        handlePayment($method, $conn);
        break;
    case 'verify-payment':
    case '/verify-payment':
        handlePaymentVerification($method, $conn);
        break;
    case '':
    case '/':
        // Root API endpoint
        echo json_encode(['status' => 'API is running', 'endpoints' => ['hostels', 'bookings']]);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'request' => $request]);
        break;
}

function handleHostels($method, $conn) {
    switch ($method) {
        case 'GET':
            // Get all hostels
            $ownerId = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : null;
            $where = "WHERE h.deleted_at IS NULL";
            if ($ownerId) {
                $where .= " AND h.owner_id = $ownerId";
            }

            $result = mysqli_query($conn, "
                SELECT h.*, hi.image_path
                FROM hostels h
                LEFT JOIN hostel_images hi ON h.id = hi.hostel_id
                $where
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
                        'images' => []
                    ];
                }

                // Add image if exists
                if ($row['image_path']) {
                    $hostels[$hostelId]['images'][] = $row['image_path'];
                }
            }

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

            $stmt = mysqli_prepare($conn, "
                INSERT INTO hostels (business_id, branch_id, owner_id, name, address, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $businessId = $data['business_id'] ?? 1;
            $branchId = $data['branch_id'] ?? 1;
            $ownerId = $data['owner_id'] ?? 1;

            mysqli_stmt_bind_param($stmt, 'iiisss',
                $businessId, $branchId, $ownerId,
                $data['name'], $data['address'] ?? '', $data['description'] ?? ''
            );

            if (mysqli_stmt_execute($stmt)) {
                $hostelId = mysqli_insert_id($conn);

                // Handle images if provided
                if (isset($data['images']) && is_array($data['images'])) {
                    foreach ($data['images'] as $imagePath) {
                        $imgStmt = mysqli_prepare($conn, "INSERT INTO hostel_images (hostel_id, image_path) VALUES (?, ?)");
                        mysqli_stmt_bind_param($imgStmt, 'is', $hostelId, $imagePath);
                        mysqli_stmt_execute($imgStmt);
                        mysqli_stmt_close($imgStmt);
                    }
                }

                echo json_encode(['id' => $hostelId, 'success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add hostel']);
            }

            mysqli_stmt_close($stmt);
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
                SELECT b.*, h.name as hostel_name, r.room_number,
                       u.name as student_name, u.email as student_email, u.phone as student_phone
                FROM bookings b
                LEFT JOIN hostels h ON b.hostel_id = h.id
                LEFT JOIN rooms r ON b.room_id = r.id
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.deleted_at IS NULL
                ORDER BY b.created_at DESC
            ");

            $bookings = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Extract registration number from user data or use a default format
                $regNumber = 'MMU/' . date('Y', strtotime($row['created_at'])) . '/' . str_pad($row['user_id'], 3, '0', STR_PAD_LEFT);

                $bookings[] = [
                    'id' => (int)$row['id'],
                    'hostel_id' => (int)$row['hostel_id'],
                    'hostel_name' => $row['hostel_name'],
                    'room_id' => $row['room_id'] ? (int)$row['room_id'] : null,
                    'room_number' => $row['room_number'],
                    'student_name' => $row['student_name'] ?: 'Unknown Student',
                    'student_reg' => $regNumber,
                    'student_email' => $row['student_email'] ?: '',
                    'student_phone' => $row['student_phone'] ?: '',
                    'check_in' => $row['start_date'],
                    'check_out' => $row['end_date'],
                    'status' => $row['status'],
                    'total_amount' => 0.00, // Will be calculated from payments
                    'created_at' => $row['created_at']
                ];
            }

            echo json_encode($bookings);
            break;

        case 'POST':
            // Add new booking
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['hostel_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data']);
                return;
            }

            // First, check if user exists or create one
            $userId = null;
            $studentEmail = $data['student_email'] ?? '';
            $studentName = $data['student_name'] ?? 'Unknown Student';
            $studentPhone = $data['student_phone'] ?? '';

            if ($studentEmail) {
                $userResult = mysqli_query($conn, "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($conn, $studentEmail) . "' LIMIT 1");
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
            $roomId = $data['room_id'] ?? null;
            if (!$roomId) {
                // Find first available room in the hostel
                $roomResult = mysqli_query($conn, "
                    SELECT id FROM rooms
                    WHERE hostel_id = " . (int)$data['hostel_id'] . "
                    AND status = 'vacant'
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
            $stmt = mysqli_prepare($conn, "
                INSERT INTO bookings (business_id, branch_id, user_id, room_id, status, start_date, end_date)
                VALUES (1, 1, ?, ?, ?, ?, ?)
            ");

            $status = $data['status'] ?? 'confirmed';
            $checkIn = $data['check_in'] ?? date('Y-m-d');
            $checkOut = $data['check_out'] ?? date('Y-m-d', strtotime('+1 month'));

            mysqli_stmt_bind_param($stmt, 'iisss', $userId, $roomId, $status, $checkIn, $checkOut);

            if (mysqli_stmt_execute($stmt)) {
                $bookingId = mysqli_insert_id($conn);

                // Update room status
                mysqli_query($conn, "UPDATE rooms SET status = 'occupied' WHERE id = $roomId");

                echo json_encode(['id' => $bookingId, 'success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add booking']);
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
?>