document.addEventListener('DOMContentLoaded', function () {


  

  const logoutBtn = document.querySelector('.logout-btn');
  const logoutModal = document.getElementById('logoutModal');
  const confirmLogout = document.getElementById('confirmLogout');
  const cancelLogout = document.getElementById('cancelLogout');

  function openLogoutModal() {
    if (logoutModal) {
      logoutModal.style.display = 'flex';
      
      const firstFocusable = logoutModal.querySelector('button');
      if (firstFocusable) firstFocusable.focus();
    }
  }

 
  function closeLogoutModal() {
    if (logoutModal) logoutModal.style.display = 'none';
  }

  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openLogoutModal();
    });
  }

  if (cancelLogout) {
    cancelLogout.addEventListener('click', (e) => {
      e.preventDefault();
      closeLogoutModal();
    });
  }

  if (confirmLogout) {
    confirmLogout.addEventListener('click', (e) => {
      e.preventDefault();
      
      window.location.href = "index.html";
    });
  }

  if (logoutModal) {
    logoutModal.addEventListener('click', (e) => {
      
      const modalContent = logoutModal.querySelector('.logout-modal-content');
      if (modalContent && !modalContent.contains(e.target)) {
        closeLogoutModal();
      }
    });
  }

  
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLogoutModal();
  });

}); 
