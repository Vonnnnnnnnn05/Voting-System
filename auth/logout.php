<?php
require_once '../config/config.php';

logoutUser();
setFlash('success', 'You have been logged out successfully.');
redirect('/auth/login.php');
