<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
logoutUser();

// Start fresh clean session for flash message
session_start();
session_regenerate_id(true);
setFlash('success', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/login.php');
exit;
