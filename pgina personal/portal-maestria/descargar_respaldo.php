<?php
session_start();

// Validar seguridad: Solo el administrador puede descargar el respaldo
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("<h1>Acceso Denegado</h1><p>Debes iniciar sesión en el Panel de Administración para generar un respaldo.</p>");
}

// Configurar límites de servidor para evitar que se corte por tiempo si hay muchos PDFs
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Nombre del archivo a generar
$fecha = date('Y-m-d_H-i-s');
$zipName = "Respaldo_Maestria_PUCP_{$fecha}.zip";
$zipTempPath = sys_get_temp_dir() . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("<h1>Error de Sistema</h1><p>No se pudo inicializar el motor de compresión en el servidor.</p>");
}

/**
 * Función recursiva para añadir carpetas enteras al ZIP
 */
function enlazarCarpetaAZip($dirPath, $zip, $zipSubDir = '') {
    if (!is_dir($dirPath)) return;
    
    $archivos = array_diff(scandir($dirPath), array('.', '..'));
    foreach ($archivos as $archivo) {
        $rutaReal = $dirPath . '/' . $archivo;
        $rutaEnZip = ltrim($zipSubDir . '/' . $archivo, '/');
        
        if (is_dir($rutaReal)) {
            // Añadir el subdirectorio y navegar recursivamente
            $zip->addEmptyDir($rutaEnZip);
            enlazarCarpetaAZip($rutaReal, $zip, $rutaEnZip);
        } else {
            // Añadir el archivo físico
            if(is_readable($rutaReal)) {
                $zip->addFile($rutaReal, $rutaEnZip);
            }
        }
    }
}

// 1. CARPETA: PADRONES Y PREINSCRIPCIONES
$zip->addEmptyDir('1_Preinscripciones');
if(file_exists('fisicaatomica20261/maestria/web/data/inscripciones.csv')) {
    $zip->addFile('fisicaatomica20261/maestria/web/data/inscripciones.csv', '1_Preinscripciones/inscripciones_2026_2.csv');
}
if(file_exists('fisicaatomica20261/maestria/web/data/catalogo_cursos.json')) {
    $zip->addFile('fisicaatomica20261/maestria/web/data/catalogo_cursos.json', '1_Preinscripciones/catalogo_cursos.json');
}

// 2. CARPETA: ALUMNOS FISICA ATOMICA (Data CSV)
$zip->addEmptyDir('2_Datos_Generales_Alumnos');
if(file_exists('fisicaatomica20261/fisicaatomica/data/alumnos.csv')) {
    $zip->addFile('fisicaatomica20261/fisicaatomica/data/alumnos.csv', '2_Datos_Generales_Alumnos/padron_alumnos.csv');
}
if(file_exists('fisicaatomica20261/fisicaatomica/data/tareas.csv')) {
    $zip->addFile('fisicaatomica20261/fisicaatomica/data/tareas.csv', '2_Datos_Generales_Alumnos/registro_de_tareas_notas.csv');
}

// 3. CARPETA: TRABAJOS Y PDFs ENTREGADOS (Uploads físicos)
$zip->addEmptyDir('3_Trabajos_Entregados_PDFs');
enlazarCarpetaAZip('fisicaatomica20261/fisicaatomica/uploads', $zip, '3_Trabajos_Entregados_PDFs');

// 4. CARPETA: DOCUMENTOS Y MATERIALES DEL CURSO (Archivos subidos por Admin)
$zip->addEmptyDir('4_Materiales_Del_Profesor');
if(is_dir('data/uploads')) {
    enlazarCarpetaAZip('data/uploads', $zip, '4_Materiales_Del_Profesor');
}
// Añadir también los JSON de configuración de las tareas
if(file_exists('data/fa_tareas_def.json')) $zip->addFile('data/fa_tareas_def.json', '4_Materiales_Del_Profesor/estructura_tareas.json');
if(file_exists('data/fa_docs_def.json')) $zip->addFile('data/fa_docs_def.json', '4_Materiales_Del_Profesor/estructura_documentos.json');
if(file_exists('data/home_content.json')) $zip->addFile('data/home_content.json', '4_Materiales_Del_Profesor/anuncios_inicio.json');

// Cerrar y guardar el ZIP
$zip->close();

// Comprobar si realmente se generó
if (!file_exists($zipTempPath)) {
    die("<h1>Error de Sistema</h1><p>El paquete de respaldo se inicializó, pero está vacío o falló la escritura.</p>");
}

// Enviar cabeceras HTTP para forzar la descarga de la base de datos
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($zipTempPath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($zipTempPath));

// Vaciar buffers de salida para evitar que se corrompa el ZIP y proceder a enviar bytes
ob_clean();
flush();
readfile($zipTempPath);

// Borrar el archivo temporal del servidor una vez descargado
unlink($zipTempPath);
exit;
?>
