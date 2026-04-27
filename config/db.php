<?php
// Database configuration
$host     = getenv('DB_HOST') ?: 'localhost';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'new_hostel';
$port     = getenv('DB_PORT') ?: '3306';

// Create connection
$conn = mysqli_connect($host, $user, $password, $database, $port);

// Check connection
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Use $conn in your scripts
?> 