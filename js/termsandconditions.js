
function openTermsModal() {
    const modal = document.getElementById("termsModal");
    modal.style.display = "block";
    
    
    void modal.offsetWidth;
    
    modal.classList.add("show");
    document.body.style.overflow = "hidden"; 
}

function closeTermsModal() {
    const modal = document.getElementById("termsModal");
    
    
    modal.classList.remove("show");
    modal.classList.add("hide");
    
    
    setTimeout(() => {
        modal.style.display = "none";
        modal.classList.remove("hide");
        document.body.style.overflow = "auto"; 
    }, 500); 
}

function acceptTerms() {
    
    
    localStorage.setItem("termsAccepted", "true");
    closeTermsModal();
    alert("You have accepted the Terms and Conditions.");
}

function declineTerms() {
    closeTermsModal();
    alert("You have declined the Terms and Conditions. Some features may be limited.");
}


document.addEventListener("DOMContentLoaded", function() {
    
    const termsLink = document.getElementById("termsLink");
    
    
    if (termsLink) {
        termsLink.addEventListener("click", function(e) {
            e.preventDefault();
            openTermsModal();
        });
    }
    
    
    window.addEventListener("click", function(event) {
        const modal = document.getElementById("termsModal");
        if (event.target.classList.contains("modal-overlay")) {
            closeTermsModal();
        }
    });
    
    
    document.addEventListener("keydown", function(event) {
        if (event.key === "Escape") {
            closeTermsModal();
        }
    });
});