document.getElementById("toggle-form-btn").addEventListener("click", function() {
    var form = document.getElementById("schedule-form");
    
    
    form.style.transition = "opacity 0.4s ease-out, transform 0.4s cubic-bezier(0.5, 0, 0, 1)";
    
    if (form.style.display === "none" || form.style.display === "") {
        
        form.style.display = "block";
        form.style.opacity = "0";
        form.style.transform = "translateY(-15px)";
        
        setTimeout(() => {
            form.style.opacity = "1";
            form.style.transform = "translateY(0)";
        }, 10);
    } else {
        
        form.style.opacity = "0";
        form.style.transform = "translateY(10px)"; 
        form.style.pointerEvents = "none"; 
        
        setTimeout(() => {
            form.style.display = "none";
            form.style.pointerEvents = "auto"; 
        }, 400); 
    }
});