<?php
require 'database/db_connection.php'; // adjust path if needed

// Hash the same password for both users
$hashedPassword = password_hash("christina_828", PASSWORD_DEFAULT);

// Prepare SQL statement
$stmt = $conn->prepare("
    INSERT INTO users (
        employee_id, email, password, f_name, m_name, l_name, address, cp_number, role_id, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Example Manager user
$managerData = [
    'EMP003',                     // employee_id
    'manager@example.com',         // email
    $hashedPassword,               // password (hashed)
    'Bob',                         // f_name
    'M.',                           // m_name
    'Smith',                       // l_name
    '456 Manager St, Makati City', // address
    '09171230001',                 // phone
    2,                             // role_id (Manager)
    'active'                       // status
];

// Example Staff user
$staffData = [
    'EMP004',
    'staff@example.com',
    $hashedPassword,
    'Charlie',
    'K.',
    'Johnson',
    '789 Staff Ave, Quezon City',
    '09171230002',
    3,          // role_id (Staff)
    'active'
];

// Bind parameters
$stmt->bind_param(
    "sssssssiss",
    $employee_id,
    $email,
    $password,
    $f_name,
    $m_name,
    $l_name,
    $address,
    $cp_number,
    $role_id,
    $status
);

// Insert Manager
list($employee_id, $email, $password, $f_name, $m_name, $l_name, $address, $cp_number, $role_id, $status) = $managerData;
$stmt->execute() or die("Manager insert failed: " . $stmt->error);

// Insert Staff
list($employee_id, $email, $password, $f_name, $m_name, $l_name, $address, $cp_number, $role_id, $status) = $staffData;
$stmt->execute() or die("Staff insert failed: " . $stmt->error);

echo "✅ Manager and Staff inserted successfully with hashed password.";

$stmt->close();
$conn->close();
?>
