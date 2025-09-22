<?php 
session_start();
require 'database/db_connection.php';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ✅ Include role_id in query
    $stmt = $conn->prepare("SELECT id, password, status, role_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['status'] !== 'active') {
            $_SESSION['login_messages'] = [
                'type' => 'error',
                'message' => 'Account is ' . $row['status']
            ];
            header("Location: index.php");
            exit();
        }

        if (password_verify($password, $row['password'])) {
            // ✅ Save user session data
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role_id'] = $row['role_id'];
            $_SESSION['login_messages'] = [
                'type' => 'success',
                'message' => 'Login successful'
            ];

            // ✅ Redirect based on role_id
            if ($row['role_id'] == 1) {
                header("Location: users/admin/dashboard.php");
            } elseif ($row['role_id'] == 2) {
                header("Location: users/manager/dashboard.php");
            } elseif ($row['role_id'] == 3) {
                header("Location: users/staff/dashboard.php");
            } else {
                // Default redirect if role is unexpected
                header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['login_messages'] = [
                'type' => 'error',
                'message' => 'Invalid email or password.'
            ];
        }
    } else {
        $_SESSION['login_messages'] = [
            'type' => 'error',
            'message' => 'Invalid email or password.'
        ];
    }

    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit();
} else {
    // No POST data, redirect safely
    header("Location: index.php");
    exit();
}
