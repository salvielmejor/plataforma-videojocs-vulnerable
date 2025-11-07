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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de connexió: " . $e->getMessage()]);
    exit;
}

// Obtener el ID del usuario desde la sesión (nombre de usuario)
$nomUsuari = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT id FROM usuaris WHERE nom_usuari = ?");
$stmt->execute([$nomUsuari]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario de sesión no válido"]);
    exit;
}
$usuari_id = (int)$row['id'];

// Obtener el ID del juego (Fruit Ninja)
$stmt = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = 'Fruit Ninja'");
$stmt->execute();
$joc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joc) {
    // Si no existe el juego, devolver nivel 1 por defecto
    echo json_encode([
        "success" => true,
        "nivell_actual" => 1,
        "puntuacio_maxima" => 0
    ]);
    exit;
}
$joc_id = $joc["id"];

// Obtener el progreso del usuario
$stmt = $pdo->prepare("SELECT nivell_actual, puntuacio_maxima FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?");
$stmt->execute([$usuari_id, $joc_id]);
$progres = $stmt->fetch(PDO::FETCH_ASSOC);

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
?>

