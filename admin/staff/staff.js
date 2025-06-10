// admin/staff/staff.js
document.addEventListener('click', function(e) {
  if (e.target && e.target.id === 'createStaffBtn') {
    window.location.href = '../staff/add_staff.php';
  }

  if (e.target && e.target.classList.contains('edit-btn')) {
  const staffId = e.target.getAttribute('data-id');
  window.location.href = `../staff/edit_staff.php?id=${staffId}`; // relative to admin_dashboard.php
}

if (e.target && e.target.classList.contains('delete-btn')) {
  const staffId = e.target.getAttribute('data-id');

  fetch('../staff/delete_staff.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${staffId}`
  })
  .then(response => response.text())
  .then(result => {
    if (result === 'success') {
      alert('Staff deleted successfully.');
      // Reload the staff list or refresh page
      location.reload(); // or your custom reload function
    } else {
      alert('Error deleting staff.');
    }
  });
  }
}
);
