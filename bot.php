<?php
// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/error.log');

// Configuración del bot
$token = 'youtoken_bot';
$website = 'https://api.telegram.org/bot'.$token;

// Registro de la solicitud entrante
file_put_contents('request.log', date('Y-m-d H:i:s')." - ".file_get_contents('php://input')."\n", FILE_APPEND);

// Procesamiento de la entrada
$input = file_get_contents('php://input');
if (empty($input)) {
    error_log("Entrada vacía recibida");
    http_response_code(400);
    exit;
}

$update = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE || !isset($update['message'])) {
    error_log("JSON inválido o estructura incorrecta: ".$input);
    http_response_code(400);
    exit;
}

// Extracción segura de datos
$chatId = $update['message']['chat']['id'] ?? null;
$message = trim($update['message']['text'] ?? '');

if (empty($chatId) || empty($message)) {
    error_log("Chat ID o mensaje vacío");
    http_response_code(400);
    exit;
}

// Procesamiento de comandos
$responseText = processCommand($message, $chatId);
if ($responseText) {
    sendMessage($responseText, $chatId);
}

/**
 * Procesa los comandos del bot
 */
function processCommand(string $message, int $chatId): ?string
{
    if (strpos($message, '/check ') === 0) {
        sendMessage("<code>processing...</code>", $chatId);
        return check(substr($message, 7));
    }

    if (strpos($message, '/iccid ') === 0) {
        sendMessage("<code>processing...</code>", $chatId);
        return iccid(substr($message, 7));
    }

    if (strpos($message, '/check_device ') === 0) {
        sendMessage("<code>processing...</code>", $chatId);
        return checkmac(substr($message, 14));
    }

    return null;
}

/**
 * Función para verificar IMEI
 */
function check(string $imei): string 
{
    $response = makeApiRequest("https://iservices-dev.us/check/Nhteam.php?imei=".urlencode($imei));
    
    if (isset($response->ERROR) && $response->ERROR === 'Invalid IMEI/Serial Number') {
        return "<code>IMEI / SERIAL INVALID ❌</code>";
    }

    if (empty($response)) {
        return "<code>Error en la API de verificación</code>";
    }

    $lockStatus = ($response->FindMyiDevice == "ON") ? "❌" : "🍎✅";
    
    return "✅ 𝐢𝐀𝐥𝐝𝐚𝐳 𝐂𝐡𝐞𝐜𝐤 𝐁𝐨𝐭 ✅\n=========================\n"
         ."<code>SERIAL => </code><u>{$response->Serial}</u>\n"
         ."<code>MODEL => </code><u>{$response->Modelo}</u>\n"
         ."<code>Activation => </code><u>{$response->Activation}</u>\n"
         ."<code>iCloud Lock => </code><u>{$response->FindMyiDevice}</u> $lockStatus\n"
         ."<code>===========================\n\n𝑻𝒉𝒂𝒏𝒌𝒔 𝒀𝒐𝒖. ✅\niALDAZ </code>";
}

/**
 * Función para verificar dispositivo por MAC
 */
function checkmac(string $serial): string 
{
    $response = makeApiRequest("https://iservices-dev.us/check/");
    return $response ?: "<code>Error al verificar dispositivo</code>";
}

/**
 * Función para verificar ICCID
 */
function iccid(string $iccid): string 
{
    $response = makeApiRequest("https://iservices-dev.us/check/iccid.php?iccid=".urlencode($iccid));
    
    if (isset($response->ERROR) && $response->ERROR === 'Invalid IMEI/Serial Number') {
        return "<code>NO ICCID</code>";
    }

    if (empty($response)) {
        return "<code>Error en la API de ICCID</code>";
    }

    return "✅ iCCID ACTIVE ✅\n=========================\n"
         ."<code>Active date => </code><u>{$response->fecha}</u>\n"
         ."<code>BUILD => </code><u>{$response->build}</u>\n"
         ."<code>iccid => </code><u>{$response->iccid}</u> 🍎✅\n"
         ."<code>===========================\n\n𝑻𝒉𝒂𝒏𝒌𝒔 𝒀𝒐𝒖. ✅\niALDAZ </code>";
}

/**
 * Función genérica para solicitudes API
 */
function makeApiRequest(string $url) 
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response);
}

/**
 * Función para enviar mensajes
 */
function sendMessage(string $text, int $chatId): bool 
{
    global $website;
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($website.'/sendMessage', false, $context);
    
    if ($result === false) {
        error_log("Error al enviar mensaje a $chatId: ".print_r($data, true));
        return false;
    }
    
    return true;
}
