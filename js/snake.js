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
  particles.forEach((p, i) => {
    ctx.fillStyle = `rgba(255,255,0,${p.alpha})`;
    ctx.beginPath();
    ctx.arc(p.x, p.y, 2 + (1 - p.alpha) * 3, 0, Math.PI * 2);
    ctx.fill();
    p.x += p.vx;
    p.y += p.vy;
    p.alpha -= 0.02;
    if (p.alpha <= 0) particles.splice(i, 1);
  });
}

function spawnParticles(x, y) {
  for (let i = 0; i < 10; i++) {
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
    alert("💀 Game Over\nPuntuación: " + score);
    snake = [{ x: 200, y: 200 }];
    direction = { x: 0, y: 0 };
    score = 0;
    level = 1;
    speed = 100;
    food = randomPosition();
    clearInterval(gameLoop);
    gameLoop = setInterval(draw, speed);
  }

  snake.forEach((s, i) => drawSegment(s.x, s.y, i));
  updateStats();
}

let gameLoop = setInterval(draw, speed);
