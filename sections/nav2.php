
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo-brand">
            <div class="back-button">
                <div class="circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </div>
            </div>
            <img src="assets/IDTap.svg" alt="Logo" class="logo">
            <h1 class="brand-name">LABGUARD</h1>
        </div>
        <div class="rectangle">
            <div class="nav-options">
                <div class="nav-option" onclick="window.location.reload();">HOME</div>
                    <div class="nav-option" id="aboutBtn" onclick="openAboutModal()">ABOUT</div>
                <div class="nav-option" id="loginBtn">LOGIN</div>
            </div>
        </div>
        <div class="time-container">
            <div class="text-wrapper">
                <div class="time-text" id="time">--:-- --</div>
                <div class="date-text" id="date">Loading...</div>
            </div>
        </div>
    </div>