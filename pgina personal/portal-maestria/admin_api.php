<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'fisicaatomica20261/fisicaatomica/PHPMailer/Exception.php';
require 'fisicaatomica20261/fisicaatomica/PHPMailer/PHPMailer.php';
require 'fisicaatomica20261/fisicaatomica/PHPMailer/SMTP.php';

// Cabeceras para que responda en JSON
header('Content-Type: application/json');

// Recibir la petición fetch de Javascript
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: [];
$action = $data['action'] ?? ($_POST['action'] ?? '');

if ($action === 'check_session') {
    echo json_encode(['success' => true, 'logged_in' => isset($_SESSION['admin_logged_in'])]);
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['admin_logged_in']);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'send_code') {
    $email = 'massoni007@gmail.com';
    $codigo = sprintf("%06d", mt_rand(1, 999999));
    
    // Guardar el código en la sesión del servidor
    $_SESSION['admin_code'] = $codigo;
    
    // Intentar enviar el mail real con PHPMailer
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
        
        $mail->setFrom('massoni007@gmail.com', 'Coordinacion Maestria');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Codigo de Acceso Administrativo - Maestria PUCP';
        $mail->Body    = "<div style='font-family: Arial; padding: 20px; text-align: center;'><h2 style='color:#0f172a'>Panel de Gestión Administrativa</h2><p>Tu código personal de acceso seguro es:</p><h1 style='background:#f1f5f9; padding:15px; border-radius:8px; letter-spacing:5px;'>{$codigo}</h1><p>Nunca compartas este código con los estudiantes.</p></div>";
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Código enviado a tu correo.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => "Hubo un error al enviar el correo: " . $mail->ErrorInfo]);
    }
    exit;
}

if ($action === 'verify_code') {
    $codigoIngresado = $data['code'] ?? '';
    
    $codigoEsperado = $_SESSION['admin_code'] ?? null;
    
    // Recuperar el código del archivo .txt como respaldo en caso de que la sesión se pierda por configuraciones locales
    if (!$codigoEsperado && file_exists('codigo_admin_recibido.txt')) {
        $content = file_get_contents('codigo_admin_recibido.txt');
        if (preg_match('/CODIGO GENERADO: (\d{6})/', $content, $matches)) {
            $codigoEsperado = $matches[1];
        }
    }
    
    if ($codigoEsperado && $codigoIngresado === $codigoEsperado) {
        $_SESSION['admin_logged_in'] = true;
        
        // Destruir el código una vez usado por seguridad
        unset($_SESSION['admin_code']);
        if (file_exists('codigo_admin_recibido.txt')) {
            unlink('codigo_admin_recibido.txt'); // Borrar archivo temporal
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Código incorrecto. Intenta nuevamente.']);
    }
    exit;
}

$cursosFile = 'fisicaatomica20261/maestria/web/data/cursos.json';

if ($action === 'get_cursos') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado. Inicie sesión.']); exit;
    }
    if (file_exists($cursosFile)) {
        echo json_encode(['success'=>true, 'data'=>json_decode(file_get_contents($cursosFile), true)]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Archivo cursos.json no encontrado']);
    }
    exit;
}

if ($action === 'save_cursos') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado. inicie sesión.']); exit;
    }
    $nuevosDatos = $data['cursos'] ?? null;
    if ($nuevosDatos) {
        file_put_contents($cursosFile, json_encode($nuevosDatos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success'=>true, 'message'=>'Catálogo actualizado']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Datos de cursos vacíos o inválidos']);
    }
    exit;
}

if ($action === 'get_alumnos_y_tareas') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado.']); exit;
    }
    
    $alumnosFile = 'fisicaatomica20261/fisicaatomica/data/alumnos.csv';
    $tareasFile = 'fisicaatomica20261/fisicaatomica/data/tareas.csv';
    
    $alumnos = [];
    if (file_exists($alumnosFile)) {
        $lines = file($alumnosFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $data = str_getcsv($line, ',', '"', '\\');
            if (count($data) >= 2) {
                // Iniciar estructura básica por código
                $alumnos[$data[0]] = [
                    'codigo' => $data[0],
                    'nombre' => $data[1],
                    'email' => $data[2] ?? '',
                    'tareas' => []
                ];
            }
        }
    }
    
    if (file_exists($tareasFile)) {
        $lines = file($tareasFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $data = str_getcsv($line, ',', '"', '\\');
            if (count($data) >= 4) {
                $cod = $data[0];
                if (!isset($alumnos[$cod])) {
                    $alumnos[$cod] = [
                        'codigo' => $cod,
                        'nombre' => "Sin Nombre Registrado ($cod)",
                        'tareas' => []
                    ];
                }
                $alumnos[$cod]['tareas'][] = [
                    'tarea_num' => $data[1],
                    'fecha_entrega' => $data[2],
                    'archivo' => $data[3]
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'alumnos' => array_values($alumnos)]);
    exit;
}

// --- GESTIÓN DE CONTENIDOS DEL HOME (CMS) ---
$homeFile = 'data/home_content.json';

function getHomeData($file) {
    if (!file_exists(dirname($file))) @mkdir(dirname($file), 0777, true);
    if (!file_exists($file)) return ['anuncios' => [], 'documentos' => []];
    return json_decode(file_get_contents($file), true) ?: ['anuncios' => [], 'documentos' => []];
}

function saveHomeData($file, $data) {
    if (!file_exists(dirname($file))) @mkdir(dirname($file), 0777, true);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Obtener contenido del home (ACCESO PÚBLICO y ADMIN)
if ($action === 'get_home_content') {
    echo json_encode(['success' => true, 'data' => getHomeData($homeFile)]);
    exit;
}

// Seguridad: Los siguientes endpoints de escritura requieren Login Admin
if (in_array($action, ['save_home_announcement', 'delete_home_announcement', 'upload_home_document', 'delete_home_document'])) {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado. Inicia sesión como administrador.']); exit;
    }
}

if ($action === 'save_home_announcement') {
    $titulo = $data['titulo'] ?? '';
    $texto = $data['texto'] ?? '';
    $idToEdit = $data['id'] ?? '';
    
    if (!$titulo || !$texto || $texto === '<p><br></p>') {
        echo json_encode(['success'=>false, 'message'=>'El título y el texto del anuncio son estrictamente necesarios.']); exit;
    }
    
    $homeData = getHomeData($homeFile);
    
    if ($idToEdit) {
        // Editar existente
        $found = false;
        foreach ($homeData['anuncios'] as &$ann) {
            if ($ann['id'] === $idToEdit) {
                $ann['titulo'] = $titulo;
                $ann['texto'] = $texto;
                $ann['fecha'] = date('Y-m-d H:i:s'); // Actualizar fecha de modificación
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo json_encode(['success'=>false, 'message'=>'No se encontró el anuncio a editar.']); exit;
        }
        $msg = 'Anuncio actualizado con éxito en la portada.';
    } else {
        // Crear nuevo
        $newId = uniqid('ann_');
        array_unshift($homeData['anuncios'], [
            'id' => $newId,
            'titulo' => $titulo,
            'texto' => $texto,
            'fecha' => date('Y-m-d H:i:s')
        ]);
        $msg = 'Anuncio publicado con éxito en la portada.';
    }
    
    saveHomeData($homeFile, $homeData);
    echo json_encode(['success'=>true, 'message'=>$msg]);
    exit;
}

if ($action === 'delete_home_announcement') {
    $idToDel = $data['id'] ?? '';
    $homeData = getHomeData($homeFile);
    $homeData['anuncios'] = array_values(array_filter($homeData['anuncios'], function($a) use ($idToDel) {
        return $a['id'] !== $idToDel;
    }));
    
    saveHomeData($homeFile, $homeData);
    echo json_encode(['success'=>true, 'message'=>'Anuncio borrado definitivamente.']);
    exit;
}

if ($action === 'upload_home_document') {
    $titulo = $_POST['titulo'] ?? 'Documento Institucional';
    $uploadDir = 'data/uploads/home/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false, 'message'=>'El archivo no llegó por completo al servidor. Problema de red o peso excesivo.']); exit;
    }
    
    $fileInfo = pathinfo($_FILES['archivo']['name']);
    $ext = strtolower($fileInfo['extension']);
    // Normalizar nombre base con uniqid para evitar choques en descargas
    $safeName = date('ymdHis') . '_' . substr(md5(mt_rand()), 0, 6) . '.' . $ext;
    $targetPath = $uploadDir . $safeName;
    
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $targetPath)) {
        // Calcular tamaño human readable
        $bytes = filesize($targetPath);
        $size = ($bytes < 1048576) ? round($bytes/1024, 1) . ' KB' : round($bytes/1048576, 1) . ' MB';
        
        $tipo = 'Documento Genérico';
        if (in_array($ext, ['pdf'])) $tipo = 'Documento PDF';
        else if (in_array($ext, ['doc','docx'])) $tipo = 'Documento Word';
        else if (in_array($ext, ['xls','xlsx','csv'])) $tipo = 'Hoja de Cálculo';
        else if (in_array($ext, ['jpg','jpeg','png'])) $tipo = 'Imagen de Apoyo';
        else if (in_array($ext, ['zip','rar'])) $tipo = 'Carpeta Comprimida';
        
        $homeData = getHomeData($homeFile);
        $newId = uniqid('doc_');
        // Agregar al inicio del arreglo de documentos
        array_unshift($homeData['documentos'], [
            'id' => $newId,
            'titulo' => $titulo,
            'archivo_real' => $_FILES['archivo']['name'],
            'ruta' => $targetPath,
            'tipo' => $tipo,
            'size' => $size,
            'fecha' => date('Y-m-d H:i:s')
        ]);
        
        saveHomeData($homeFile, $homeData);
        echo json_encode(['success'=>true, 'message'=>'Documento inyectado en la red del portal.']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'No se pudo dar asilo al archivo en la memoria. Permisos bloqueados.']);
    }
    exit;
}

if ($action === 'delete_home_document') {
    $idToDel = $data['id'] ?? '';
    $homeData = getHomeData($homeFile);
    
    foreach ($homeData['documentos'] as $idx => $doc) {
        if ($doc['id'] === $idToDel) {
            // Eliminar archivo físico seguro
            if (file_exists($doc['ruta'])) @unlink($doc['ruta']);
            unset($homeData['documentos'][$idx]);
            break;
        }
    }
    $homeData['documentos'] = array_values($homeData['documentos']);
    saveHomeData($homeFile, $homeData);
    
    echo json_encode(['success'=>true, 'message'=>'El archivo ha sido destruido formalmente.']);
    exit;
}

// --- GESTIÓN DE DOCUMENTOS FÍSICA ATÓMICA ---
$faDocsFile = 'data/fa_docs_def.json';

function getFaDocsData($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveFaDocsData($file, $d) {
    file_put_contents($file, json_encode($d, JSON_PRETTY_PRINT));
}

if ($action === 'get_fa_docs') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado.']); exit;
    }
    echo json_encode(['success'=>true, 'data'=>getFaDocsData($faDocsFile)]);
    exit;
}

if ($action === 'delete_fa_doc') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado.']); exit;
    }
    $idToDel = $data['id'] ?? ($_POST['id'] ?? '');
    $docs = getFaDocsData($faDocsFile);
    foreach ($docs as $d) {
        if ($d['id'] == $idToDel) {
            $path = $d['archivo'];
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    }
    $docs = array_values(array_filter($docs, function($d) use ($idToDel) {
        return $d['id'] != $idToDel;
    }));
    saveFaDocsData($faDocsFile, $docs);
    echo json_encode(['success'=>true, 'message'=>'El documento ha sido borrado del portal de los alumnos.']);
    exit;
}

if ($action === 'save_fa_doc') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado.']); exit;
    }
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if (!$titulo) {
        echo json_encode(['success'=>false, 'message'=>'El título es requerido.']); exit;
    }
    
    $docs = getFaDocsData($faDocsFile);
    $savedFile = null;
    
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION));
        // Permitimos PDFs, Docs y PPTs
        if (!in_array($ext, ['pdf', 'docx', 'doc', 'zip', 'pptx', 'ppt', 'xlsx'])) {
            echo json_encode(['success'=>false, 'message'=>'Solo se permiten archivos comunes de clase (pdf, doc, zip, ppt, etc).']); exit;
        }
        $dir = 'data/uploads/fa_docs/';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['archivo_pdf']['name']);
        $filePath = $dir . 'DOC_' . time() . '_' . $safeName;
        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $filePath)) {
            $savedFile = $filePath;
        }
    }
    
    // Validar en FrontEnd si mandan archivo para nuevas inserciones, aqui es opcional solo en edicion
    
    if ($id) {
        foreach ($docs as &$d) {
            if ($d['id'] == $id) {
                $d['titulo'] = $titulo;
                $d['descripcion'] = $descripcion;
                if ($savedFile) {
                    $d['archivo'] = $savedFile;
                }
                break;
            }
        }
        $msg = "Se actualizó la información del documento.";
    } else {
        $maxId = 0;
        foreach ($docs as $d) {
            if ((int)$d['id'] > $maxId) $maxId = (int)$d['id'];
        }
        if (!$savedFile) {
            echo json_encode(['success'=>false, 'message'=>'Debes adjuntar un archivo físico para el nuevo documento.']); exit;
        }
        $docs[] = [
            'id' => $maxId + 1,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'archivo' => $savedFile,
            'fecha_creacion' => date('d/m/Y')
        ];
        $msg = "El documento se subió correctamente.";
    }
    
    saveFaDocsData($faDocsFile, $docs);
    echo json_encode(['success'=>true, 'message'=>$msg]);
    exit;
}

// --- GESTIÓN DE TAREAS FÍSICA ATÓMICA ---
$faTasksFile = 'data/fa_tareas_def.json';

function getFaTasksData($file) {
    if (!file_exists(dirname($file))) @mkdir(dirname($file), 0777, true);
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveFaTasksData($file, $data) {
    if (!file_exists(dirname($file))) @mkdir(dirname($file), 0777, true);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($action === 'get_fa_tasks') {
    echo json_encode(['success' => true, 'data' => getFaTasksData($faTasksFile)]);
    exit;
}

if ($action === 'save_fa_task' || $action === 'delete_fa_task') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado. Inicia sesión.']); exit;
    }
}

if ($action === 'save_fa_task') {
    $id = $_POST['id'] ?? ($data['id'] ?? '');
    $titulo = $_POST['titulo'] ?? ($data['titulo'] ?? '');
    $descripcion = $_POST['descripcion'] ?? ($data['descripcion'] ?? '');
    
    if (!$titulo) {
        echo json_encode(['success'=>false, 'message'=>'El título de la tarea es obligatorio.']); exit;
    }
    
    $tasks = getFaTasksData($faTasksFile);
    $savedFile = '';
    
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            echo json_encode(['success'=>false, 'message'=>'El archivo adjunto debe ser estrictamente PDF.']); exit;
        }
        $dir = 'data/uploads/fa_admin/';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES['archivo_pdf']['name']);
        $filePath = $dir . 'TAREA_' . time() . '_' . $safeName;
        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $filePath)) {
            $savedFile = $filePath;
        }
    }
    
    if ($id) {
        // Edit existing
        foreach ($tasks as &$t) {
            if ($t['id'] == $id) {
                $t['titulo'] = $titulo;
                $t['descripcion'] = $descripcion;
                if ($savedFile) {
                    $t['archivo'] = $savedFile;
                }
                break;
            }
        }
        $msg = "Se actualizaron las instrucciones de la tarea.";
    } else {
        // Add new
        $maxId = 0;
        foreach ($tasks as $t) {
            if ((int)$t['id'] > $maxId) $maxId = (int)$t['id'];
        }
        $tasks[] = [
            'id' => $maxId + 1,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'archivo' => $savedFile,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        $msg = "Se habilitó la nueva asignación para los estudiantes.";
    }
    
    saveFaTasksData($faTasksFile, $tasks);
    echo json_encode(['success'=>true, 'message'=>$msg]);
    exit;
}

if ($action === 'delete_fa_task') {
    $idToDel = $data['id'] ?? '';
    $tasks = getFaTasksData($faTasksFile);
    $tasks = array_values(array_filter($tasks, function($t) use ($idToDel) {
        return $t['id'] != $idToDel;
    }));
    saveFaTasksData($faTasksFile, $tasks);
    echo json_encode(['success'=>true, 'message'=>'La tarea ha sido retirada del portal de los alumnos.']);
    exit;
}

if ($action === 'reset_fa_system') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado. Inicia sesión.']); exit;
    }
    
    $tareasFile = 'fisicaatomica20261/fisicaatomica/data/tareas.csv';
    $alumnosFile = 'fisicaatomica20261/fisicaatomica/data/alumnos.csv';
    $faTasksFile = 'data/fa_tareas_def.json';
    
    // Función auxiliar para limpiar carpetas sin borrarlas
    function clearDirectory($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                clearDirectory($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    // 1. Limpiar Archivos de Tareas (Submissions)
    if (file_exists($tareasFile)) {
        file_put_contents($tareasFile, "codigo,tarea_num,fecha_entrega,archivo\n");
    }
    
    // 2. Limpiar Lista de Alumnos
    if (file_exists($alumnosFile)) {
        file_put_contents($alumnosFile, "codigo,nombre,fecha_inscripcion\n");
    }
    
    // 3. Limpiar Definición de Tareas (Assignments)
    file_put_contents($faTasksFile, json_encode([], JSON_PRETTY_PRINT));
    
    // 4. Borrar archivos físicos (entregas de alumnos)
    clearDirectory('fisicaatomica20261/fisicaatomica/uploads/');
    
    // 5. Borrar archivos físicos (enunciados del profesor)
    clearDirectory('data/uploads/fa_admin/');
    
    // 6. Borrar definicion de documentos y archivos físicos
    file_put_contents($faDocsFile, json_encode([], JSON_PRETTY_PRINT));
    clearDirectory('data/uploads/fa_docs/');
    
    echo json_encode(['success' => true, 'message' => 'Sistema de Física Atómica reiniciado correctamente. Todos los datos han sido borrados.']);
    exit;
}

if ($action === 'upload_fa_alumnos_csv') {
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success'=>false, 'message'=>'No autorizado.']); exit;
    }
    
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false, 'message'=>'El archivo no se recibió correctamente en el servidor.']); exit;
    }
    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    if ($ext === 'xls' || $ext === 'xlsx') {
        echo json_encode(['success'=>false, 'message'=>'Formato incorrecto. El sistema detectó un archivo Excel, pero requiere un archivo de texto en formato CSV. Abre el archivo en Excel, selecciona "Guardar como" y elige "CSV (delimitado por comas)".']); exit;
    }

    $content = file_get_contents($_FILES['archivo']['tmp_name']);
    
    // Suprimir warnings temporalmente para que no rompan el JSON
    $old_er = error_reporting(0);
    
    // Detectar y convertir codificación si es necesario (PUCP suele usar ISO-8859-1)
    if (function_exists('mb_convert_encoding') && !mb_check_encoding($content, 'UTF-8')) {
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
    }
    
    // Usar un stream de memoria para procesar el CSV
    $tempStream = fopen('php://temp', 'r+');
    fwrite($tempStream, $content);
    rewind($tempStream);

    $alumnos = [];
    $headerFound = false;
    $colIndices = ['codigo' => -1, 'nombre' => -1, 'email' => -1];
    
    // Detectar delimitador
    $firstLine = fgets($tempStream);
    rewind($tempStream);
    $delimiter = (strpos($firstLine, ";") !== false) ? ";" : ",";

    while (($data = fgetcsv($tempStream, 2000, $delimiter)) !== FALSE) {
        if (empty($data) || count($data) < 2) continue;
        
        if (!$headerFound) {
            foreach ($data as $idx => $val) {
                $val = trim($val);
                if (strcasecmp($val, "Alumno") === 0 || strcasecmp($val, "Código") === 0) $colIndices['codigo'] = $idx;
                if (strcasecmp($val, "Nombre") === 0) $colIndices['nombre'] = $idx;
                if (stripos($val, "E-mail") !== false || stripos($val, "Correo") !== false) $colIndices['email'] = $idx;
            }
            if ($colIndices['codigo'] !== -1 && $colIndices['nombre'] !== -1) {
                $headerFound = true;
            }
            continue;
        }
        
        $codigo = trim($data[$colIndices['codigo']] ?? '');
        $nombre = trim($data[$colIndices['nombre']] ?? '');
        $email = ($colIndices['email'] !== -1) ? trim($data[$colIndices['email']] ?? '') : '';
        
        if ($email && strpos($email, ",") !== false) {
            $email = trim(explode(",", $email)[0]);
        }
        
        if (is_numeric($codigo) && $nombre !== "" && $nombre !== "Nombre") {
            $alumnos[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'email' => strtolower($email),
                'fecha' => date('d-m-Y')
            ];
        }
    }
    fclose($tempStream);
    
    if (count($alumnos) > 0) {
        $csvPath = 'fisicaatomica20261/fisicaatomica/data/alumnos.csv';
        if (!is_dir(dirname($csvPath))) @mkdir(dirname($csvPath), 0777, true);
        
        $fp = @fopen($csvPath, 'w');
        if (!$fp) {
            echo json_encode(['success' => false, 'message' => 'No se puede escribir en alumnos.csv. Verifique permisos.']); exit;
        }
        fputcsv($fp, ['codigo', 'nombre', 'email', 'fecha_inscripcion']);
        foreach ($alumnos as $al) {
            fputcsv($fp, [$al['codigo'], $al['nombre'], $al['email'], $al['fecha']]);
        }
        fclose($fp);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Padrón actualizado. Se importaron ' . count($alumnos) . ' alumnos.',
            'count' => count($alumnos)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se detectaron datos válidos. Verifique las columnas "Alumno" y "Nombre".']);
    }
    
    // Restaurar nivel de errores
    error_reporting($old_er);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida o no definida.']);
