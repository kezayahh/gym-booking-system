document.addEventListener("DOMContentLoaded", () => {
  const registerForm = document.querySelector(".register-form");

  registerForm.addEventListener("submit", (e) => {
    e.preventDefault();

    // Optional: Add password matching check
    const pass = document.getElementById("password").value;
    const confirm = document.getElementById("confirmPassword").value;

    if (pass !== confirm) {
      alert("Passwords do not match. Please try again.");
      return;
    }

    // If matched → redirect to dashboard
    alert("Registration successful!");
    window.location.href = "dashboard.html";
  });
});