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
  assert.equal(home.includes('Request a Friction Review'), false);
  assert.equal((contact.match(/<form\b/g) || []).length, 1);
  assert.match(contact, /class="guided-intake contact-form"/);
  assert.match(contact, /data-intake="contact"/);
  assert.match(contact, /data-rest-endpoint="intake\/contact"/);
  assert.match(contact, /action="\/contact-thanks\/"/);
  assert.match(contact, /method="GET"/);
  assert.match(contact, />Request a Friction Review</);
  assert.equal(
    createHash('sha256').update(readFileSync(join(root, 'frictera-theme/templates/page-contact.html'))).digest('hex'),
    '59f77bb293a8f22d49cfdb3ce5942fe12428144f1f7d470c6f0a8407a8ab89be',
  );
});

test('header and home invitations use the same booking page', () => {
  const headerLinks = anchors(header, 'Book a Friction Review');
  assert.equal(headerLinks.length, 2);
  for (const link of headerLinks) {
    assert.match(link, new RegExp(`href="${canonical}"`));
  }

  const homeLinks = anchors(home, 'Book a Friction Review');
  assert.ok(homeLinks.length >= 2);
  for (const link of homeLinks) {
    assert.match(link, new RegExp(`href="${canonical}"`));
  }

  assert.match(home, /<section id="contact" class="section contact-section">/);
  assert.match(home, /<h2>Book a Friction Review<\/h2>/);
  assert.match(
    home,
    /<div class="booking-conversion">\s*<a href="\/contact\/" class="btn btn-primary btn-lg">Book a Friction Review<\/a>\s*<\/div>/,
  );
  assert.equal((home.match(/href="#contact"/g) || []).length, 2);
});
