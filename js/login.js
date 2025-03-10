const modal = document.getElementById("loginModal");
const backButton = document.querySelector(".back-button");
const contentToBlur = document.querySelectorAll("body > *:not(.navbar)"); 

function openModal() {
    modal.style.display = "flex";
    document.body.classList.add("modal-open");
    contentToBlur.forEach(el => el.classList.add("content-to-blur"));
}

function closeModal() {
    modal.style.display = "none";
    document.body.classList.remove("modal-open");
    contentToBlur.forEach(el => el.classList.remove("content-to-blur"));
}

backButton.addEventListener("click", closeModal);
