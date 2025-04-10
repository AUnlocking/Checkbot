<?php
// Token de tu bot (obtenido de @BotFather)
define('BOT_TOKEN', 'TU_TOKEN_AQUI');

// URL de la API de Telegram
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Función para enviar mensajes
function sendMessage($chat_id, $text) {
    $url = API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text);
    file_get_contents($url);
}

// Función para verificar IMEI con Apple
function checkImei($imei) {
    $url = 'https://selfsolve.apple.com/warrantyChecker.do?sn=' . urlencode($imei);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && !empty($response)) {
        $response = str_replace(['null(', ')'], '', $response);
        return json_decode($response, true);
    }
    return false;
}

// Procesar los mensajes recibidos
$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message']['text'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = $update['message']['text'];
    
    // Comando /start
    if ($text == '/start') {
        sendMessage($chat_id, "📱 *Bot de Verificación de IMEI iPhone*\n\nEnvíame un IMEI y te daré información de Apple.");
    } 
    // Verificar IMEI (si es un número)
    elseif (preg_match('/^\d{15}$/', $text)) {
        $data = checkImei($text);
        
        if (isset($data['ERROR_CODE'])) {
            sendMessage($chat_id, "❌ *Error*: IMEI inválido o no encontrado.");
        } elseif ($data) {
            $message = "✅ *Información del IMEI*: $text\n";
            $message .= "📱 *Modelo*: " . ($data['PART_DESCR'] ?? 'N/A') . "\n";
            $message .= "🌍 *País*: " . ($data['PURCH_COUNTRY'] ?? 'N/A') . "\n";
            $message .= "📅 *Garantía*: " . ($data['HW_COVERAGE_DESC'] ?? 'N/A') . "\n";
            $message .= "🔚 *Fin de garantía*: " . ($data['COV_END_DATE'] ?? 'N/A') . "\n";
            $message .= "📶 *Operador*: " . ($data['CARRIER'] ?? 'No bloqueado') . "\n";
            
            sendMessage($chat_id, $message);
        } else {
            sendMessage($chat_id, "⚠️ Error al conectar con Apple. Intenta más tarde.");
        }
    } else {
        sendMessage($chat_id, "🔢 Por favor, envía un IMEI válido (15 dígitos).");
    }
}
?>
