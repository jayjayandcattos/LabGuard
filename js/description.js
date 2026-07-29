const texts = [
  "STUDENTS CAN ONLY LOG THEIR ATTENDANCE WHEN YOUR PROFESSOR IS PRESENT.",
  "PROFESSORS CAN ONLY LOG THEIR ATTENDANCE DURING THEIR CLASS SCHEDULE.",
  "SCAN YOUR EMPLOYEE ID OR STUDENT ID TO LOG YOUR DIGITAL ATTENDANCE.",
  "BETTER LATE THAN NEVER, BUT NEVER LATE IS BETTER. ATTEND YOUR CLASSES ON TIME!",
  "THANK YOU FOR USING LABGUARD!",
];

let currentIndex = 0;
const descriptionElement = document.getElementById("description");

function changeText() {
  descriptionElement.textContent = texts[currentIndex];
  currentIndex = (currentIndex + 1) % texts.length;
}

setInterval(changeText, 3000);
