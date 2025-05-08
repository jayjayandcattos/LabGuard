function toggleForm() {
    const form = document.getElementById("roomForm");
    const isHidden = form.classList.contains("hidden-form");
    
    if (isHidden) {
        // Show form with animation
        form.style.display = "block";
        form.style.height = "0";
        form.style.opacity = "0";
        form.style.overflow = "hidden";
        form.style.transition = "all 0.3s ease-in-out";
        
        // Trigger reflow to enable animation
        void form.offsetHeight;
        
        form.classList.remove("hidden-form");
        form.style.height = form.scrollHeight + "px";
        form.style.opacity = "1";
    } else {
        // Hide form with animation
        form.style.height = form.scrollHeight + "px";
        form.style.opacity = "1";
        form.style.overflow = "hidden";
        form.style.transition = "all 0.3s ease-in-out";
        
        // Trigger reflow to enable animation
        void form.offsetHeight;
        
        form.style.height = "0";
        form.style.opacity = "0";
        
        // After animation completes, hide completely
        setTimeout(() => {
            form.style.display = "none";
            form.classList.add("hidden-form");
            form.style.height = "";
            form.style.opacity = "";
            form.style.overflow = "";
            form.style.transition = "";
        }, 300); // Match this with transition duration
    }
}