<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'new_hostel';

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}

// Use $conn in your scripts
?> 