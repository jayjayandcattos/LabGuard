function updateTime() {
    const now = new Date();
    

    let hours = now.getHours();
    let minutes = now.getMinutes();
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12; 
    hours = hours.toString().padStart(2, '0'); 
    minutes = minutes.toString().padStart(2, '0');


    const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
    let formattedDate = now.toLocaleDateString('en-US', options).replace(',', '');
    
    formattedDate = formattedDate.replace(/^(\w{3})/, (match) => match + 's').replace(' ', ' | ');


    document.getElementById('time').textContent = `${hours}:${minutes} ${ampm}`;
    document.getElementById('date').textContent = formattedDate;
  }


  setInterval(updateTime, 1000);
  updateTime();