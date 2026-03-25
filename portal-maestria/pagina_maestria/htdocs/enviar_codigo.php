<?php
// Report errors but hide them, return clean JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');

// Incluir las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

try {
    session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    if (!isset($_POST['email'])) {
        throw new Exception('No se recibió el correo.');
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

    if (!$email) {
        throw new Exception('Por favor, ingresa un correo electrónico válido.');
    }

    // Generar código de 6 dígitos aleatorio
    $code = sprintf("%06d", mt_rand(1, 999999));
    $_SESSION['verification_code'] = $code;

    // --- Configuración e Instanciación de PHPMailer ---
    $mail = new PHPMailer(true);

    // Ajustes del Servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';             // Servidor SMTP de Google
    $mail->SMTPAuth   = true;                         // Habilitar autenticación SMTP
    $mail->Username   = 'massoni007@gmail.com';        // ¡CAMBIAR POR TU CORREO DE GMAIL!
    $mail->Password   = 'kfoq xyhk rbkg bkqd';// ¡CAMBIAR POR TU CONTRASEÑA DE APLICACIÓN DE GOOGLE!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // Encriptación implícita TLS
    $mail->Port       = 465;                          // Puerto TCP 

    // Opciones para saltar validación estricta SSL del entorno local
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Destinatarios y Remitente
    $mail->setFrom('massoni007@gmail.com', 'Acceso Maestria'); // Remitente
    $mail->addAddress($email);                                // Destinatario

    // Contenido del Email
    $mail->isHTML(true);                                      
    $mail->Subject = 'Tu Codigo de Verificacion de Acceso';
    $mail->CharSet = 'UTF-8';

    $mail->Body = "
    <html>
    <head>
        <title>Código de Verificación</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f5; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .code { font-size: 36px; font-weight: bold; color: #4f46e5; text-align: center; letter-spacing: 5px; margin: 20px 0; background: #e0e7ff; padding: 15px; border-radius: 8px; }
            .footer { margin-top: 30px; font-size: 12px; color: #6b7280; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Verificación de Acceso</h2>
            </div>
            <p>Hola,</p>
            <p>Has solicitado un código de verificación. Utiliza el siguiente código de 6 dígitos para continuar:</p>
            
            <div class='code'>{$code}</div>
            
            <p>Este código expira pronto y es de un solo uso.</p>
            
            <div class='footer'>
                &copy; " . date('Y') . " . Todos los derechos reservados.
            </div>
        </div>
    </body>
    </html>
    ";

    // Enviar el correo
    $mail->send();
    
    echo json_encode(['success' => true, 'message' => '¡Código enviado correctamente a tu correo!']);

} catch (Exception $e) {
    // Si PHPMailer falla (por ejemplo contraseña incorrecta), lo capturamos aquí
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el mensaje: ' . $mail->ErrorInfo
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
?>
