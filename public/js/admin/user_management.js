document.addEventListener('DOMContentLoaded', function() {
  const editButtons = document.querySelectorAll('.edit');
  const deleteButtons = document.querySelectorAll('.delete');
  const editModal = document.getElementById('editModal');
  const deleteModal = document.getElementById('deleteModal');
  const closeBtns = document.querySelectorAll('.close-btn');
  const cancelBtns = document.querySelectorAll('.cancel-btn');
  const form = document.getElementById('editForm');
  const deleteConfirmBtn = document.querySelector('.delete-btn');
  const deleteMsg = document.getElementById('deleteMessage');

  let currentRow = null;

  // Open Edit Modal
  editButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      currentRow = e.target.closest('tr');
      const cells = currentRow.querySelectorAll('td');

      document.getElementById('userId').value = cells[0].textContent;
      document.getElementById('fullName').value = cells[1].textContent;
      document.getElementById('email').value = cells[2].textContent;
      document.getElementById('role').value = cells[3].textContent;
      document.getElementById('dateRegistered').value = cells[4].textContent;

      editModal.style.display = 'flex';
    });
  });

  // Open Delete Modal
  deleteButtons.forEach(btn => {
    btn.addEventListener('click', e => {
      currentRow = e.target.closest('tr');
      const userName = currentRow.cells[1].textContent;
      deleteMsg.textContent = `Do you really want to delete user ${userName}? This action cannot be undone.`;
      deleteModal.style.display = 'flex';
    });
  });

  // Close modal function
  const closeModals = () => {
    editModal.style.display = 'none';
    deleteModal.style.display = 'none';
  };

  // Close modals on X or Cancel
  closeBtns.forEach(btn => btn.addEventListener('click', closeModals));
  cancelBtns.forEach(btn => btn.addEventListener('click', closeModals));

  // Save Changes
  form.addEventListener('submit', e => {
    e.preventDefault();
    if (currentRow) {
      currentRow.cells[1].textContent = document.getElementById('fullName').value;
      currentRow.cells[2].textContent = document.getElementById('email').value;
      currentRow.cells[3].textContent = document.getElementById('role').value;
      alert('User details updated successfully!');
    }
    closeModals();
  });

  // Confirm Delete
  deleteConfirmBtn.addEventListener('click', () => {
    if (currentRow) {
      currentRow.remove();
      alert('User deleted successfully!');
    }
    closeModals();
  });

  // Click outside to close
  window.addEventListener('click', e => {
    if (e.target === editModal || e.target === deleteModal) closeModals();
  });
});
