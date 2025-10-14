const logo = document.getElementById('logo');

let scaleUp = true;
setInterval(() => {
  logo.style.transform = scaleUp ? 'scale(1.1)' : 'scale(1)';
  scaleUp = !scaleUp;
}, 1000);
