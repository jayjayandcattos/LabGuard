<head>
    <link href="../css/prof.css" rel="stylesheet">
    <script src="../js/time.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Monomaniac+One&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/IDtap.svg" type="image/x-icon">
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo-brand">
            <img src="../assets/IDTap.svg" alt="Logo" class="logo">
            <h1 class="brand-name">LABGUARD</h1>
        </div>
           <div class="professor-header">
        <h1>PROFESSOR PROFILE</h1>
        <p>WELCOME PROFESSOR <?= htmlspecialchars($prof_lastname); ?>!</p>
    </div>
        <div class="time-container">
            <div class="text-wrapper">
                <div class="time-text" id="time">--:-- --</div>
                <div class="date-text" id="date">Loading...</div>
            </div>
        </div>
    </div>