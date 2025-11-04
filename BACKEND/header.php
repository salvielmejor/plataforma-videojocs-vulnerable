<?php
require_once __DIR__ . '/session.php';
// Detectar la página actual para mostrar el nav apropiado
$current_page = basename($_SERVER['PHP_SELF']);
$is_backend_page = strpos($_SERVER['REQUEST_URI'], '/BACKEND/') !== false;
?>

<header class="menu-superior">
    <nav>
        <?php if ($is_backend_page): ?>
            <!-- Nav para páginas del backend (usuarios logueados) -->
            <div class="nav-brand">
                <h2>POMASA LANDIA</h2>
            </div>
            <div class="nav-center">
                <a href="menu.php" class="<?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>">Inici</a>
                <a href="perfil.php" class="<?php echo ($current_page == 'perfil.php') ? 'active' : ''; ?>">Perfil</a>
                <a href="ranking.php" class="<?php echo ($current_page == 'ranking.php') ? 'active' : ''; ?>">Ranking</a>
            </div>
            <div class="nav-right">
                <a href="logout.php" class="logout-btn">Sortir</a>
            </div>
        <?php else: ?>
            <!-- Nav para páginas públicas -->
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Inici</a>
            <a href="registre.php" class="<?php echo ($current_page == 'registre.php') ? 'active' : ''; ?>">Registre</a>
            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="BACKEND/menu.php">Jocs</a>
                <a href="BACKEND/perfil.php">Perfil</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</header>