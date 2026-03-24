<?php
/**
 * Main Entry Point for Jiji-Inspired-1.0
 */

// Define project root
define('ROOT_PATH', __DIR__);

// Check if installed
if (!file_exists(ROOT_PATH . '/config/config.php')) {
    header('Location: install/index.php');
    exit;
}

// Load configuration
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/inc/functions.php';
require_once ROOT_PATH . '/inc/auth.php';

// Route to public/index.php or handle routing here
// For simplicity in this vanilla PHP setup, we might use a simple router or include files
require_once ROOT_PATH . '/public/index.php';
