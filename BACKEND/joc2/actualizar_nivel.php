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

// Comprobar si se ha recibido JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['nivell_actual'])) {
    echo json_encode(["success" => false, "message" => "No s'han rebut dades vàlides"]);
    exit;
}

$nivell_actual = intval($data['nivell_actual']);
$usuari_id = intval($_SESSION['usuari_id']);

// Resolver el ID del juego por nombre (o crearlo si no existe)
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

