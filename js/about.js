const aboutModal = document.getElementById("aboutModal");
const aboutBackButton = document.querySelector(".back-button");
const aboutBtn = document.getElementById("aboutBtn"); 
const contentToBlur = document.querySelectorAll("body > *:not(.navbar)"); 

function openAboutModal() {
  aboutModal.classList.add('active');
  document.body.classList.add("modal-open");
}

function closeAboutModal() {
  aboutModal.classList.remove('active');
  document.body.classList.remove("modal-open");
}

aboutBackButton.addEventListener("click", closeAboutModal);

if (aboutBtn) {
  aboutBtn.addEventListener("click", openAboutModal);
}

window.openAboutModal = openAboutModal;
window.closeAboutModal = closeAboutModal;   