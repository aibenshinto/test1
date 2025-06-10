<?php
include '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check email uniqueness except for current user
    $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCheck->bind_param("si", $email, $id);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    if ($stmtCheck->num_rows > 0) {
        echo "<script>alert('Error: Email address already in use by another staff member.'); window.history.back();</script>";
        $stmtCheck->close();
        $conn->close();
        exit;
    }
    $stmtCheck->close();

    if (!empty($password)) {
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ? AND role = 'staff'");
        $stmt->bind_param("sssi", $username, $email, $hashedPwd, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ? AND role = 'staff'");
        $stmt->bind_param("ssi", $username, $email, $id);
    }

    if ($stmt->execute()) {
        // Show popup and redirect
        echo "<script>
                alert('Staff updated successfully!');
                window.location.href = '../dashboard/admin_dashboard.php';
              </script>";
    } else {
        echo "<script>alert('Error updating staff.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>