<?php
// Display the debug log
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we should clear the log
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    $logFile = '../debug.log';
    file_put_contents($logFile, '');
    echo "Log cleared!";
    exit;
}

// Get the path to the debug log file
$logFile = '../debug.log';

// Check if the file exists
if (file_exists($logFile)) {
    // Read the file
    $log = file_get_contents($logFile);
    
    // Convert newlines to <br> tags for HTML display
    $log = nl2br($log);
    
    // Display the log
    echo "<h1>Debug Log</h1>";
    echo "<p><a href='?clear=1'>Clear Log</a></p>";
    echo "<div style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo $log;
    echo "</div>";
} else {
    echo "Log file does not exist.";
}
?> 