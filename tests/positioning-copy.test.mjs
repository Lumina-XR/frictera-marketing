import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const read = (relative) => readFileSync(join(root, relative), 'utf8');

const home = read('frictera-theme/templates/page-home.html');
const contact = read('frictera-theme/templates/page-contact.html');
const research = read('frictera-theme/templates/page-research.html');
const footer = read('frictera-theme/parts/footer.html');
const privacy = read('frictera-theme/templates/page-privacy.html');
const terms = read('frictera-theme/templates/page-terms.html');

test('hero and footer use approved positioning copy', () => {
  assert.match(home, /<span class="eyebrow">Operational intelligence<\/span>/);
  assert.match(home, /From friction to decision\./);
  assert.match(home, /With evidence behind it\./);
  assert.match(home, /governed next steps—so improvement is deliberate/);
  assert.match(footer, />Operational intelligence and governed intervention\.</);
});

test('workflow uses six approved stages without standalone Automate', () => {
  const labels = ['Observe', 'Understand', 'Value', 'Decide', 'Authorise', 'Verify'];
  for (const label of labels) {
    assert.match(home, new RegExp(`<span class="workflow-label">${label}</span>`));
  }
  assert.doesNotMatch(home, /<span class="workflow-label">Automate<\/span>/);
  assert.doesNotMatch(home, /Find the friction/);
  assert.doesNotMatch(home, /Prove the gain/);
});

test('economic consequence and automation callout are present', () => {
  assert.match(home, /Economic consequence/);
  assert.match(home, /Not every inefficiency deserves intervention\./);
  assert.match(home, /Automate only where automation earns its place\./);
});

test('Friction Review language stays canonical', () => {
  const marketing = `${home}\n${contact}\n${footer}\n${research}`;
  assert.equal(marketing.includes('Book a Friction Review'), false);
  assert.match(home, />Request a Friction Review</);
  assert.match(contact, /arrange a focused Friction Review\./);
  assert.match(
    home,
    /Tell us about one workflow creating delay, repeated effort or uncertainty\./,
  );
});

test('research purpose wording and legal bodies stay bounded', () => {
  assert.match(
    research,
    /operational intelligence and governed intervention offerings/,
  );
  assert.doesNotMatch(research, /design of governed automation services/);
  assert.equal(
    createHash('sha256').update(privacy).digest('hex'),
    '1a77a7eac4e7de4391ed23e087b34848177759dd9c33bc2f1302365399e3136f',
  );
  assert.equal(
    createHash('sha256').update(terms).digest('hex'),
    '699103374f52cbab8d8bf3afb49da9226e9d2a06cb0f298186687e88f9a24cc4',
  );
});

test('engineering numeric claims remain on home unchanged', () => {
  assert.match(home, /<span class="evidence-number">122<\/span>/);
  assert.match(home, /<span class="evidence-number">58<\/span>/);
  assert.doesNotMatch(home, /From £1,500/);
});
