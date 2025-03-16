<?php
// Configuración
$target_url = "https://hlsflex.com/e/2u5ldvh0be2f";

// Inicializar cURL
$ch = curl_init();

// Configurar opciones de cURL
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Ejecutar cURL y obtener contenido
$html_content = curl_exec($ch);

// Verificar errores
if(curl_errno($ch)) {
    die('Error de cURL: ' . curl_error($ch));
}

curl_close($ch);

// Variable para almacenar la URL del m3u8
$m3u8_url = "";

// Buscar patrones comunes para enlaces m3u8
$patterns = [
    '/source\s+src=[\'"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/',
    '/file:\s*[\'\"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/',
    '/[\'"]file[\'"]\s*:\s*[\'\"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/',
    '/hlsUrl\s*=\s*[\'\"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/',
    '/[\'"]url[\'"]\s*:\s*[\'\"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/',
    '/player\.src\(\s*{\s*src:\s*[\'\"](https?:\/\/[^"\']+\.m3u8[^\'"]*)[\'"]/'
];

// Buscar cualquiera de los patrones en el contenido
foreach ($patterns as $pattern) {
    if (preg_match($pattern, $html_content, $matches)) {
        $m3u8_url = $matches[1];
        break;
    }
}

// Si no encontramos una URL directa, busquemos en JavaScript embebido
if (empty($m3u8_url)) {
    // Buscar dentro de scripts
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html_content, $script_matches)) {
        foreach ($script_matches[1] as $script) {
            // Buscar cualquier URL que termine en .m3u8
            if (preg_match('/https?:\/\/[^\s\'"]+\.m3u8[^\s\'"]*/', $script, $url_match)) {
                $m3u8_url = $url_match[0];
                break;
            }
        }
    }
}

// Decodificar la URL si está codificada
$m3u8_url = str_replace('\\/', '/', $m3u8_url);

// Si aún no tenemos la URL, mostrar un mensaje de error
if (empty($m3u8_url)) {
    die("No se pudo encontrar la URL del stream m3u8. Es posible que el sitio haya cambiado su estructura.");
}

// Crear la página HTML con el reproductor
$player_html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproductor de Video</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/video.js/7.20.3/video-js.min.css" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #000;
        }
        .video-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .video-js {
            width: 100%;
            height: 100%;
        }
        .info-panel {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            z-index: 100;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        .info-panel:hover {
            opacity: 1;
        }
        .m3u8-url {
            word-break: break-all;
            font-size: 12px;
            margin-top: 5px;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <video id="my-video" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto">
            <source src="<?php echo htmlspecialchars($m3u8_url); ?>" type="application/x-mpegURL">
            <p class="vjs-no-js">
                Para ver este video, por favor habilita JavaScript y considera actualizar a un navegador web que
                <a href="https://videojs.com/html5-video-support/" target="_blank">soporte video HTML5</a>
            </p>
        </video>
    </div>
    
    <div class="info-panel">
        <h4 style="margin: 0 0 5px 0;">URL del Stream:</h4>
        <div class="m3u8-url"><?php echo htmlspecialchars($m3u8_url); ?></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/video.js/7.20.3/video.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/videojs-contrib-hls/5.15.0/videojs-contrib-hls.min.js"></script>
    <script>
        var player = videojs('my-video', {
            responsive: true,
            fluid: true,
            html5: {
                hls: {
                    overrideNative: true
                }
            }
        });
        
        player.play();
        
        // Ocultar el panel de información después de 10 segundos
        setTimeout(function() {
            document.querySelector('.info-panel').style.opacity = '0';
            // Eliminar después de finalizar la transición
            setTimeout(function() {
                document.querySelector('.info-panel').style.display = 'none';
            }, 300);
        }, 10000);
    </script>
</body>
</html>
HTML;

// Mostrar el reproductor
echo $player_html;
?>
