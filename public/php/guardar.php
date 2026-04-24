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
    die("Error de configuración: la variable MONGODB_URI no está definida.");
}

// =============================================
//  Solo procesar si el método es POST
// =============================================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Método no permitido.");
}

// =============================================
//  Recoger y limpiar los datos del formulario
// =============================================
function limpiar(string $valor): string {
    return htmlspecialchars(trim($valor));
}

$nombres          = limpiar($_POST["nombres"]          ?? "");
$apellidos        = limpiar($_POST["apellidos"]        ?? "");
$cedula           = limpiar($_POST["cedula"]           ?? "");
$correo           = limpiar($_POST["correo"]           ?? "");
$fecha_nacimiento = limpiar($_POST["fecha_nacimiento"] ?? "");
$carrera          = limpiar($_POST["carrera"]          ?? "");
$semestre         = (int) ($_POST["semestre"]          ?? 0);
$modalidad        = limpiar($_POST["modalidad"]        ?? "");
$observaciones    = limpiar($_POST["observaciones"]    ?? "");

// Validación básica de campos requeridos
if (!$nombres || !$apellidos || !$cedula || !$correo || !$fecha_nacimiento || !$carrera || !$semestre || !$modalidad) {
    http_response_code(400);
    die("Por favor completa todos los campos requeridos.");
}

// =============================================
//  Guardar en MongoDB usando el driver nativo
//  (ext-mongodb — sin Composer)
// =============================================
try {
    $manager = new MongoDB\Driver\Manager($mongoUri);

    $documento = [
        "nombres"          => $nombres,
        "apellidos"        => $apellidos,
        "cedula"           => $cedula,
        "correo"           => $correo,
        "fecha_nacimiento" => $fecha_nacimiento,
        "carrera"          => $carrera,
        "semestre"         => $semestre,
        "modalidad"        => $modalidad,
        "observaciones"    => $observaciones,
        "fecha_registro"   => new MongoDB\BSON\UTCDateTime(),
    ];

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($documento);

    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY);
    $resultado    = $manager->executeBulkWrite("students.Customer", $bulk, ["writeConcern" => $writeConcern]);

    if ($resultado->getInsertedCount() === 1) {
        header("Location: ../index.html?estado=ok");
        exit;
    } else {
        throw new Exception("No se pudo insertar el documento.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo "<p style='color:red;font-family:Arial'>Error al guardar: " . htmlspecialchars($e->getMessage()) . "</p>";
}
