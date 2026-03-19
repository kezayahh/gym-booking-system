document.getElementById("createAccount").addEventListener("click", () => {
  window.location.href = "register.html";
});
document.getElementById("loginBtn").addEventListener("click", () => {
  // Optional: you can add form validation here
  window.location.href = "/admin/dashboard";
});
// Forgot Password Modal
const forgotLink = document.getElementById('forgotLink');
const forgotModal = document.getElementById('forgotModal');
const closeForgot = document.getElementById('closeForgot');
const cancelForgot = document.getElementById('cancelForgot');
const sendReset = document.getElementById('sendReset');
const forgotEmail = document.getElementById('forgotEmail');

// Open modal
forgotLink.addEventListener('click', (e) => {
  e.preventDefault();
  forgotModal.style.display = 'flex';
});

// Close modal
closeForgot.addEventListener('click', () => {
  forgotModal.style.display = 'none';
});

cancelForgot.addEventListener('click', () => {
  forgotModal.style.display = 'none';
});

// Send reset alert
sendReset.addEventListener('click', () => {
  if(forgotEmail.value){
    alert(`Password reset link sent to ${forgotEmail.value}`);
    forgotModal.style.display = 'none';
    forgotEmail.value = '';
  } else {
    alert('Please enter your email address');
  }
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
  if(e.target === forgotModal){
    forgotModal.style.display = 'none';
  }
});
