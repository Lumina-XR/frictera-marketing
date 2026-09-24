import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const functionsPhp = readFileSync(join(root, 'frictera-theme/functions.php'), 'utf8');
const intakeJs = readFileSync(join(root, 'frictera-theme/assets/js/intake.js'), 'utf8');

test('theme defaults intake to disabled when config constant absent', () => {
  assert.match(functionsPhp, /define\('FRICTERA_INTAKE_ENABLED', false\)/);
  assert.match(functionsPhp, /if \(!FRICTERA_INTAKE_ENABLED\)/);
});

test('intake script blocks submission when enabled flag is off', () => {
  assert.match(intakeJs, /Submissions are not yet enabled/);
  assert.match(intakeJs, /isIntakeEnabled/);
});

test('REST processing rejects when intake is not live', () => {
  assert.match(functionsPhp, /if \(!frictera_intake_is_live\(\)\)/);
  assert.match(functionsPhp, /Intake submission is not yet enabled/);
});
