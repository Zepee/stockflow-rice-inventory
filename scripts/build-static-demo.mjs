import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const source = resolve('public/demo.html');
const output = resolve('dist/stockflow-demo/index.html');

mkdirSync(dirname(output), { recursive: true });
copyFileSync(source, output);

console.log('Built the StockFlow static preview.');
