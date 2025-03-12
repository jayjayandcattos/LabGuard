<!DOCTYPE html>
<html lang="en">

<head>
  <title>LabGuard</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="js/time.js" defer></script>
  <script src="js/loadingtransition.js" defer></script>
  <script src="js/description.js" defer></script>
  <link href="css/tailwind.css" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/IDtap.svg" type="image/x-icon">
</head>


<body>

  <?php include 'sections/nav.php'; ?>

  <div class="content-container">
    <h2>WELCOME TO LABGUARD</h2>
    <div class="white-line"></div>
    <div id="description">
      STUDENTS CAN ONLY LOG THEIR ATTENDANCE WHEN YOUR PROFESSOR IS PRESENT.
    </div>
    <div class="white-line"></div>
  </div>
  <div class="wrapper">
    <div class="scan-container">
      <img src="assets/IDtap.svg" alt="Scan ID" class="scan-image">
      <h2>PLEASE SCAN YOUR ID.</h2>
    </div>
    <div class="right-rectangle">
      <h2>RECENT TAPS</h2>
      <div class="recent-taps-content">
        <div class="section">
          <div class="table">
            <div class="table-header">
              <span>PHOTO</span>
              <span>NAME</span>
              <span>TIME IN</span>
              <span>STATUS</span>
            </div>
            <div class="table-row">
              <span>PLACEHOLDER</span>
              <span>PLACEHOLDER</span>
              <span>PLACEHOLDER</span>
            </div>
          </div>
        </div>
        <div class="section">
          <div class="table">
            <div class="table-header">
              <span>PHOTO</span>
              <span>NAME</span>
              <span>TIME IN</span>
              <span>STATUS</span>
            </div>
            <div class="table-row">
              <span>PLACEHOLDER</span>
              <span>PLACEHOLDER</span>
              <span>PLACEHOLDER</span>
            </div>
          </div>
        </div>

</body>

</html>