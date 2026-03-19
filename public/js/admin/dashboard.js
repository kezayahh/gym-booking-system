document.addEventListener("DOMContentLoaded", () => {

  // ---------- LOGOUT MODAL ----------
  const logoutBtn = document.querySelector(".logout-btn");
  const logoutModal = document.getElementById("logoutModal");
  const closeLogoutBtn = logoutModal.querySelector(".close-btn");
  const cancelLogoutBtn = logoutModal.querySelector(".cancel-btn");
  const confirmLogoutBtn = logoutModal.querySelector(".confirm-logout-btn");

  logoutBtn.addEventListener("click", () => logoutModal.style.display = "flex");
  closeLogoutBtn.addEventListener("click", () => logoutModal.style.display = "none");
  cancelLogoutBtn.addEventListener("click", () => logoutModal.style.display = "none");
  confirmLogoutBtn.addEventListener("click", () => window.location.href = "index.html");
  window.addEventListener("click", e => { if(e.target === logoutModal) logoutModal.style.display = "none"; });

  // ---------- PROFILE MODAL ----------
  const profileSection = document.querySelector(".profile-section");
  const profileModal = document.getElementById("profileModal");
  const profileCloseBtn = profileModal.querySelector(".close-btn");
  const profileCancelBtn = profileModal.querySelector(".cancel-btn");
  const profileForm = document.getElementById("profileForm");
  const profileTopbarName = document.getElementById("profileNameTopbar");
  const profileTopbarPic = document.querySelector(".profile-pic");
  const profilePicInput = document.getElementById("profilePic");

  profileSection.addEventListener("click", () => {
    profileModal.style.display = "flex";
    document.getElementById("profileName").value = localStorage.getItem("userName") || "John Doe";
    document.getElementById("profileEmail").value = localStorage.getItem("userEmail") || "john@example.com";
    document.getElementById("profileRole").value = localStorage.getItem("userRole") || "Admin";
  });

  profileCloseBtn.addEventListener("click", () => profileModal.style.display = "none");
  profileCancelBtn.addEventListener("click", () => profileModal.style.display = "none");
  window.addEventListener("click", (e) => { if (e.target === profileModal) profileModal.style.display = "none"; });

  profileForm.addEventListener("submit", e => {
    e.preventDefault();
    const name = document.getElementById("profileName").value;
    const email = document.getElementById("profileEmail").value;
    const role = document.getElementById("profileRole").value;

    localStorage.setItem("userName", name);
    localStorage.setItem("userEmail", email);
    localStorage.setItem("userRole", role);
    profileTopbarName.textContent = name;

    if(profilePicInput.files[0]){
      const reader = new FileReader();
      reader.onload = e => {
        localStorage.setItem("profilePic", e.target.result);
        profileTopbarPic.src = e.target.result;
      };
      reader.readAsDataURL(profilePicInput.files[0]);
    }

    alert("Profile updated successfully!");
    profileModal.style.display = "none";
  });

  const savedPic = localStorage.getItem("profilePic");
  if(savedPic) profileTopbarPic.src = savedPic;

});
