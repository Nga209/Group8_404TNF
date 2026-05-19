<?php

// Start session
session_start();

// Include config
require_once '../config/config.php';

// Autoload core classes (Simple version)
require_once '../core/App.php';
require_once '../core/Controller.php';

// Init App
$app = new App();
