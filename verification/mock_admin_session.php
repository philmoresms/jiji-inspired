<?php
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_user'] = 'admin';
echo "Admin session mocked.";
