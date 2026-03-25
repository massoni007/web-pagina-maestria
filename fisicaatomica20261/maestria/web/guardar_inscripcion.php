<?php
// Evitar que errores o advertencias de PHP rompan el formato JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$nombre = trim($_POST['nombre'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$cursos_json = $_POST['cursos'] ?? '[]';
$cursos = json_decode($cursos_json, true);

if (!$email || empty($nombre) || empty($codigo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
    exit;
}

if (!is_array($cursos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Estructura de cursos inválida.']);
    exit;
}

$fecha = date('Y-m-d H:i:s');

if (!is_dir('data')) {
    mkdir('data', 0777, true);
}

$archivo_csv = 'data/inscripciones_2026.csv';

// Leer todas las lineas existentes
$lineas_a_mantener = [];
$cabecera = ['Fecha de Inscripción', 'Nombre del Alumno', 'Código del Alumno', 'Correo del Alumno', 'Curso Inscrito'];

if (file_exists($archivo_csv)) {
    if (($handle = fopen($archivo_csv, "r")) !== FALSE) {
        $first_row = true;
        while (($line = fgets($handle)) !== false) {
            $line_trim = trim($line);
            if (empty($line_trim)) continue;
            
            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line_trim, $separator);

            if ($first_row) {
                if (stripos($line_trim, 'Fecha de Inscripción') !== false || stripos($line_trim, 'sep=') !== false) {
                    $first_row = false;
                    continue; // Skip appending header from file, we'll write ours cleanly
                }
                $first_row = false;
            }
            
            if (count($data) >= 5) {
                $correo_csv = trim($data[3]);
                // Si el correo coincida, NO lo añadimos al array (es decir, lo "borramos")
                if (strtolower($correo_csv) !== strtolower($email)) {
                    $lineas_a_mantener[] = $data;
                }
            } else {
                $lineas_a_mantener[] = $data;
            }
        }
        fclose($handle);
    }
}

// Ahora escribir el archivo completo reescribiéndolo ('w' mode)
$file = @fopen($archivo_csv, 'w'); 

if (!$file) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: El archivo inscripciones_2026.csv está abierto en Excel u otro programa. ciérralo y vuelve a intentarlo.']);
    exit;
}

// Escribimos BOM para UTF-8 de Excel
fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); 
fputcsv($file, $cabecera, ',');

// Reescribir resto de estudiantes intocados
foreach ($lineas_a_mantener as $row) {
    fputcsv($file, $row, ',');
}

// Escribir las nuevas lineas o actualización
$insertado = true;
foreach($cursos as $curso) {
    // Escibimos una fila independiente por CADA curso seleccionado usando coma
    if (!fputcsv($file, [$fecha, $nombre, $codigo, $email, trim($curso)], ',')) {
        $insertado = false;
    }
}
fclose($file);

if ($insertado) {
    echo json_encode(['success' => true, 'message' => 'Inscripción procesada correctamente.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo escribir en el archivo. Verifica permisos del servidor.']);
}
?>
