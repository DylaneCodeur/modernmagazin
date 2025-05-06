<?php
require_once 'config.php';

// Destroy the session
session_destroy();

// Redirect to the home page
redirect('index.php');
?>