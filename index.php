<?php
$cache = __DIR__.'/cache/';
eliminar_archivos_antiguos($cache, 2);

$path = $_SERVER['QUERY_STRING'];
if( 'POST'== $_SERVER['REQUEST_METHOD'] ) {
    $postdata = file_get_contents('php://input');
    load($cache, $path, $postdata);
} else {
    load($cache, $path, '');
}

function load($cache, $path, $body) {
    $host = 'https://kroki.io/';
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

function eliminar_archivos_antiguos($directorio, $max_archivos) {
    $archivos = glob($directorio . '/*');
    if (count($archivos) > $max_archivos) {
        // ordena los archivos por fecha de modificación
        array_multisort(array_map('filemtime', $archivos), SORT_ASC, $archivos);
        // elimina el archivo más antiguo
        unlink($archivos[0]);
        // llama de nuevo a la función para eliminar el siguiente archivo más antiguo
        eliminar_archivos_antiguos($directorio, $max_archivos);
    }
}

function sanitizar_nombre_archivo($nombre_archivo) {
    // elimina caracteres no permitidos, excepto letras, números, guiones y guiones bajos
    $nombre_archivo = preg_replace('/[^\w\-\.]/', '', $nombre_archivo);
    // limita el número de caracteres a 255 (el máximo para muchos sistemas de archivos)
    $nombre_archivo = substr($nombre_archivo, 0, 255);
    return $nombre_archivo;
}
