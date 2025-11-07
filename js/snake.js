const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

// API
const API_BASE = 'http://172.18.33.242/projecte_marcsalvi/BACKEND/api.php';
const GAME_NAME = 'Snake Pro';

const gridSize = 20;
let snake = [{ x: 200, y: 200 }];
let direction = { x: 0, y: 0 };
let food = randomPosition();
let score = 0;
let level = 1;
let speed = 100;
let levelConfig = null; // configuración actual del nivel desde la API
let particles = [];
let gameActive = true;
let initialLevel = 1; // Nivel inicial del usuario

function defaultLevelConfig(lvl) {
  const capped = Math.max(1, Math.min(lvl, 20));
  // Velocidad base 120ms, reduce 8ms por nivel hasta 40ms mínimo
  const speedMs = Math.max(40, 120 - (capped - 1) * 8);
  return { speedMs };
}

async function loadLevelConfig(lvl) {
  try {
    const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${encodeURIComponent(lvl)}`;
    const res = await fetch(url);
    if (res.ok) {
      return await res.json();
    }
    return defaultLevelConfig(lvl);
  } catch (e) {
    return defaultLevelConfig(lvl);
  }
}

async function saveLevelConfig(lvl, config, nomNivell) {
  try {
    const url = `${API_BASE}?joc_nom=${encodeURIComponent(GAME_NAME)}&nivell=${encodeURIComponent(lvl)}&only_if_missing=1`;
    await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ configuracio: config, nom_nivell: nomNivell || `Nivell ${lvl}` })
    });
  } catch (_) {}
}

async function loadUserProgress() {
  try {
    const response = await fetch('obtener_progreso.php');
    const data = await response.json();
    if (data.success) {
      return {
        nivell_actual: data.nivell_actual || 1,
        puntuacio_maxima: data.puntuacio_maxima || 0
      };
    }
    return { nivell_actual: 1, puntuacio_maxima: 0 };
  } catch (err) {
    console.error('Error al cargar progreso:', err);
    return { nivell_actual: 1, puntuacio_maxima: 0 };
  }
}

async function updateLevelProgress(newLevel) {
  try {
    const response = await fetch('actualizar_nivel.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ nivell_actual: newLevel })
    });
    const data = await response.json();
    if (data.success) {
      console.log('Nivel actualizado a:', newLevel);
    }
  } catch (err) {
    console.error('Error al actualizar nivel:', err);
  }
}

const scoreEl = document.getElementById("score");
const levelEl = document.getElementById("level");
const speedEl = document.getElementById("speed");

document.addEventListener("keydown", (e) => {
  // Si el juego no está activo y no hay un gameLoop, iniciarlo
  if (!gameLoop && gameActive) {
    gameLoop = setInterval(draw, speed);
  }
  
  switch (e.key) {
    case "ArrowUp": direction = { x: 0, y: -gridSize }; break;
    case "ArrowDown": direction = { x: 0, y: gridSize }; break;
    case "ArrowLeft": direction = { x: -gridSize, y: 0 }; break;
    case "ArrowRight": direction = { x: gridSize, y: 0 }; break;
  }
});

function randomPosition() {
  return {
    x: Math.floor(Math.random() * canvas.width / gridSize) * gridSize,
    y: Math.floor(Math.random() * canvas.height / gridSize) * gridSize
  };
}

function drawSegment(x, y, i) {
  const baseColor = `hsl(${(i * 30) % 360}, 100%, 50%)`;
  const gradient = ctx.createLinearGradient(x, y, x + gridSize, y + gridSize);
  gradient.addColorStop(0, "#000");
  gradient.addColorStop(0.3, baseColor);
  gradient.addColorStop(1, "#fff");

  ctx.shadowColor = "rgba(0,0,0,0.5)";
  ctx.shadowBlur = 5;
  ctx.shadowOffsetX = 2;
  ctx.shadowOffsetY = 2;

  ctx.fillStyle = gradient;
  ctx.fillRect(x, y, gridSize, gridSize);

  ctx.shadowBlur = 0;
}

function drawFood() {
  const gradient = ctx.createRadialGradient(
    food.x + gridSize / 2, food.y + gridSize / 2, 2,
    food.x + gridSize / 2, food.y + gridSize / 2, gridSize / 2
  );
  gradient.addColorStop(0, "#fff700");
  gradient.addColorStop(1, "#ff3cac");

  ctx.fillStyle = gradient;
  ctx.beginPath();
  ctx.arc(food.x + gridSize / 2, food.y + gridSize / 2, gridSize / 2, 0, Math.PI * 2);
  ctx.fill();
}

function drawParticles() {
  // Optimización: usar for loop en lugar de forEach para mejor rendimiento
  for (let i = particles.length - 1; i >= 0; i--) {
    const p = particles[i];
    ctx.fillStyle = `rgba(255,255,0,${p.alpha})`;
    ctx.beginPath();
    ctx.arc(p.x, p.y, 2 + (1 - p.alpha) * 3, 0, Math.PI * 2);
    ctx.fill();
    p.x += p.vx;
    p.y += p.vy;
    p.alpha -= 0.02;
    if (p.alpha <= 0) {
      particles.splice(i, 1);
    }
  }
}

function spawnParticles(x, y) {
  // Optimización: limitar número de partículas para mejor rendimiento
  const maxParticles = Math.min(8, 15 - particles.length);
  for (let i = 0; i < maxParticles; i++) {
    particles.push({
      x, y,
      vx: (Math.random() - 0.5) * 4,
      vy: (Math.random() - 0.5) * 4,
      alpha: 1
    });
  }
}

function updateStats() {
  scoreEl.textContent = `Puntuación: ${score}`;
  levelEl.textContent = `Nivel: ${level}`;
  speedEl.textContent = `Velocidad: ${speed}ms`;
}

function draw() {
  if (!gameActive) return;
  
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  drawParticles();
  drawFood();

  const head = { x: snake[0].x + direction.x, y: snake[0].y + direction.y };
  snake.unshift(head);

  if (head.x === food.x && head.y === food.y) {
    food = randomPosition();
    score++;
    spawnParticles(head.x, head.y);

    if (score % 5 === 0) {
      const newLevel = Math.min(1 + Math.floor(score / 5), 20);
      // El nivel nunca debe ser menor que el nivel inicial
      level = Math.max(newLevel, initialLevel);
      
      // Cargar config de nivel y ajustar velocidad
      (async () => {
        levelConfig = await loadLevelConfig(level);
        // Si la API no tiene, persistimos la por defecto para usos futuros
        if (!levelConfig || typeof levelConfig.speedMs !== 'number') {
          levelConfig = defaultLevelConfig(level);
        }
        saveLevelConfig(level, levelConfig);
        speed = levelConfig.speedMs;
        clearInterval(gameLoop);
        gameLoop = setInterval(draw, speed);
        
        // Si el nivel supera el nivel inicial, actualizar en la base de datos
        if (level > initialLevel) {
          await updateLevelProgress(level);
          initialLevel = level; // Actualizar el nivel inicial para futuras comparaciones
        }
      })();
    }
  } else {
    snake.pop();
  }

  if (
    head.x < 0 || head.x >= canvas.width ||
    head.y < 0 || head.y >= canvas.height ||
    snake.slice(1).some(s => s.x === head.x && s.y === head.y)
  ) {
    gameOver();
    return;
  }
  
  snake.forEach((s, i) => drawSegment(s.x, s.y, i));
  updateStats();
}

let gameLoop = null;

(async function bootstrap() {
  // Cargar el progreso del usuario
  let userProgress = await loadUserProgress();
  initialLevel = userProgress.nivell_actual || 1;
  level = initialLevel;
  
  // Calcular score inicial basado en el nivel (5 puntos por nivel)
  score = (initialLevel - 1) * 5;
  
  // Cargar configuración del nivel inicial
  // 1) Pre-crear si falta (evita 404 en el primer GET)
  await saveLevelConfig(level, defaultLevelConfig(level));
  // 2) Leer de la API
  levelConfig = await loadLevelConfig(level);
  if (!levelConfig || typeof levelConfig.speedMs !== 'number') {
    levelConfig = defaultLevelConfig(level);
  }
  speed = levelConfig.speedMs;
  updateStats();
  
  // Solo iniciar el juego si el usuario presiona una tecla
  // No auto-iniciar para permitir que el usuario vea su nivel
})();

// Función para manejar el fin del juego
function gameOver() {
  gameActive = false;
  clearInterval(gameLoop);
  
  // Mostrar pantalla de game over
  const gameOverDiv = document.getElementById('gameOver');
  const finalScoreEl = document.getElementById('finalScore');
  finalScoreEl.textContent = `Puntuación Final: ${score}`;
  gameOverDiv.style.display = 'block';
  
  // Guardar puntuación en la base de datos
  saveScore(score);
}

// Función para guardar la puntuación
async function saveScore(finalScore) {
  console.log('Intentando guardar puntuación:', finalScore);
  console.log('Usuario ID:', usuarioId);
  
  // Verificar si hay usuario logueado
  if (!usuarioId || usuarioId === 0) {
    alert('⚠️ No estás logueado. Por favor, inicia sesión para guardar tu puntuación.');
    return;
  }

  try {
    // Determinar el nivel máximo alcanzado durante la partida
    const maxLevelReached = Math.max(initialLevel, level);
    const shouldUpdateLevel = maxLevelReached > initialLevel;
    
    console.log('Enviando datos:', {
      usuari_id: usuarioId,
      joc_id: 3,
      puntuacio: finalScore,
      nivell_maximo_alcanzado: maxLevelReached,
      actualizar_nivel: shouldUpdateLevel
    });

    const response = await fetch('guardar.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        usuari_id: usuarioId,
        joc_id: 3,
        puntuacio: finalScore,
        nivell_maximo_alcanzado: maxLevelReached,
        actualizar_nivel: shouldUpdateLevel
      })
    });
    
    console.log('Respuesta recibida:', response.status);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('Resultado:', result);
    
    if (result.status === 'ok') {
      console.log('✅ Puntuación guardada:', result.message);
      if (result.message === 'Nueva puntuación máxima!') {
        alert(`¡${result.message}\nPuntuación: ${result.puntuacion}`);
        // Actualizar la mejor puntuación mostrada
        const bestElement = document.getElementById('best');
        if (bestElement) {
          bestElement.textContent = `Mejor: ${result.puntuacion}`;
        }
      } else {
        alert(`✅ ${result.message}`);
      }
    } else {
      console.error('❌ Error al guardar puntuación:', result.message);
      alert('❌ Error al guardar la puntuación: ' + result.message);
    }
  } catch (error) {
    console.error('❌ Error de conexión:', error);
    alert('❌ Error de conexión: ' + error.message);
  }
}

// Función para reiniciar el juego
async function restartGame() {
  const gameOverDiv = document.getElementById('gameOver');
  gameOverDiv.style.display = 'none';
  
  // Cargar el progreso del usuario de nuevo (por si se actualizó)
  let userProgress = await loadUserProgress();
  initialLevel = userProgress.nivell_actual || 1;
  
  // Reiniciar variables del juego
  gameActive = true;
  snake = [{ x: 200, y: 200 }];
  direction = { x: 0, y: 0 };
  score = (initialLevel - 1) * 5; // Score inicial basado en el nivel
  level = initialLevel;
  food = randomPosition();
  particles = [];
  
  // Cargar configuración del nivel inicial
  await saveLevelConfig(level, defaultLevelConfig(level));
  levelConfig = await loadLevelConfig(level);
  if (!levelConfig || typeof levelConfig.speedMs !== 'number') {
    levelConfig = defaultLevelConfig(level);
  }
  speed = levelConfig.speedMs;
  updateStats();
  
  // Limpiar canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  // Reiniciar el loop del juego
  clearInterval(gameLoop);
  gameLoop = setInterval(draw, speed);
}

