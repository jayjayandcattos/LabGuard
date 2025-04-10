function toggleForm() {
    const form = document.getElementById("roomForm");
    form.classList.toggle("hidden-form");

    if (form.classList.contains("hidden-form")) {
        form.style.display = "none"; 
    } else {
        form.style.display = "block"; 
    }
}
