<?php
session_start();

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
    
    // IMPORTANTE: Como estás probando en una computadora local (Windows) que puede no tener servidor SMTP,
    // escribiremos el código en un archivo txt como "plan B" para que puedas comprobar que funcionó
    // sin tener que depender de si el correo sale o no de tu sistema local.
    // Una vez subido a InfinityFree, el correo sí saldrá sin problemas.
    $mensajeLog = "=== SOLICITUD DE ACCESO ADMIN ===\n" . 
                  "Fecha: " . date("Y-m-d H:i:s") . "\n" .
                  "Correo: $email\n" .
                  "CODIGO GENERADO: $codigo\n" .
                  "================================\n";
    file_put_contents('codigo_admin_recibido.txt', $mensajeLog);
    
    // Intentar enviar el mail real
    $subject = "Código de Acceso Administrativo - Maestría PUCP";
    $message = "Hola Eduardo Massoni,\n\nTu código de acceso de 6 dígitos para entrar al Panel de Gestión es: $codigo\n\nEste código caduca pronto. Si no fuiste tú quien solicitó el acceso, puedes ignorar este correo de manera segura.\n\n--\nPortal de Coordinación PUCP";
    $headers = "From: noreply@maestriafisica.pucp\r\n";
    
    // Silenciamos posibles errores de mail() en local usando '@'
    @mail($email, $subject, $message, $headers);
    
    echo json_encode(['success' => true, 'message' => 'Código generado.', 'debug_code' => $codigo]);
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

echo json_encode(['success' => false, 'message' => 'Acción no válida o no definida.']);
