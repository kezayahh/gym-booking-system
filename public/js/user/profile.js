document.addEventListener("DOMContentLoaded", () => {
  const changePicBtn = document.getElementById("changePicBtn");
  const uploadPhoto = document.getElementById("uploadPhoto");
  const profilePic = document.getElementById("profilePic");
  const saveBtn = document.getElementById("saveBtn");
  const logoutBtn = document.getElementById("logoutBtn");

  // Dropdown
  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");
  const goProfile = document.getElementById("goProfile");
  const logoutDropdown = document.getElementById("logoutDropdown");

  // ✅ Profile dropdown toggle
  profileBtn?.addEventListener("click", (event) => {
    event.stopPropagation();
    profileMenu.style.display =
      profileMenu.style.display === "block" ? "none" : "block";
  });
  document.addEventListener("click", () => (profileMenu.style.display = "none"));
  goProfile?.addEventListener("click", () => (window.location.href = "profile.html"));
  logoutDropdown?.addEventListener("click", () => {
    if (confirm("Are you sure you want to log out?")) {
      localStorage.setItem("loggedIn", "false");
      window.location.href = "login.html";
    }
  });

  // ✅ Load and display saved profile data
  const profileFields = ["name", "email", "phone", "address", "password"];
  profileFields.forEach((field) => {
    const input = document.getElementById(field);
    const value = localStorage.getItem(field);
    if (input && value) {
      input.value = value; // always show saved data
    }
    input?.setAttribute("readonly", "true");
  });

  // ✅ Load saved profile picture
  if (localStorage.getItem("profilePic")) {
    profilePic.src = localStorage.getItem("profilePic");
  }

  // ✅ Allow editing when clicking inside any field
  document.querySelectorAll("#profileForm input").forEach((input) => {
    input.addEventListener("focus", () => input.removeAttribute("readonly"));
  });

  // ✅ Save Changes button
  saveBtn?.addEventListener("click", () => {
    document.querySelectorAll("#profileForm input").forEach((input) => {
      localStorage.setItem(input.id, input.value);
      input.setAttribute("readonly", "true");
    });
    alert("✅ Changes saved successfully!");
  });

  // ✅ Change Picture
  changePicBtn?.addEventListener("click", () => uploadPhoto.click());
  uploadPhoto?.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      const imgURL = URL.createObjectURL(file);
      profilePic.src = imgURL;
      localStorage.setItem("profilePic", imgURL);
    }
  });

  // ✅ Logout (sidebar)
  logoutBtn?.addEventListener("click", () => {
    if (confirm("Logout?")) {
      localStorage.setItem("loggedIn", "false");
      window.location.href = "login.html";
    }
  });
});
