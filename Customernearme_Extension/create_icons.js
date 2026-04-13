// Run with: node create_icons.js
// Requires: npm install canvas (optional) OR use pure JS canvas

const fs = require('fs');
const { createCanvas } = require('canvas');

const sizes = [16, 48, 128];

for (const size of sizes) {
  const canvas = createCanvas(size, size);
  const ctx = canvas.getContext('2d');

  // Background circle
  ctx.fillStyle = '#1a73e8';
  ctx.beginPath();
  ctx.arc(size/2, size/2, size/2, 0, Math.PI * 2);
  ctx.fill();

  // Pin shape
  ctx.fillStyle = 'white';
  const px = size * 0.3;
  const py = size * 0.18;
  const pw = size * 0.4;
  const pinH = size * 0.6;
  const r = pw / 2;
  const cx2 = size / 2;
  const cy2 = size * 0.42;

  ctx.beginPath();
  ctx.arc(cx2, cy2, r, 0, Math.PI * 2);
  ctx.fill();

  // Triangle pointing down
  ctx.beginPath();
  ctx.moveTo(cx2 - r * 0.7, cy2 + r * 0.5);
  ctx.lineTo(cx2 + r * 0.7, cy2 + r * 0.5);
  ctx.lineTo(cx2, cy2 + r * 2.2);
  ctx.closePath();
  ctx.fill();

  // Inner circle (hole)
  ctx.fillStyle = '#1a73e8';
  ctx.beginPath();
  ctx.arc(cx2, cy2, r * 0.42, 0, Math.PI * 2);
  ctx.fill();

  const buffer = canvas.toBuffer('image/png');
  fs.writeFileSync(`icons/icon${size}.png`, buffer);
  console.log(`Created icon${size}.png`);
}
