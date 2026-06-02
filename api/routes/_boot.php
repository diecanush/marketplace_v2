<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-HTTP-Method-Override');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/paths.php';
require_once PATH_API . '/config/db.php';
require_once PATH_API . '/helpers/core.php';
$db = (new Database())->connect();
$method = method();
