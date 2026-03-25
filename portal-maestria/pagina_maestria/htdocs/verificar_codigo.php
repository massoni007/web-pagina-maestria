<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$code = trim($_POST['code'] ?? '');

// Strict verification logic for production
if (isset($_SESSION['verification_code']) && $code === $_SESSION['verification_code']) {
    // Correct code
    unset($_SESSION['verification_code']); // Invalidate code after successful use 
    echo json_encode(['success' => true, 'message' => '¡Cuenta verificada con éxito!']);
} else {
    // Incorrect code
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'El código ingresado es incorrecto.']);
}

?>
