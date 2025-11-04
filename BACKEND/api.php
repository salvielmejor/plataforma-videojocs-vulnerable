<?php
// api_basica.php
require_once "./db_pdo.php";

// Capçaleres bàsiques
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Obtenim la URL sol·licitada
$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));

// Esperat (exemples):
// /projecte_marcsalvi/BACKEND/api.php/jocs/1/nivells/3
// /projecte_marcsalvi/BACKEND/api.php/jocs/nom/Fruit%20Ninja/nivells/3

$method = $_SERVER['REQUEST_METHOD'];

// Helper: obtenir joc_id a partir de id numèric o nom
function resolveJocId(PDO $pdo, array $uri): ?int {
    // Busquem els segments "jocs" i després si ve un id o el literal "nom"
    // ... ex: [..., 'api.php', 'jocs', '1', 'nivells', '3']
    // ... ex: [..., 'api.php', 'jocs', 'nom', 'Fruit Ninja', 'nivells', '3']
    $count = count($uri);
    for ($i = 0; $i < $count; $i++) {
        if ($uri[$i] === 'jocs') {
            if (isset($uri[$i+1]) && $uri[$i+1] === 'nom' && isset($uri[$i+2])) {
                $nom = urldecode($uri[$i+2]);
                $stmt = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = ?");
                $stmt->execute([$nom]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) return (int)$row['id'];
                return null;
            } elseif (isset($uri[$i+1])) {
                return (int)$uri[$i+1];
            }
        }
    }
    // Fallback per querystring
    if (isset($_GET['joc_id'])) {
        return (int)$_GET['joc_id'];
    }
    if (isset($_GET['joc_nom'])) {
        $nom = $_GET['joc_nom'];
        $stmt = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = ?");
        $stmt->execute([$nom]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];
    }
    return null;
}

// Helper: obtenir nivell des de la ruta
function resolveNivell(array $uri): ?int {
    $count = count($uri);
    for ($i = 0; $i < $count; $i++) {
        if ($uri[$i] === 'nivells' && isset($uri[$i+1])) {
            return (int)$uri[$i+1];
        }
    }
    if (isset($_GET['nivell'])) {
        return (int)$_GET['nivell'];
    }
    return null;
}

if ($method === 'GET') {
    $jocId = resolveJocId($pdo, $uri);
    $nivell = resolveNivell($uri);

    if ($jocId && $nivell !== null) {
        $stmt = $pdo->prepare("SELECT configuracio_json FROM nivells_joc WHERE joc_id = ? AND nivell = ?");
        $stmt->execute([$jocId, $nivell]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            // Retornem directament el JSON guardat
            echo $fila['configuracio_json'];
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Joc o nivell no trobat"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Ruta no vàlida. Exemples: /BACKEND/api.php/jocs/1/nivells/3 o /BACKEND/api.php/jocs/nom/Fruit%20Ninja/nivells/3"]);
    }
} elseif ($method === 'POST' || $method === 'PUT') {
    $jocId = resolveJocId($pdo, $uri);
    $nivell = resolveNivell($uri);
    $body = json_decode(file_get_contents('php://input'), true);

    // Si no existeix el joc i es passa per nom, el creem automàticament
    if ((!$jocId || $jocId === 0) && isset($_GET['joc_nom'])) {
        $nom = trim($_GET['joc_nom']);
        if ($nom !== '') {
            $stmtIns = $pdo->prepare("INSERT INTO jocs (nom_joc, descripcio, puntuacio_maxima, nivells_totals, actiu) VALUES (?, '', 0, 1, 1)");
            try {
                $stmtIns->execute([$nom]);
                $jocId = (int)$pdo->lastInsertId();
            } catch (Throwable $e) {
                // Si es duplica, recuperem l'id
                $stmtSel = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = ?");
                $stmtSel->execute([$nom]);
                $row = $stmtSel->fetch(PDO::FETCH_ASSOC);
                if ($row) { $jocId = (int)$row['id']; }
            }
        }
    }

    if (!$jocId || $nivell === null) {
        http_response_code(400);
        echo json_encode(["error" => "Ruta no vàlida o joc inexistent"]);
        exit;
    }

    if (!isset($body['configuracio'])) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el camp 'configuracio'"]);
        exit;
    }

    // JSON de configuració (validem que sigui un objecte/array)
    $configuracio = $body['configuracio'];
    if (!is_array($configuracio)) {
        http_response_code(400);
        echo json_encode(["error" => "'configuracio' ha de ser objecte JSON"]);
        exit;
    }

    $nomNivell = isset($body['nom_nivell']) ? trim($body['nom_nivell']) : null;

    // Inserció condicional si s'indica via query ?only_if_missing=1
    $onlyIfMissing = isset($_GET['only_if_missing']) && $_GET['only_if_missing'] == '1';

    // UPSERT: si existeix, actualitza; si no, insereix (o només insereix si falta)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT id FROM nivells_joc WHERE joc_id = ? AND nivell = ? FOR UPDATE");
        $stmt->execute([$jocId, $nivell]);
        $existeix = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existeix) {
            if (!$onlyIfMissing) {
                $sql = "UPDATE nivells_joc SET configuracio_json = :cfg" . ($nomNivell !== null ? ", nom_nivell = :nom" : "") . " WHERE joc_id = :joc AND nivell = :nivell";
                $stmt = $pdo->prepare($sql);
                $params = [
                    ':cfg' => json_encode($configuracio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ':joc' => $jocId,
                    ':nivell' => $nivell,
                ];
                if ($nomNivell !== null) $params[':nom'] = $nomNivell;
                $stmt->execute($params);
            }
        } else {
            $sql = "INSERT INTO nivells_joc (joc_id, nivell, nom_nivell, configuracio_json) VALUES (:joc, :nivell, :nom, :cfg)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':joc' => $jocId,
                ':nivell' => $nivell,
                ':nom' => $nomNivell,
                ':cfg' => json_encode($configuracio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
        $pdo->commit();
        echo json_encode(["ok" => true, "joc_id" => $jocId, "nivell" => $nivell]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "No s'ha pogut guardar la configuració", "detall" => $e->getMessage()]);
    }
} else {
    http_response_code(405); // Mètode no permès
    echo json_encode(["error" => "Mètode no suportat"]);
}

$pdo = null;
?>