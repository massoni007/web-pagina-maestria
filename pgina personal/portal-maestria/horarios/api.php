<?php
session_start();

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Correct paths for your project structure
require '../fisicaatomica20261/fisicaatomica/PHPMailer/Exception.php';
require '../fisicaatomica20261/fisicaatomica/PHPMailer/PHPMailer.php';
require '../fisicaatomica20261/fisicaatomica/PHPMailer/SMTP.php';

$db_file = 'data/horarios_db.json';

// Helper to send email
function send_otp_email($to, $code) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        file_put_contents('otp_preview.txt', "To: $to\nCode: $code\nTime: " . date('Y-m-d H:i:s'));
        return true;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'massoni007@gmail.com';
        $mail->Password   = 'kfoq xyhk rbkg bkqd'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));

        $mail->setFrom('massoni007@gmail.com', 'Sistema Horarios PUCP');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Tu Código de Acceso - Mecánica Estadística';
        $mail->Body    = "Tu código de verificación es: <b>$code</b><br><br>Este código expirará en 10 minutos.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: file_put_contents('mail_error.log', $mail->ErrorInfo, FILE_APPEND);
        return false;
    }
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'send_code') {
    $email = $data['email'] ?? '';
    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Email requerido.']);
        exit;
    }

    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['temp_otp'] = $code;
    $_SESSION['temp_email'] = $email;

    if (send_otp_email($email, $code)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo.']);
    }
    exit;
}

if ($action === 'verify_code') {
    $code = $data['code'] ?? '';
    $tempOtp = $_SESSION['temp_otp'] ?? '';
    $tempEmail = $_SESSION['temp_email'] ?? '';

    if ($code && $code === $tempOtp) {
        $_SESSION['auth_email'] = $tempEmail;
        unset($_SESSION['temp_otp']);
        unset($_SESSION['is_schedule_admin']); // Ensure student login clears admin status
        echo json_encode(['success' => true, 'email' => $tempEmail]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Código incorrecto.']);
    }
    exit;
}

if ($action === 'check_auth') {
    echo json_encode(['success' => isset($_SESSION['auth_email']), 'email' => $_SESSION['auth_email'] ?? '']);
    exit;
}

if ($action === 'admin_login') {
    $pass = $data['password'] ?? '';
    if ($pass === '3333') {
        $_SESSION['is_schedule_admin'] = true;
        $_SESSION['auth_email'] = 'admin@maestria.pucp'; 
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Clave incorrecta.']);
    }
    exit;
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check_admin') {
    echo json_encode(['success' => isset($_SESSION['is_schedule_admin'])]);
    exit;
}

// Existing schedule handling logic
if ($action === 'save_schedule') {
    if (!isset($_SESSION['auth_email'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado.']);
        exit;
    }
    
    $email = $_SESSION['auth_email'];
    $name = $data['name'] ?? '';
    $grid = $data['grid'] ?? [];
    
    // Auto-role assignment
    $role = 'alumno';
    if ($email === 'admin@maestria.pucp' || isset($_SESSION['is_schedule_admin'])) {
        $role = 'profesor';
    }

    $db = json_decode(file_get_contents($db_file), true);
    if (!$db) $db = [];

    $found = false;
    foreach ($db as &$entry) {
        if ($entry['email'] === $email) {
            $entry['name'] = $name;
            $entry['grid'] = $grid;
            $entry['role'] = $role;
            $entry['updated'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }

    if (!$found) {
        $db[] = [
            'email' => $email,
            'name' => $name,
            'grid' => $grid,
            'role' => $role,
            'created' => date('Y-m-d H:i:s'),
            'updated' => date('Y-m-d H:i:s')
        ];
    }

    file_put_contents($db_file, json_encode($db));
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_my_schedule') {
    if (!isset($_SESSION['auth_email'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    $email = $_SESSION['auth_email'];
    $db = json_decode(file_get_contents($db_file), true);
    foreach ($db as $entry) {
        if ($entry['email'] === $email) {
            echo json_encode(['success' => true, 'data' => $entry]);
            exit;
        }
    }
    echo json_encode(['success' => false]);
    exit;
}

if ($action === 'get_all_schedules') {
    $db = json_decode(file_get_contents($db_file), true);
    echo json_encode(['success' => true, 'data' => $db]);
    exit;
}

if ($action === 'clear_all_schedules') {
    if (!isset($_SESSION['is_schedule_admin'])) {
        echo json_encode(['success' => false, 'message' => 'Solo el administrador puede borrar los datos.']);
        exit;
    }
    file_put_contents($db_file, json_encode([]));
    echo json_encode(['success' => true]);
    exit;
}
