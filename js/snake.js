const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

const gridSize = 20;
let snake = [{ x: 200, y: 200 }];
let direction = { x: 0, y: 0 };
let food = randomPosition();
let score = 0;
let level = 1;
let speed = 100;
let particles = [];
let gameActive = true;

const scoreEl = document.getElementById("score");
const levelEl = document.getElementById("level");
const speedEl = document.getElementById("speed");

document.addEventListener("keydown", (e) => {
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
      level++;
      speed = Math.max(40, speed - 10);
      clearInterval(gameLoop);
      gameLoop = setInterval(draw, speed);
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

let gameLoop = setInterval(draw, speed);

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
    console.log('Enviando datos:', {
      usuari_id: usuarioId,
      joc_id: 3,
      puntuacio: finalScore
    });

    const response = await fetch('guardar.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        usuari_id: usuarioId,
        joc_id: 3,
        puntuacio: finalScore
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
function restartGame() {
  const gameOverDiv = document.getElementById('gameOver');
  gameOverDiv.style.display = 'none';
  
  // Reiniciar variables del juego
  gameActive = true;
  snake = [{ x: 200, y: 200 }];
  direction = { x: 0, y: 0 };
  score = 0;
  level = 1;
  speed = 100;
  food = randomPosition();
  particles = [];
  
  // Limpiar canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  
  // Reiniciar el loop del juego
  clearInterval(gameLoop);
  gameLoop = setInterval(draw, speed);
}

