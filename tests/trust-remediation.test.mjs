import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const read = (relative) => readFileSync(join(root, relative), 'utf8');

const home = read('frictera-theme/templates/page-home.html');
const contact = read('frictera-theme/templates/page-contact.html');
const research = read('frictera-theme/templates/page-research.html');
const privacy = read('frictera-theme/templates/page-privacy.html');
const terms = read('frictera-theme/templates/page-terms.html');
const intakeJs = read('frictera-theme/assets/js/intake.js');

const publicMarketing = `${home}\n${contact}\n${research}`;

test('phase one removes unsupported SLA and public pricing from templates', () => {
  assert.doesNotMatch(publicMarketing, /Within two business days/i);
  assert.doesNotMatch(home, /From £1,500/);
  assert.doesNotMatch(publicMarketing, /£750/);
});

test('contact form excludes research conversation intent', () => {
  assert.doesNotMatch(contact, /Short research conversation/);
  assert.doesNotMatch(contact, /value="research-conversation"/);
  assert.match(research, /guided-intake research-intake/);
  assert.match(research, /research-intake/);
});

test('canonical Friction Review journey remains', () => {
  assert.equal(publicMarketing.includes('Book a Friction Review'), false);
  assert.match(contact, /Request a Friction Review/);
});

test('intake JS treats disabled flag consistently', () => {
  assert.match(intakeJs, /function isIntakeEnabled/);
  assert.match(intakeJs, /!isIntakeEnabled\(window\.fricteraIntake\.enabled\)/);
});

test('legal-held template bodies remain unchanged', () => {
  assert.match(privacy, /Frictera Limited/);
  assert.match(terms, /limited company registered in England and Wales/);
  assert.match(privacy, /may use analytics cookies/);
});
