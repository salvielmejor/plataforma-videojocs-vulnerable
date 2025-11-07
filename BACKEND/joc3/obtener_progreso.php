<?php
session_start();
header('Content-Type: application/json');

// Requiere usuario logueado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No hay sesión iniciada"]);
    exit;
}

// Configuración de base de datos
$host = "localhost";
$dbname = "plataforma_videojocs";
$user = "plataforma_user";
$pass = "123456789a";

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Error de connexió: " . $conn->connect_error]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error de connexió: " . $e->getMessage()]);
    exit;
}

// Obtener el ID del usuario desde la sesión (nombre de usuario)
$nomUsuari = $_SESSION['usuario'];
$stmt = $conn->prepare("SELECT id FROM usuaris WHERE nom_usuari = ?");
$stmt->bind_param("s", $nomUsuari);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario de sesión no válido"]);
    exit;
}
$usuari_id = (int)$row['id'];
$stmt->close();

$joc_id = 3; // ID del juego "Snake Pro"

// Obtener el progreso del usuario
$stmt = $conn->prepare("SELECT nivell_actual, puntuacio_maxima FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?");
$stmt->bind_param("ii", $usuari_id, $joc_id);
$stmt->execute();
$result = $stmt->get_result();
$progres = $result->fetch_assoc();

if ($progres) {
    echo json_encode([
        "success" => true,
        "nivell_actual" => (int)$progres['nivell_actual'],
        "puntuacio_maxima" => (int)$progres['puntuacio_maxima']
    ]);
} else {
    // Si no existe progreso, devolver nivel 1 por defecto
    echo json_encode([
        "success" => true,
        "nivell_actual" => 1,
        "puntuacio_maxima" => 0
    ]);
}

$stmt->close();
$conn->close();
?>

