document.getElementById("createAccount").addEventListener("click", () => {
  window.location.href = "register.html";
});

document.getElementById("loginBtn").addEventListener("click", () => {
  window.location.href = "/user/dashboard";
});

/* FORGOT PASSWORD MODAL */
const forgotLink = document.querySelector(".forgot");
const modal = document.getElementById("forgotModal");
const closeModal = document.getElementById("closeModal");
const sendReset = document.getElementById("sendReset");

forgotLink.addEventListener("click", (e) => {
  e.preventDefault();
  modal.style.display = "flex";
});

closeModal.addEventListener("click", () => {
  modal.style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target === modal) modal.style.display = "none";
});

sendReset.addEventListener("click", () => {
  alert("✅ A password reset link has been sent to your email.");
  modal.style.display = "none";
});
