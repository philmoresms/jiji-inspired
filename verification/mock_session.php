<?php
session_start();
$_SESSION['user_id'] = 999;
$_SESSION['user_name'] = 'Test Seller';
echo "Session mocked.";
