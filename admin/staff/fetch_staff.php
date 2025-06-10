<?php
include '../../db_connect.php';
?>
<link rel="stylesheet" href="staff.css">

<div class="staff-header">
  <h3>Staff Members</h3>
  <button id="createStaffBtn">+ Add Staff</button>
</div>

<?php
$sql = "SELECT id, username, email, created_at FROM users WHERE role = 'staff'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table class='staff-table'>";
    echo "<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Created At</th><th>Actions</th></tr></thead><tbody>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['username']}</td>
                <td>{$row['email']}</td>
                <td>{$row['created_at']}</td>
                <td>
                    <button class='edit-btn' data-id='{$row['id']}'>Edit</button>
                    <button class='delete-btn' data-id='{$row['id']}'>Delete</button>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No staff found.</p>";
}

$conn->close();
?>
<script src="staff.js"></script>
