document.addEventListener("DOMContentLoaded", () => {
  const registerForm = document.querySelector(".register-form");

  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const firstname = document.getElementById("firstname").value.trim();
    const lastname = document.getElementById("lastname").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const address = document.getElementById("address").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirm = document.getElementById("confirmPassword").value.trim();

    // Password check
    if (password !== confirm) {
      alert("⚠️ Passwords do not match. Please try again.");
      return;
    }

    // ✅ Store user info in localStorage for profile page
    const fullName = `${firstname} ${lastname}`;
    localStorage.setItem("name", fullName);
    localStorage.setItem("email", email);
    localStorage.setItem("phone", phone);
    localStorage.setItem("address", address);
    localStorage.setItem("password", password);

    // Default profile image (optional)
    localStorage.setItem("profilePic", "images/default-user.png");

    alert("✅ Registration successful!");
    window.location.href = "dashboard.html"; // redirect after register
  });
});
