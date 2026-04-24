<?php

$envFile = __DIR__ . "/../../.env";

if (file_exists($envFile)) {
    $lineas = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (str_starts_with(trim($linea), "#")) continue; 
        if (str_contains($linea, "=")) {
            [$clave, $valor] = explode("=", $linea, 2);
            $_ENV[trim($clave)] = trim($valor);
        }
    }
}

$mongoUri = $_ENV["MONGODB_URI"] ?? getenv("MONGODB_URI");

if (!$mongoUri) {
    http_response_code(500);
    die("Configuration error: the MONGODB_URI variable is not defined.");
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Method not allowed.");
}


function limpiar(string $valor): string {
    return htmlspecialchars(trim($valor));
}

$fullName            = limpiar($_POST["fullName"]            ?? "");
$email               = limpiar($_POST["email"]               ?? "");
$age                 = (int) ($_POST["age"]                  ?? 0);
$studentCodingPrefer = limpiar($_POST["studentCodingPrefer"] ?? "");


if (!$fullName || !$email || !$age || !$studentCodingPrefer) {
    http_response_code(400);
    die("Please complete all required fields.");
}


try {
    $manager = new MongoDB\Driver\Manager($mongoUri);

    $documento = [
        "fullName"            => $fullName,
        "email"               => $email,
        "age"                 => $age,
        "studentCodingPrefer" => $studentCodingPrefer,
    ];

    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($documento);

    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY);
    $resultado    = $manager->executeBulkWrite("students.Customer", $bulk, ["writeConcern" => $writeConcern]);

    if ($resultado->getInsertedCount() === 1) {
        header("Location: ../index.html?estado=ok");
        exit;
    } else {
        throw new Exception("Could not insert the document.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo "<p style='color:red;font-family:Arial'>Error saving: " . htmlspecialchars($e->getMessage()) . "</p>";
}
