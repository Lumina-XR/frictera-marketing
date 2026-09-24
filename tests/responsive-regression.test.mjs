import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const css = readFileSync(join(root, 'frictera-theme/style.css'), 'utf8');

test('contact grid uses minmax grid collapse for responsive layout', () => {
  assert.match(css, /\.page-contact \.contact-grid\.wp-block-columns[\s\S]*grid-template-columns: minmax\(0, 1fr\)/);
  assert.match(css, /grid-template-columns: minmax\(0, 1\.5fr\) minmax\(0, 1fr\)/);
});

test('option cards wrap words not characters', () => {
  assert.match(css, /\.option-card \.label[\s\S]*overflow-wrap: break-word/);
  assert.match(css, /\.option-card \.label[\s\S]*word-break: normal/);
});

test('evidence light mode uses readable foreground tokens', () => {
  assert.match(css, /html\[data-theme="light"\] \.evidence-label[\s\S]*color: var\(--frictera-text\)/);
  assert.match(css, /html\[data-theme="light"\] \.evidence-note[\s\S]*color: var\(--frictera-text-muted\)/);
});

test('hero and contact titles allow safe wrapping on narrow viewports', () => {
  assert.match(css, /\.hero h1[\s\S]*overflow-wrap: anywhere/);
  assert.match(css, /\.page-contact \.page-title[\s\S]*overflow-wrap: anywhere/);
});
