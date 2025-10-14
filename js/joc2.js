const playArea = document.getElementById('play-area');
const scoreDisplay = document.getElementById('score');
const levelDisplay = document.getElementById('level');
const startBtn = document.getElementById('start-btn');
const winMessage = document.getElementById('win-message');

let score = 0;
let level = 1;
let monkeys = [];
let monkeySpeed = 2000;
let maxLevels = 5;

function createMonkey() {
  const monkey = document.createElement('div');
  monkey.classList.add('monkey');
  monkey.style.top = Math.random() * (playArea.clientHeight - 60) + 'px';
  monkey.style.left = Math.random() * (playArea.clientWidth - 60) + 'px';
  playArea.appendChild(monkey);
  monkeys.push(monkey);

  const moveInterval = setInterval(() => {
    monkey.style.top = Math.random() * (playArea.clientHeight - 60) + 'px';
    monkey.style.left = Math.random() * (playArea.clientWidth - 60) + 'px';
  }, monkeySpeed);
}

function throwBanana(x, y) {
  const banana = document.createElement('div');
  banana.classList.add('banana');
  banana.style.left = '385px';
  banana.style.top = '470px';
  playArea.appendChild(banana);

  const dx = x - 400;
  const dy = y - 470;
  const distance = Math.sqrt(dx * dx + dy * dy);
  const speed = 50;
  const steps = distance / speed;
  let step = 0;

  const interval = setInterval(() => {
    step++;
    banana.style.left = 385 + (dx / steps) * step + 'px';
    banana.style.top = 470 + (dy / steps) * step + 'px';

    monkeys.forEach(monkey => {
      const mRect = monkey.getBoundingClientRect();
      const bRect = banana.getBoundingClientRect();
      if (
        bRect.left < mRect.right &&
        bRect.right > mRect.left &&
        bRect.top < mRect.bottom &&
        bRect.bottom > mRect.top
      ) {
        monkey.remove();
        banana.remove();
        monkeys = monkeys.filter(m => m !== monkey);
        score += 10;
        scoreDisplay.textContent = 'Puntos: ' + score;
        checkLevelProgress();
        clearInterval(interval);
      }
    });

    if (step > steps) {
      banana.remove();
      clearInterval(interval);
    }
  }, 20);
}

function checkLevelProgress() {
  if (monkeys.length === 0) {
    level++;
    if (level > maxLevels) {
      winMessage.classList.remove('hidden');
      startBtn.disabled = false;
    } else {
      levelDisplay.textContent = 'Nivel: ' + level;
      monkeySpeed -= 300;
      startLevel();
    }
  }
}

function startLevel() {
  for (let i = 0; i < level + 2; i++) {
    createMonkey();
  }
}

startBtn.addEventListener('click', () => {
  score = 0;
  level = 1;
  monkeySpeed = 2000;
  scoreDisplay.textContent = 'Puntos: 0';
  levelDisplay.textContent = 'Nivel: 1';
  winMessage.classList.add('hidden');
  monkeys.forEach(m => m.remove());
  monkeys = [];
  startBtn.disabled = true;
  startLevel();
});

playArea.addEventListener('click', (e) => {
  const rect = playArea.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;
  throwBanana(x, y);
});
