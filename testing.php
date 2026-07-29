<!DOCTYPE html>
<html lang="en">

<head>
    <title>LabGuard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="js/time.js" defer></script>
    <link href="css/tailwind.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="assets/logo.png" type="image/x-icon">


    <style>
        .folder-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .folder {
            width: 200px;
            height: 150px;
            background-color: #e0e0e0;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
            border-top-right-radius: 60px;
            position: relative;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        }

        .folder::before {
            content: '';
            position: absolute;
            top: -15px;
            right: 0;
            width: 60px;
            height: 30px;
            background-color: #e0e0e0;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.1);
        }

        .circle {
            width: 50px;
            height: 50px;
            background-color: green;
            border: 3px solid cyan;
            border-radius: 50%;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>

    <?php include 'sections/nav.php'; ?>


    <div class="folder-container">
  <div class="folder"></div>
  <div class="circle"></div>
</div>




</body>

</html>