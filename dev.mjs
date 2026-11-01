import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.dirname(fileURLToPath(import.meta.url));
const npm = process.platform === 'win32' ? 'npm.cmd' : 'npm';

const children = [
  spawn(npm, ['run', 'dev'], { cwd: path.join(root, 'server'), stdio: 'inherit' }),
  spawn(npm, ['run', 'dev'], { cwd: path.join(root, 'web'), stdio: 'inherit' })
];

const shutdown = (code = 0) => {
  for (const child of children) {
    if (!child.killed) child.kill();
  }
  process.exit(code);
};

children.forEach((child) => {
  child.on('exit', (code) => {
    if (code && code !== 0) shutdown(code);
  });
});

process.on('SIGINT', () => shutdown(0));
process.on('SIGTERM', () => shutdown(0));

console.log('库洛汐 Kuluoxi · 前端 http://localhost:5173 · 接口 http://localhost:3001');
