
const modal = document.getElementById("loginModal");
const backButton = document.querySelector(".back-button");
const loginBtn = document.getElementById("loginBtn"); 
const contentToBlur = document.querySelectorAll("body > *:not(.navbar)"); 


function openModal() {
  modal.classList.add('active');
  document.body.classList.add("modal-open");

}

function closeModal() {
  modal.classList.remove('active');
  document.body.classList.remove("modal-open");
  contentToBlur.forEach(el => el.classList.remove("content-to-blur"));
}

backButton.addEventListener("click", closeModal);


if (loginBtn) {
  loginBtn.addEventListener("click", openModal);
}


window.openModal = openModal;
window.closeModal = closeModal;