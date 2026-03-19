// Modal logic for Refund Management

const editButtons = document.querySelectorAll(".edit");
const deleteButtons = document.querySelectorAll(".delete");
const editModal = document.getElementById("editModal");
const deleteModal = document.getElementById("deleteModal");
const closeButtons = document.querySelectorAll(".close-btn");
const cancelButtons = document.querySelectorAll(".cancel-btn");

// Open Update Modal
editButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    editModal.style.display = "flex";
  });
});

// Open Delete Modal
deleteButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    deleteModal.style.display = "flex";
  });
});

// Close any modal
closeButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    editModal.style.display = "none";
    deleteModal.style.display = "none";
  });
});

cancelButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    editModal.style.display = "none";
    deleteModal.style.display = "none";
  });
});
