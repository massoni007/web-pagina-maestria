<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
header('Content-Type: application/json');
date_default_timezone_set('America/Lima'); // O la zona horaria que refiera

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'send_code') {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "error" => "Correo inválido"]); exit;
    }
    
    $code = sprintf("%06d", mt_rand(1, 999999));
    $_SESSION['verification_code'] = $code;
    $_SESSION['verification_email'] = $email;
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // Credenciales automáticas del profesor para pruebas
        $mail->Username   = 'massoni007@gmail.com';
        $mail->Password   = 'kfoq xyhk rbkg bkqd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
        
        $mail->setFrom('massoni007@gmail.com', 'Fisica Atomica');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Codigo de Acceso a Tareas';
        $mail->Body    = "<div style='font-family: Arial; padding: 20px; text-align: center;'><h2 style='color:#06b6d4'>Física Atómica 2026-1</h2><p>Tu código personal de acceso seguro es:</p><h1 style='background:#f1f5f9; padding:15px; border-radius:8px; letter-spacing:5px;'>{$code}</h1><p>Nunca compartas este código.</p></div>";
        
        $mail->send();
        echo json_encode(["success" => true, "debug_code" => $code]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => "Ocurrió un error con el correo: " . $mail->ErrorInfo]);
    }
    exit;
}

if ($action === 'verify_code') {
    $code = trim($_POST['code'] ?? '');
    $email = $_SESSION['verification_email'] ?? '';
    
    if (!$code || $code !== ($_SESSION['verification_code'] ?? null)) {
        echo json_encode(["success" => false, "error" => "El código de 6 dígitos es incorrecto o ha expirado"]); exit;
    }
    
    // Buscar al alumno en el padrón oficial (alumnos.csv)
    $csvAlumnos = 'data/alumnos.csv';
    $alumnoInfo = null;
    
    if (file_exists($csvAlumnos)) {
        $lines = file($csvAlumnos, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $data = str_getcsv($line);
            // Formato esperado: codigo,nombre,email,fecha_inscripcion
            if (isset($data[2]) && strcasecmp(trim($data[2]), $email) === 0) {
                $alumnoInfo = [
                    "codigo" => $data[0],
                    "nombre" => $data[1],
                    "fecha_inscripcion" => $data[3] ?? date('d-m-Y')
                ];
                break;
            }
        }
    }

    // Si no está en el CSV, manejamos como Invitado (Externo)
    if (!$alumnoInfo) {
        $alumnoInfo = [
            "codigo" => "EXT-" . strtoupper(substr(md5($email), 0, 4)),
            "nombre" => "Alumno Externo ($email)",
            "fecha_inscripcion" => date('d-m-Y')
        ];
    }
    
    echo json_encode([
        "success" => true,
        "alumno" => $alumnoInfo
    ]);
    exit;
}

if ($action === 'direct_test_login') {
    $codigoParam = trim($_POST['codigo'] ?? '');
    if (!$codigoParam) {
        echo json_encode(["success" => false, "error" => "Debes ingresar un código válido"]); exit;
    }
    
    // Buscar al alumno en el padrón oficial (alumnos.csv)
    $csvAlumnos = 'data/alumnos.csv';
    $alumnoInfo = null;
    
    if (file_exists($csvAlumnos)) {
        $lines = file($csvAlumnos, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $data = str_getcsv($line);
            // Formato esperado: codigo,nombre,email,fecha_inscripcion
            if (isset($data[0]) && strcasecmp(trim($data[0]), $codigoParam) === 0) {
                $alumnoInfo = [
                    "codigo" => $data[0],
                    "nombre" => $data[1],
                    "fecha_inscripcion" => $data[3] ?? date('d-m-Y')
                ];
                break;
            }
        }
    }

    // Si no está en el CSV, manejamos como Invitado (Externo) para pruebas
    if (!$alumnoInfo) {
        $alumnoInfo = [
            "codigo" => "TEST-" . strtoupper(substr(md5($codigoParam), 0, 4)),
            "nombre" => "Usuario de Prueba ($codigoParam)",
            "fecha_inscripcion" => date('d-m-Y')
        ];
    }
    
    echo json_encode([
        "success" => true,
        "alumno" => $alumnoInfo
    ]);
    exit;
}

if ($action === 'get_tasks') {
    $codigo = trim($_GET['codigo'] ?? '');
    $entregadas = [];
    
    if (file_exists('data/tareas.csv')) {
        $lines = file('data/tareas.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $data = str_getcsv($line, ',', '"', '\\');
            if ($data[0] === $codigo) {
                $entregadas[(int)$data[1]] = [
                    "fecha_entrega" => $data[2],
                    "archivo" => $data[3]
                ];
            }
        }
    }
    
    $defFile = '../../data/fa_tareas_def.json';
    $definiciones = [];
    if (file_exists($defFile)) {
        $definiciones = json_decode(file_get_contents($defFile), true) ?: [];
    }
    
    $docsFile = '../../data/fa_docs_def.json';
    $documentos = [];
    if (file_exists($docsFile)) {
        $documentos = json_decode(file_get_contents($docsFile), true) ?: [];
    }
    
    echo json_encode([
        "success" => true, 
        "entregadas" => $entregadas, 
        "definicion_tareas" => $definiciones,
        "documentos" => $documentos
    ]);
    exit;
}

if ($action === 'upload_task') {
    $codigo = trim($_POST['codigo'] ?? '');
    $tarea_num = (int)($_POST['tarea_num'] ?? 0);
    
    if (!$codigo || !$tarea_num || !isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "error" => "No se adjuntó archivo o hubo un error en la carga"]);
        exit;
    }
    
    $studentFolder = 'uploads/' . $codigo;
    if (!is_dir($studentFolder)) {
        mkdir($studentFolder, 0777, true);
    }
    
    $file = $_FILES['archivo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($ext !== 'pdf') {
        echo json_encode(["success" => false, "error" => "El sistema solo permite subir documentos en formato PDF (.pdf). Por favor transforma tu archivo."]);
        exit;
    }
    
    // Nombres seguros
    $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
    $relativeFilePath = $codigo . "/T" . $tarea_num . "_" . time() . "_" . $safeName;
    $targetPath = "uploads/" . $relativeFilePath;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Registrar en CSV
        $fecha = date('d-m-Y H:i:s');
        $row = [$codigo, $tarea_num, $fecha, $relativeFilePath];
        $fp = fopen('data/tareas.csv', 'a');
        fputcsv($fp, $row);
        fclose($fp);
        
        echo json_encode(["success" => true, "fecha" => $fecha, "archivo" => $relativeFilePath]);
    } else {
        echo json_encode(["success" => false, "error" => "El servidor no pudo guardar el archivo físico"]);
    }
    exit;
}

if ($action === 'delete_task') {
    $codigo = trim($_POST['codigo'] ?? '');
    $tarea_num = (int)($_POST['tarea_num'] ?? 0);
    
    if (!$codigo || !$tarea_num) {
        echo json_encode(["success" => false, "error" => "Datos insuficientes"]);
        exit;
    }
    
    $csvFile = 'data/tareas.csv';
    if (!file_exists($csvFile)) {
        echo json_encode(["success" => false, "error" => "No hay entregas registradas"]);
        exit;
    }
    
    $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    $found = false;
    $fileToDelete = '';
    
    foreach ($lines as $i => $line) {
        if ($i === 0) {
            $newLines[] = $line;
            continue;
        }
        $data = str_getcsv($line, ',', '"', '\\');
        if ($data[0] === $codigo && (int)$data[1] === $tarea_num) {
            $found = true;
            $fileToDelete = "uploads/" . $data[3];
            // No lo agregamos a newLines para "borrarlo"
        } else {
            $newLines[] = $line;
        }
    }
    
    if ($found) {
        // Guardar nuevo CSV
        file_put_contents($csvFile, implode("\n", $newLines) . "\n");
        // Borrar archivo físico si existe
        if ($fileToDelete && file_exists($fileToDelete)) {
            unlink($fileToDelete);
        }
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "No se encontró la entrega para borrar"]);
    }
    exit;
}

echo json_encode(["success" => false, "error" => "Ruta API no válida"]);
?>
