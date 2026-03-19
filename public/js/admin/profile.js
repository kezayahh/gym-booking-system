document.addEventListener("DOMContentLoaded", () => {
  const profileImage = document.getElementById("profileImage");
  const profilePicInput = document.getElementById("profilePicInput");
  const profileForm = document.getElementById("profileForm");
  const statusMessage = document.getElementById("statusMessage");
  const logoutBtn = document.querySelector(".logout-btn");

  // Live preview of uploaded profile picture
  profilePicInput.addEventListener("change", () => {
    const file = profilePicInput.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        profileImage.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });

  // Save changes (simulated)
  profileForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const name = document.getElementById("fullName").value;
    const email = document.getElementById("email").value;
    statusMessage.textContent = `Profile updated! Name: ${name}, Email: ${email}`;
  });

  // Logout
  logoutBtn.addEventListener("click", () => {
    if(confirm("Are you sure you want to logout?")) {
      window.location.href = "index.html";
    }
  });
});
