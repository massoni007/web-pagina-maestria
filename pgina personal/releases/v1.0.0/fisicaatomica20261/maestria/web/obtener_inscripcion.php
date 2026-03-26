<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo inválido o no proporcionado.']);
    exit;
}

$archivo_csv = 'data/inscripciones_2026.csv';
$data_encontrada = null;

if (file_exists($archivo_csv)) {
    if (($handle = fopen($archivo_csv, "r")) !== FALSE) {
        $first_row = true;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;

            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line, $separator);
            
            if ($first_row) {
                if (stripos($line, 'Fecha de Inscripción') !== false || stripos($line, 'sep=') !== false) {
                   $first_row = false;
                   continue;
                }
                $first_row = false;
            }
            
            if (count($data) >= 5) {
                $nombre = trim($data[1]);
                $codigo = trim($data[2]);
                $correo_csv = trim($data[3]);
                $curso = trim($data[4]);
                
                if (strtolower($correo_csv) === strtolower($email)) {
                    if ($data_encontrada === null) {
                        $data_encontrada = [
                            'nombre' => $nombre,
                            'codigo' => $codigo,
                            'cursos' => []
                        ];
                    }
                    if (!empty($curso)) {
                        $data_encontrada['cursos'][] = $curso;
                    }
                }
            }
        }
        fclose($handle);
    }
}

if ($data_encontrada !== null) {
    echo json_encode(['success' => true, 'data' => $data_encontrada]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontraron registros previos.']);
}
?>
