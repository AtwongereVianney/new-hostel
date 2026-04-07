<?php
require_once 'config/db.php';

// Insert default business if it doesn't exist
$result = mysqli_query($conn, "SELECT id FROM business WHERE id = 1");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "INSERT INTO business (id, name) VALUES (1, 'MMU Hostel Solutions')");
    echo "Created default business\n";
}

// Insert default branch if it doesn't exist
$result = mysqli_query($conn, "SELECT id FROM branch WHERE id = 1");
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "INSERT INTO branch (id, business_id, name, location) VALUES (1, 1, 'Main Campus', 'Fort Portal')");
    echo "Created default branch\n";
}

// Ensure status column exists in users
$checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
if (mysqli_num_rows($checkStatus) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER role_id");
    echo "Added status column to users table\n";
}

// Insert default roles
$roles = [
    ['id' => 1, 'name' => 'super_admin'],
    ['id' => 2, 'name' => 'hostel_owner'],
    ['id' => 3, 'name' => 'student']
];
foreach ($roles as $role) {
    $rId = $role['id'];
    $rName = $role['name'];
    $res = mysqli_query($conn, "SELECT id FROM roles WHERE id = $rId");
    if (mysqli_num_rows($res) == 0) {
        mysqli_query($conn, "INSERT INTO roles (id, business_id, branch_id, name) VALUES ($rId, 1, 1, '$rName')");
        echo "Created role: $rName\n";
    }
}

// Insert default admin user if it doesn't exist
$result = mysqli_query($conn, "SELECT id FROM users WHERE email = 'admin@mmu.edu'");
if (mysqli_num_rows($result) == 0) {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "
        INSERT INTO users (business_id, branch_id, name, email, password, phone, role_id, status)
        VALUES (1, 1, 'System Administrator', 'admin@mmu.edu', '$password', '+256700000000', 1, 'active')
    ");
    echo "Created default admin user\n";
} else {
    // Ensure admin user has correct role
    mysqli_query($conn, "UPDATE users SET role_id = 1 WHERE email = 'admin@mmu.edu'");
}

// Insert some sample rooms for existing hostels
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rooms");
$row = mysqli_fetch_assoc($result);
if ($row['count'] == 0) {
    // Get existing hostels
    $hostelsResult = mysqli_query($conn, "SELECT id, name FROM hostels LIMIT 5");
    while ($hostel = mysqli_fetch_assoc($hostelsResult)) {
        // Create 10 rooms per hostel
        for ($i = 1; $i <= 10; $i++) {
            $roomNumber = $hostel['id'] . sprintf('%02d', $i);
            $price = rand(50000, 150000); // Random price between 50k and 150k
            mysqli_query($conn, "
                INSERT INTO rooms (business_id, branch_id, hostel_id, room_number, type, price, status)
                VALUES (1, 1, {$hostel['id']}, '$roomNumber', 'Single', $price, 'vacant')
            ");
        }
    }
    echo "Created sample rooms\n";
}

echo "Database setup complete!\n";
?>