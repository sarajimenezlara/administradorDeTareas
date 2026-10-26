<?php
session_start();
$env = parse_ini_file(".env");
foreach ($env as $key => $value) {
    putenv("$key=$value");
}

require_once("db.php");

// ?c= dice qué controlador usar y ?action= qué hace ese controlador
$c = isset($_GET['c']) ? $_GET['c'] : 'task';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';

if ($c === 'user') {
    require_once("controllers/userController.php");
    $controller = new UserController();
} else {
    require_once("controllers/taskController.php");
    $controller = new TaskController();
}

$controller->run($action);
?>
