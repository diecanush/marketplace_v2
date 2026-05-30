<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
$data = file_get_contents('php://input');
if ($data) {
    file_put_contents(__DIR__ . '/browser_console.txt', $data . PHP_EOL, FILE_APPEND);
}
echo json_encode(['success' => true]);
