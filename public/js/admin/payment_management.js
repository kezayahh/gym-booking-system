// Payment Management JS

document.addEventListener("DOMContentLoaded", () => {
  const editButtons = document.querySelectorAll(".edit");
  const deleteButtons = document.querySelectorAll(".delete");
  const editModal = document.getElementById("editModal");
  const deleteModal = document.getElementById("deleteModal");
  const closeButtons = document.querySelectorAll(".close-btn");
  const cancelButtons = document.querySelectorAll(".cancel-btn");

  // Open Edit Modal
  editButtons.forEach(button => {
    button.addEventListener("click", () => {
      editModal.style.display = "flex";
    });
  });

  // Open Delete Modal
  deleteButtons.forEach(button => {
    button.addEventListener("click", () => {
      deleteModal.style.display = "flex";
    });
  });

  // Close Modals
  closeButtons.forEach(button => {
    button.addEventListener("click", () => {
      editModal.style.display = "none";
      deleteModal.style.display = "none";
    });
  });

  cancelButtons.forEach(button => {
    button.addEventListener("click", () => {
      editModal.style.display = "none";
      deleteModal.style.display = "none";
    });
  });

  // Logout confirmation
  const logoutBtn = document.querySelector(".logout-btn");
  logoutBtn.addEventListener("click", () => {
    if (confirm("Are you sure you want to log out?")) {
      window.location.href = "index.html";
    }
  });
});
