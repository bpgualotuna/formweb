<?php
// =============================================
//  Cargar .env manualmente (sin Composer)
//  Solo aplica en entorno local
// =============================================
$envFile = __DIR__ . "/../../.env";

if (file_exists($envFile)) {
    $lineas = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), "#")) continue; // ignorar comentarios
        if (str_contains($linea, "=")) {
            [$clave, $valor] = explode("=", $linea, 2);
            $_ENV[trim($clave)] = trim($valor);
        }
    }
}

// =============================================
//  Leer el URI de MongoDB desde el entorno
// =============================================
$mongoUri = $_ENV["MONGODB_URI"] ?? getenv("MONGODB_URI");

if (!$mongoUri) {
    http_response_code(500);
    die(json_encode(["error" => "Error de configuración: la variable MONGODB_URI no está definida."]));
}

header('Content-Type: application/json');

try {
    $manager = new MongoDB\Driver\Manager($mongoUri);
    $query = new MongoDB\Driver\Query([]);
    
    $cursor = $manager->executeQuery("students.Customer", $query);
    $result = [];
    foreach ($cursor as $document) {
        $result[] = $document;
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
