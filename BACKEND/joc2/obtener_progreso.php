<?php
session_start();
header('Content-Type: application/json');

// Requiere usuario logueado
if (!isset($_SESSION['usuari_id'])) {
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

$usuari_id = intval($_SESSION['usuari_id']);

// Resolver el ID del juego por nombre (lo crea si no existe)
$joc_nom = 'Atrapa les Estrelles';
$stmt = $conn->prepare("SELECT id FROM jocs WHERE nom_joc = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Error prepare select joc: " . $conn->error]);
    exit;
}
$stmt->bind_param("s", $joc_nom);
$stmt->execute();
$resJoc = $stmt->get_result();
$joc_id = null;
if ($resJoc && $resJoc->num_rows > 0) {
    $joc_id = intval($resJoc->fetch_assoc()['id']);
} else {
    $stmtIns = $conn->prepare("INSERT INTO jocs (nom_joc, descripcio, puntuacio_maxima, nivells_totals, actiu) VALUES (?, '', 0, 3, 1)");
    if (!$stmtIns) {
        echo json_encode(["success" => false, "message" => "Error prepare insert joc: " . $conn->error]);
        exit;
    }
    $stmtIns->bind_param("s", $joc_nom);
    if (!$stmtIns->execute()) {
        echo json_encode(["success" => false, "message" => "Error execució insert joc: " . $stmtIns->error]);
        exit;
    }
    $joc_id = $conn->insert_id;
    $stmtIns->close();
}
$stmt->close();

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

