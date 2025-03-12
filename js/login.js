document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("loginModal");
  const backButton = document.querySelector(".back-button");
  const loginBtn = document.getElementById("loginBtn"); 

  function openModal() {
      if (!modal) {
          console.error("Modal element not found!");
          return;
      }
      modal.classList.add('active');
      document.body.classList.add("modal-open");
  }

  function closeModal() {
      if (!modal) {
          console.error("Modal element not found!");
          return;
      }
      modal.classList.remove('active');
      document.body.classList.remove("modal-open");
  }

  if (backButton) {
      backButton.addEventListener("click", closeModal);
  } else {
      console.error("Back button not found!");
  }

  if (loginBtn) {
      loginBtn.addEventListener("click", openModal);
  } else {
      console.error("Login button not found!");
  }

  window.openModal = openModal;
  window.closeModal = closeModal;
});
