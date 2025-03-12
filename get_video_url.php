<?php
// Configurar encabezados para devolver JSON
header("Content-Type: application/json; charset=UTF-8");

// Función para hacer solicitudes cURL
function fetchStreamwishDirectLink($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva verificación SSL (cuidado en producción)
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    return null;
}

// Tu clave API y el file_code del video
$apiKey = "45j9zf8m0xz52jlb1"; // Tu clave API
$fileCode = "019okndocv4e";    // Reemplaza con el file_code real del video

// Construir la URL de la API para obtener el enlace directo
$directLinkUrl = "https://api.streamwish.com/api/file/direct_link?key=$apiKey&file_code=$fileCode";
$directData = fetchStreamwishDirectLink($directLinkUrl);

if ($directData && $directData['status'] == 200) {
    $m3u8Url = $directData['result']['url']; // El enlace .m3u8 con token actualizado
    echo json_encode(['status' => 'success', 'url' => $m3u8Url]);
} else {
    $errorMsg = $directData['msg'] ?? 'No se pudo obtener el enlace directo';
    echo json_encode(['status' => 'error', 'message' => $errorMsg]);
}
?>
