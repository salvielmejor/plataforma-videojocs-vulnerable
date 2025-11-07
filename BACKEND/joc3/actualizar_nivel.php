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

// Comprobar si se ha recibido JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['nivell_actual'])) {
    echo json_encode(["success" => false, "message" => "No s'han rebut dades vàlides"]);
    exit;
}

$nivell_actual = intval($data['nivell_actual']);

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

try {
    // Verificar si existe progreso
    $stmt = $conn->prepare("SELECT nivell_actual FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?");
    $stmt->bind_param("ii", $usuari_id, $joc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $progres = $result->fetch_assoc();
    $stmt->close();
    
    if ($progres) {
        // Solo actualizar si el nuevo nivel es mayor al actual
        if ($nivell_actual > $progres['nivell_actual']) {
            $stmt = $conn->prepare("UPDATE progres_usuari SET nivell_actual = ? WHERE usuari_id = ? AND joc_id = ?");
            $stmt->bind_param("iii", $nivell_actual, $usuari_id, $joc_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode([
                "success" => true,
                "message" => "Nivel actualizado correctamente",
                "nivell_anterior" => (int)$progres['nivell_actual'],
                "nivell_actual" => $nivell_actual
            ]);
        } else {
            echo json_encode([
                "success" => true,
                "message" => "El nivel no necesita actualización",
                "nivell_actual" => (int)$progres['nivell_actual']
            ]);
        }
    } else {
        // Crear nuevo progreso si no existe
        $stmt = $conn->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, ?, 0, 0, NOW())");
        $stmt->bind_param("iii", $usuari_id, $joc_id, $nivell_actual);
        $stmt->execute();
        $stmt->close();
        echo json_encode([
            "success" => true,
            "message" => "Progreso creado correctamente",
            "nivell_actual" => $nivell_actual
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al actualizar: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>

