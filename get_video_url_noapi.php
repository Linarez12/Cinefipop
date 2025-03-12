<?php
// Configurar encabezados para devolver JSON
header("Content-Type: application/json; charset=UTF-8");

// Función para obtener el contenido de la página
function fetchPageContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva verificación SSL (cuidado en producción)
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// URL pública del video en Streamwish
$videoUrl = "https://streamwish.com/019okndocv4e"; // Reemplaza con la URL real del video

// Obtener el contenido de la página
$pageContent = fetchPageContent($videoUrl);

// Buscar el enlace .m3u8 en el HTML
preg_match('/(https?:\/\/[^\s\'"]+\.m3u8\?[^\'"]+)/i', $pageContent, $matches);

if (isset($matches[1])) {
    $m3u8Url = $matches[1];
    echo json_encode(['status' => 'success', 'url' => $m3u8Url]);
} else {
    // Si no se encuentra directamente, intentar inspeccionar el reproductor
    preg_match('/<iframe[^>]+src=["\'](.*?)["\']/i', $pageContent, $iframeMatch);
    if (isset($iframeMatch[1])) {
        $iframeUrl = $iframeMatch[1];
        $iframeContent = fetchPageContent($iframeUrl);
        preg_match('/(https?:\/\/[^\s\'"]+\.m3u8\?[^\'"]+)/i', $iframeContent, $iframeMatches);
        if (isset($iframeMatches[1])) {
            $m3u8Url = $iframeMatches[1];
            echo json_encode(['status' => 'success', 'url' => $m3u8Url]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo encontrar el enlace m3u8']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró iframe ni enlace m3u8']);
    }
}
?>
