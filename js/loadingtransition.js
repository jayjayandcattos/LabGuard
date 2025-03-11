
  document.addEventListener("DOMContentLoaded", function () {
    
    document.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', function (e) {
        
        e.preventDefault();

        
        document.getElementById('loading').style.display = 'block';

        
        setTimeout(() => {
          
          window.location.href = link.href;
        }, 500); 
      });
    });
  });
