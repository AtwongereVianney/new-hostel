<?php
// Logout script
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit; 