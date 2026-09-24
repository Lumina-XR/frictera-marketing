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
const header = read('frictera-theme/parts/header.html');
const canonical = '/contact/';

function anchors(html, label) {
  const pattern = new RegExp(`<a\\b[^>]*>\\s*${label}\\s*</a>`, 'g');
  return [...html.matchAll(pattern)].map((match) => match[0]);
}

test('the contact page remains the only Friction Review form', () => {
  assert.equal(home.includes('<form'), false);
  assert.equal((home.match(/<(input|select|textarea)\b/g) || []).length, 0);
  assert.equal(home.includes('name="consent"'), false);
  assert.equal((contact.match(/<form\b/g) || []).length, 1);
  assert.match(contact, /class="guided-intake contact-form"/);
  assert.match(contact, /data-intake="contact"/);
  assert.match(contact, /data-rest-endpoint="intake\/contact"/);
  assert.match(contact, /action="\/contact-thanks\/"/);
  assert.match(contact, /method="GET"/);
  assert.match(contact, />Request a Friction Review</);
  assert.equal(
    createHash('sha256').update(readFileSync(join(root, 'frictera-theme/templates/page-contact.html'))).digest('hex'),
    '4da2b3ff4f365398e1449a121c5f67ec36f4c2eace587b84c7749d1385126328',
  );
});

test('header and home invitations use the same booking page', () => {
  const label = 'Request a Friction Review';
  const headerLinks = anchors(header, label);
  assert.equal(headerLinks.length, 2);
  for (const link of headerLinks) {
    assert.match(link, new RegExp(`href="${canonical}"`));
  }

  const homeLinks = anchors(home, label);
  assert.ok(homeLinks.length >= 2);
  for (const link of homeLinks) {
    assert.match(link, new RegExp(`href="${canonical}"`));
  }

  assert.equal(`${home}\n${header}\n${contact}`.includes('Book a Friction Review'), false);
  assert.match(contact, /<h1 class="wp-block-heading page-title">Request a Friction Review<\/h1>/);
  assert.match(home, /<section id="contact" class="section contact-section">/);
  assert.match(home, /<h2>Request a Friction Review<\/h2>/);
  assert.match(
    home,
    /<div class="booking-conversion">\s*<a href="\/contact\/" class="btn btn-primary btn-lg">Request a Friction Review<\/a>\s*<\/div>/,
  );
  assert.equal((home.match(/href="#contact"/g) || []).length, 2);
});
