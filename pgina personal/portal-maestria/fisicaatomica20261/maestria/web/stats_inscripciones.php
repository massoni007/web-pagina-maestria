<?php
// Reportar pero ocultar errores visuales para no romper JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$archivo_csv = 'data/inscripciones_2026.csv';
$total_alumnos = 0;
$cursos_count = [];
$alumnos_unicos = []; // Array para trackear alumnos únicos por código o correo

if (file_exists($archivo_csv)) {
    if (($handle = fopen($archivo_csv, "r")) !== FALSE) {
        $first_row = true;
        
        while (($line = fgets($handle)) !== false) {
            // Eliminar espacios y retornos de carro
            $line = trim($line);
            if (empty($line)) continue;

            // Detectar si Excel lo guardó con punto y coma o coma
            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line, $separator);
            
            if ($first_row) {
                // Si la primera línea es BOM o cabecera
                if (stripos($line, 'Fecha de Inscripción') !== false || stripos($line, 'sep=') !== false) {
                   $first_row = false;
                   continue;
                }
                $first_row = false;
            }
            
            // Verificamos que al menos existan 5 columnas ['Fecha', 'Nombre', 'Código', 'Correo', 'Curso']
            if (count($data) >= 5) {
                $codigo = trim($data[2]); 
                $correo = trim($data[3]);
                $curso = trim($data[4]);
                
                $identificador = !empty($codigo) ? $codigo : $correo;
                if (!empty($identificador)) {
                    $alumnos_unicos[$identificador] = true;
                }
                
                if (!empty($curso)) {
                    if (!isset($cursos_count[$curso])) {
                        $cursos_count[$curso] = 0;
                    }
                    $cursos_count[$curso]++;
                }
            }
        }
        fclose($handle);
    }
}

// Ordenar alfabéticamente los cursos
ksort($cursos_count);

echo json_encode([
    'success' => true,
    'total_alumnos' => count($alumnos_unicos),
    'cursos' => $cursos_count
]);
?>
