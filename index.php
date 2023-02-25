<?php
$host = 'https://kroki.io/';
$cache = __DIR__.'/cache/';
$maximo_archivos_cache = 100;
eliminar_archivos_antiguos($cache, $maximo_archivos_cache);

$path = $_SERVER['QUERY_STRING'];
if( 'POST'== $_SERVER['REQUEST_METHOD'] ) {
    $postdata = file_get_contents('php://input');
    load($host, $cache, $path, $postdata);
} else {
    load($host, $cache, $path, '');
}

function load($host, $cache, $path, $body) {
    $file = $path;
    if( $body ) {
        $file .= base64_encode($body);
    }
    $parts = pathinfo( $file );
    $directory = $cache . '/' . $parts['dirname'];
    $file_name = sanitizar_nombre_archivo( $parts['basename'] );
    if( file_exists($directory . $file_name ) ) {
        echo file_get_contents( $directory . $file_name );
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host . $path); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        if( $body ) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $data = curl_exec($ch); 
        curl_close($ch); 

        if(!file_exists($directory) ) {
            mkdir($directory, 0777, true);
        }
        $file_w = fopen($directory . '/' . $file_name, 'w+');
        fwrite($file_w, $data);
        fclose($file_w);
        echo $data;
    }
}

function eliminar_archivos_antiguos($directorio, $max_ficheros) {
    // crea una instancia del iterador recursivo para el directorio
    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directorio),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    // crea un array para almacenar los detalles de los archivos
    $archivos = array();
    
    // recorre los archivos y agrega detalles al array
    foreach ($iterador as $archivo) {
        if ($archivo->isFile()) {
            $archivos[] = array(
                'ruta' => $archivo->getPathname(),
                'fecha' => $archivo->getMTime()
            );
        }
    }
    
    // ordena los archivos por fecha (más antiguo a más reciente)
    usort($archivos, function($a, $b) {
        return $a['fecha'] - $b['fecha'];
    });
    
    // elimina los archivos más antiguos hasta que quede el número máximo de archivos
    while (count($archivos) > $max_ficheros) {
        $archivo = array_shift($archivos);
        if (is_writable($archivo['ruta'])) {
            unlink($archivo['ruta']);
        } else {
            // manejar el error (no se puede borrar el archivo)
        }
    }
}

function sanitizar_nombre_archivo($nombre_archivo) {
    // elimina caracteres no permitidos, excepto letras, números, guiones y guiones bajos
    $nombre_archivo = preg_replace('/[^\w\-\.]/', '', $nombre_archivo);
    // limita el número de caracteres a 255 (el máximo para muchos sistemas de archivos)
    $nombre_archivo = substr($nombre_archivo, 0, 255);
    return $nombre_archivo;
}
