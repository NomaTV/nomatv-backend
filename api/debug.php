<?php
header('Content-Type: application/json');

// Tentar ler de REQUEST_BODY (variável de ambiente do Node.js) ou php://input
$rawInput = $_SERVER['REQUEST_BODY'] ?? file_get_contents('php://input');
$decoded = json_decode($rawInput, true);

$debug = [
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
    'REQUEST_BODY_ENV' => $_SERVER['REQUEST_BODY'] ?? 'N/A',
    'raw_input' => $rawInput,
    'raw_input_length' => strlen($rawInput),
    'decoded' => $decoded,
    'POST' => $_POST,
    'GET' => $_GET,
    'action' => $decoded['action'] ?? 'N/A',
    'username' => $decoded['username'] ?? 'N/A'
];

echo json_encode($debug, JSON_PRETTY_PRINT);
?>