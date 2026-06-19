<?php
require_once __DIR__ . '/../includes/csrf.php';
csrf_start();
unset($_SESSION['master']);
header('Location: login.php');
