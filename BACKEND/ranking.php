<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['usuari_id'])) {
  header("Location: ../index.php");
  exit();
}

$host = 'localhost';
$user = 'plataforma_user';
$pass = '123456789a';
$dbname = 'plataforma_videojocs';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}

// Ranking global
$sql_global = "
    SELECT u.nom_usuari, SUM(pu.puntuacio_maxima) AS total_punts
    FROM progres_usuari pu
    INNER JOIN usuaris u ON pu.usuari_id = u.id
    GROUP BY u.id
    ORDER BY total_punts DESC
    LIMIT 10;
";
$result_global = $conn->query($sql_global);

// Ranking por juego
$sql_jocs = "SELECT id, nom_joc FROM jocs WHERE actiu = 1;";
$result_jocs = $conn->query($sql_jocs);

$jocs = [];
if ($result_jocs->num_rows > 0) {
    while ($row = $result_jocs->fetch_assoc()) {
        $joc_id = $row['id'];
        $nom_joc = $row['nom_joc'];

        $sql_ranking_joc = "
            SELECT u.nom_usuari, pu.puntuacio_maxima
            FROM progres_usuari pu
            INNER JOIN usuaris u ON pu.usuari_id = u.id
            WHERE pu.joc_id = $joc_id
            ORDER BY pu.puntuacio_maxima DESC
            LIMIT 10;
        ";
        $res_ranking = $conn->query($sql_ranking_joc);

        $ranking = [];
        while ($r = $res_ranking->fetch_assoc()) {
            $ranking[] = $r;
        }

        $jocs[] = [
            'nom' => $nom_joc,
            'ranking' => $ranking
        ];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8" />
  <title>Rànquing de Jugadors</title>
  <link rel="stylesheet" href="../css/menu_css.css" />
  <link rel="stylesheet" href="../css/header.css" />
  <link rel="stylesheet" href="../css/sanitize.css" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet" />
</head>
<body>

<?php include 'header.php'; ?>

<main class="panel-container ranking-container">
  <h2>Rànquing Global</h2>

  <section class="ranking ranking-total">
    <h3>TOP 10 GLOBAL</h3>
    <ol>
      <?php if ($result_global->num_rows > 0): ?>
        <?php while ($row = $result_global->fetch_assoc()): ?>
          <li><?= htmlspecialchars($row['nom_usuari']) ?> — <?= number_format($row['total_punts'], 0, ',', '.') ?> pts</li>
        <?php endwhile; ?>
      <?php else: ?>
        <li>No hi ha dades disponibles.</li>
      <?php endif; ?>
    </ol>
  </section>

  <div class="ranking-jocs">
    <?php foreach ($jocs as $joc): ?>
      <section class="ranking joc">
        <h3><?= htmlspecialchars($joc['nom']) ?></h3>
        <ol>
          <?php if (!empty($joc['ranking'])): ?>
            <?php foreach ($joc['ranking'] as $r): ?>
              <li><?= htmlspecialchars($r['nom_usuari']) ?> — <?= number_format($r['puntuacio_maxima'], 0, ',', '.') ?> pts</li>
            <?php endforeach; ?>
          <?php else: ?>
            <li>No hi ha dades per a aquest joc.</li>
          <?php endif; ?>
        </ol>
      </section>
    <?php endforeach; ?>
  </div>
</main>

<footer>
  <p>© 2025 Plataforma de Videojocs - Rànquing</p>
</footer>

</body>
</html>
