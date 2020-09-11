<?php
function imgcode64($path){
    // Nombre de la imagen
    $path = "https://s.s-bol.com/imgbase0/imagebase3/large/FC/6/4/6/2/9200000123272646.jpg";
 
    // Extensión de la imagen
    $type = pathinfo($path, PATHINFO_EXTENSION);
    
    // Cargando la imagen
    $data = file_get_contents($path);
    
    // Decodificando la imagen en base64
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

    // Mostrando el código base64
    return $base64;
 }
?>