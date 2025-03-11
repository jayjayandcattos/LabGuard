document.addEventListener('DOMContentLoaded', function() {
    const button = document.getElementById('hoverButton');
    const hoverImage = document.getElementById('hoverImage');
    
    button.addEventListener('mouseenter', function() {
        hoverImage.style.opacity = '1';
    });
    
    button.addEventListener('mouseleave', function() {
        hoverImage.style.opacity = '0';
    });
});