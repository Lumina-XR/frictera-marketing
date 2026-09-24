import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const css = readFileSync(join(root, 'frictera-theme/style.css'), 'utf8');

const protectedHashes = {
  'frictera-theme/assets/js/theme.js': '1aa9ef6f47a4af5df4e0981c5f1a583183b03f24065fcd40c625d0516f186c9a',
  'frictera-theme/assets/js/intake.js': '95137e351eeb3ceaf6df4fc737158688516b77ddebfd8ad96b5c9cf4ce07caa5',
  'frictera-theme/functions.php': 'd45c9f232db92745a7bfde9af6575a6e054a8bf23d0905a287b194f1f6afdb52',
  'frictera-theme/theme.json': '0aae4015ccd78696f63244f294b5a7e49118fe13eeac43cfebe078c5723c8d1e',
  'frictera-theme/templates/page-home.html': '1f0bcf442c3a8b866513f3e031cce3fb6724d5d7c325126cd2891ca7d0d5c15a',
  'frictera-theme/templates/page-contact.html': '138fc84ef32d9c827e727ef40397accf5ddf81ab64a61746c618495c805ee821',
  'frictera-theme/templates/page-research.html': '11675828f280ad597f51a4686b92fd9ee2be8d0fb0541cd7104e08f3ec2b6840',
  'frictera-theme/templates/page-privacy.html': '1a77a7eac4e7de4391ed23e087b34848177759dd9c33bc2f1302365399e3136f',
  'frictera-theme/templates/page-terms.html': '699103374f52cbab8d8bf3afb49da9226e9d2a06cb0f298186687e88f9a24cc4',
  'frictera-theme/templates/index.html': '86315b7b37db2e72cebc720039d6955a98bc6b183895909ccbd8acfba7b19de5',
  'frictera-theme/parts/header.html': '96f004f14d4dc3160b2837e9ede0507acc2cb81d29eb291066a77ccc0f5aa94c',
  'frictera-theme/parts/footer.html': 'bcd0d275f004858b86692d2b81c0f369b42dc098f86ac793f6394d1047580d44',
  'frictera-theme/README.md': '5c73633a48437c0c96487b7f6d243ab8a8c936c1fd4805326a41a8252d11207b',
  'README.md': '7d57e6555cbc59b864bf6a1d3129f287c5fc348ab7e0762e355c7ae4582b6ef6',
  'frictera-theme/functions.php.before-anon-canary-20260816-105237': 'bc146fc5f59b6c7aece098386989a2572046ec9541afa68bf615842ac1a9831f',
  'frictera-theme/style.css.rollback-before-specificity-repair-02': 'dfe31c12b6a227b9f5eeb818af50b2fac3176c0b85458165f80f783ec555617c',
};

function block(selector) {
  const pattern = new RegExp(`${selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*\\{([^}]*)\\}`);
  const match = css.match(pattern);
  assert.ok(match, `missing ${selector}`);
  return match[1];
}

function declarations(body) {
  const values = {};
  for (const part of body.replace(/\/\*[\s\S]*?\*\//g, '').split(';')) {
    const index = part.indexOf(':');
    if (index === -1) continue;
    values[part.slice(0, index).trim()] = part.slice(index + 1).trim();
  }
  return values;
}

function resolve(value, tokens) {
  let current = value;
  for (let i = 0; i < 4; i += 1) {
    const match = current.match(/^var\((--frictera-[a-z0-9-]+)\)$/);
    if (!match) return current;
    current = tokens[match[1]];
    assert.ok(current, `unresolved ${match[1]}`);
  }
  return current;
}

function luminance(hex) {
  const value = Number.parseInt(hex.slice(1), 16);
  const channels = [(value >> 16) & 255, (value >> 8) & 255, value & 255].map((channel) => {
    const scaled = channel / 255;
    return scaled <= 0.03928 ? scaled / 12.92 : ((scaled + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

function contrast(foreground, background) {
  const lighter = Math.max(luminance(foreground), luminance(background));
  const darker = Math.min(luminance(foreground), luminance(background));
  return (lighter + 0.05) / (darker + 0.05);
}

const light = declarations(block(':root'));
const dark = { ...light, ...declarations(block('html[data-theme="dark"]')) };

test('contact surfaces follow semantic theme tokens', () => {
  const pages = declarations(block('.page-legal,\n.page-research,\n.page-contact'));
  const expectation = declarations(block('.expectation-panel'));
  const form = declarations(block('.page-contact .guided-intake'));
  assert.equal(pages.background, 'var(--frictera-bg)');
  assert.equal(expectation.background, 'var(--frictera-surface-raised)');
  assert.equal(form.background, 'var(--frictera-surface)');
  assert.doesNotMatch(css, /\.page-contact\s*\{[^}]*background:\s*var\(--frictera-cloud\)/);
  assert.doesNotMatch(css, /\.expectation-panel\s*\{[^}]*background:\s*var\(--frictera-white\)/);
});

test('light surfaces stay light and dark surfaces stay dark', () => {
  assert.equal(resolve(light['--frictera-bg'], light), '#F5F7F8');
  assert.equal(resolve(light['--frictera-surface'], light), '#FFFFFF');
  assert.equal(resolve(light['--frictera-surface-raised'], light), '#FFFFFF');
  assert.equal(resolve(light['--frictera-text'], light), '#0B1220');
  assert.equal(resolve(light['--frictera-text-muted'], light), '#52606D');
  assert.equal(resolve(light['--frictera-action'], light), '#087F78');
  assert.equal(dark['--frictera-bg'], '#07111D');
  assert.equal(dark['--frictera-surface'], '#0D1926');
  assert.equal(dark['--frictera-surface-raised'], '#122131');
  assert.equal(dark['--frictera-text'], '#F4F7F8');
  assert.equal(dark['--frictera-text-muted'], '#A9B6C2');
  assert.equal(dark['--frictera-action'], '#0FA89D');
  assert.ok(luminance(dark['--frictera-bg']) < 0.05);
  assert.ok(luminance(dark['--frictera-text']) > 0.8);
});

test('previously failing contact text meets WCAG AA', () => {
  const darkCanvas = dark['--frictera-bg'];
  const darkForm = dark['--frictera-surface'];
  const darkPanel = dark['--frictera-surface-raised'];
  const lightCard = resolve(light['--frictera-surface'], light);
  assert.ok(contrast(dark['--frictera-text'], darkCanvas) >= 4.5);
  assert.ok(contrast(dark['--frictera-text'], darkPanel) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], darkPanel) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], darkForm) >= 4.5);
  assert.ok(contrast(dark['--frictera-action'], darkForm) >= 4.5);
  assert.ok(contrast(dark['--frictera-action'], darkPanel) >= 4.5);
  assert.ok(contrast(resolve(light['--frictera-action'], light), lightCard) >= 4.5);
  assert.ok(contrast(resolve(light['--frictera-text-muted'], light), lightCard) >= 4.5);
  assert.ok(contrast(dark['--frictera-focus'], darkForm) >= 3);
  assert.ok(contrast(resolve(light['--frictera-focus'], light), lightCard) >= 3);
  assert.equal(declarations(block('html[data-theme="dark"] .option-card:hover'))['border-color'], 'var(--frictera-action)');
  assert.equal(declarations(block('html[data-theme="dark"] .option-card.is-selected'))['border-color'], 'var(--frictera-action)');
  assert.equal(declarations(block('.option-card:hover'))['border-color'], 'var(--frictera-teal)');
  assert.match(declarations(block('html[data-theme="dark"] .option-card'))['border-color'], /0\.55/);
});

test('protected theme files and intake transport are unchanged', () => {
  for (const [path, expected] of Object.entries(protectedHashes)) {
    const bytes = readFileSync(join(root, path));
    const actual = createHash('sha256').update(bytes).digest('hex');
    assert.equal(actual, expected, path);
  }
  assert.match(readFileSync(join(root, 'frictera-theme/assets/js/intake.js'), 'utf8'), /fetch\(/);
});

test('sitewide surfaces, evidence, workflow, footer and skip link', () => {
  assert.equal(declarations(block('.workflow-label'))['color'], 'var(--frictera-text)');
  assert.equal(declarations(block('.workflow-step.highlight .workflow-label'))['color'], 'var(--frictera-action)');
  assert.equal(declarations(block('.diagram-node.friction'))['color'], 'var(--frictera-accent-text)');
  assert.equal(declarations(block('.diagram-node.outcome'))['color'], 'var(--frictera-accent-text)');
  assert.equal(declarations(block('.diagram-arrow'))['color'], 'var(--frictera-teal)');
  assert.equal(declarations(block('.evidence-console')).background, 'var(--frictera-surface)');
  assert.equal(declarations(block('.evidence-console')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.evidence-panel')).background, 'var(--frictera-surface-soft)');
  assert.equal(declarations(block('.evidence-panel')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.evidence-label')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.evidence-note')).color, 'var(--frictera-text-muted)');
  assert.equal(declarations(block('.evidence-number')).color, 'var(--frictera-accent-text)');
  assert.equal(declarations(block('.evidence-flag'))['color'], 'var(--frictera-accent-text)');
  assert.equal(declarations(block('.theme-toggle-button'))['color'], 'var(--frictera-text)');
  assert.equal(declarations(block('.site-footer a,\n.site-footer a:visited'))['color'], 'var(--frictera-signal)');
  assert.equal(declarations(block('.footer-col a:visited'))['color'], 'rgba(255, 255, 255, 0.7)');
  const visited = declarations(block('.legal-content a,\n.legal-content a:visited,\n.research-content a,\n.research-content a:visited,\n.contact-content a,\n.contact-content a:visited'));
  assert.equal(visited.color, 'var(--frictera-action)');
  const skip = declarations(block('a.skip-link:focus,\na.skip-link:focus-visible'));
  assert.equal(skip.color, 'var(--frictera-text)');
  assert.equal(skip.background, 'var(--frictera-surface)');
  assert.equal(skip.outline, '2px solid var(--frictera-focus)');
  assert.equal(skip.position, 'fixed');
  assert.equal(declarations(block('.legal-content,\n.research-content')).background, 'var(--frictera-surface)');
  assert.equal(declarations(block('.btn-secondary')).color, 'var(--frictera-action)');
  assert.equal(declarations(block('.contact-form .form-note')).color, 'var(--frictera-text-muted)');
  const colorImportant = css.match(/color:[^;]*!important/g) || [];
  assert.deepEqual(colorImportant, [
    'color: var(--frictera-text-muted) !important',
    'color: #dc2626 !important',
  ]);
});

test('accent, signal, focus and footer contrast meet WCAG AA', () => {
  const ink = '#0B1220';
  const cloud = '#F5F7F8';
  const white = '#FFFFFF';
  const darkForm = dark['--frictera-surface'];
  const darkCanvas = dark['--frictera-bg'];
  assert.equal(light['--frictera-accent-text'], '#066B66');
  assert.equal(dark['--frictera-accent-text'], '#0FA89D');
  assert.equal(light['--frictera-signal'], '#0FA89D');
  assert.ok(contrast(light['--frictera-accent-text'], white) >= 4.5);
  assert.ok(contrast(light['--frictera-accent-text'], cloud) >= 4.5);
  assert.ok(contrast(dark['--frictera-accent-text'], darkForm) >= 4.5);
  assert.ok(contrast(dark['--frictera-accent-text'], darkCanvas) >= 4.5);
  assert.ok(contrast(light['--frictera-signal'], ink) >= 4.5);
  assert.ok(contrast(dark['--frictera-text'], darkCanvas) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], dark['--frictera-surface-raised']) >= 4.5);
  assert.ok(contrast(resolve(light['--frictera-focus'], light), white) >= 3);
  assert.ok(contrast(dark['--frictera-focus'], darkForm) >= 3);
  assert.ok(contrast('#087F78', white) >= 3);
  assert.ok(contrast('#087F78', ink) >= 3);
  assert.ok(contrast(dark['--frictera-action'], darkForm) >= 4.5);
  assert.ok(contrast(dark['--frictera-action'], white) < 4.5);
});

test('borders, placeholders, titles and diagram nodes stay perceivable', () => {
  assert.equal(light['--frictera-border'], '#7E8C98');
  assert.equal(dark['--frictera-border'], '#8AA0AE');
  assert.ok(contrast(light['--frictera-border'], '#FFFFFF') >= 3);
  assert.ok(contrast(light['--frictera-border'], '#F5F7F8') >= 3);
  assert.ok(contrast(dark['--frictera-border'], dark['--frictera-surface']) >= 3);
  assert.ok(contrast(dark['--frictera-border'], dark['--frictera-bg']) >= 3);
  assert.equal(declarations(block('.field input::placeholder,\n.field textarea::placeholder')).color, 'var(--frictera-text-muted)');
  assert.ok(contrast(resolve(light['--frictera-text-muted'], light), '#FFFFFF') >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], dark['--frictera-surface']) >= 4.5);
  assert.equal(declarations(block('.page-title')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.hero h1')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.diagram-node.input')).background, 'var(--frictera-surface)');
  assert.equal(declarations(block('.diagram-node.input')).color, 'var(--frictera-text-muted)');
  assert.equal(declarations(block('html[data-theme="dark"] .field select'))['border-color'], 'var(--frictera-border)');
  assert.equal(declarations(block('html[data-theme="dark"] .field select')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('html[data-theme="dark"] .option-card .card-reveal textarea'))['border-color'], 'var(--frictera-border)');
  const fieldFocus = declarations(block('.field input:focus,\n.field textarea:focus,\n.field select:focus'));
  assert.equal(fieldFocus.outline, '2px solid var(--frictera-focus)');
  assert.equal(fieldFocus['outline-offset'], '2px');
  const footer = '#0B1220';
  assert.ok(contrast('#B6B8BC', footer) >= 4.5);
  assert.ok(contrast('#858990', footer) >= 4.5);
  assert.ok(contrast(light['--frictera-signal'], footer) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], '#122131') >= 4.5);
});

test('contact dark mode canvas and what-happens-next follow the theme', () => {
  const page = declarations(block('html[data-theme="dark"] .page-contact'));
  const panel = declarations(block('html[data-theme="dark"] .expectation-panel'));
  const canvas = dark['--frictera-bg'];
  const card = dark['--frictera-surface-raised'];
  assert.equal(page.background, 'var(--frictera-bg)');
  assert.equal(page.color, 'var(--frictera-text)');
  assert.equal(panel.background, 'var(--frictera-surface-raised)');
  assert.equal(panel.color, 'var(--frictera-text)');
  assert.equal(declarations(block('.expectation-heading')).color, 'var(--frictera-text)');
  assert.notEqual(page.background, 'var(--frictera-cloud)');
  assert.notEqual(panel.background, 'var(--frictera-white)');
  assert.ok(contrast(dark['--frictera-text'], canvas) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], canvas) >= 4.5);
  assert.ok(contrast(dark['--frictera-text'], card) >= 4.5);
  assert.ok(contrast(dark['--frictera-text-muted'], card) >= 4.5);
  assert.ok(contrast(dark['--frictera-action'], card) >= 4.5);
  assert.ok(contrast(dark['--frictera-border'], card) >= 3);
  assert.ok(luminance(canvas) < 0.05);
  assert.ok(luminance(card) < 0.05);
});

test('theme controls and booking CTA stay distinguishable', () => {
  assert.equal(declarations(block('.theme-toggle-button')).color, 'var(--frictera-text)');
  assert.equal(declarations(block('.theme-toggle-button.is-active')).background, 'var(--frictera-white)');
  assert.equal(declarations(block('.theme-toggle-button.is-active')).color, 'var(--frictera-teal)');
  assert.equal(declarations(block('html[data-theme="dark"] .theme-toggle-button.is-active')).background, 'var(--frictera-surface-raised)');
  assert.equal(declarations(block('html[data-theme="dark"] .theme-toggle-button.is-active')).color, 'var(--frictera-evidence)');
  assert.match(css, /@media \(min-width: 768px\) \{\s*\.theme-toggle-label \{\s*display: inline;/);
  const cta = declarations(block('.site-header .header-inner .header-controls .wp-block-buttons > .wp-block-button.nav-cta > .wp-block-button__link'));
  assert.equal(cta.background, 'var(--frictera-teal)');
  assert.equal(cta.color, 'var(--frictera-white)');
  assert.equal(declarations(block('.mobile-nav .nav-cta')).color, 'var(--frictera-white)');
  const ctaHover = declarations(block('.site-header .header-inner .header-controls .wp-block-buttons > .wp-block-button.nav-cta > .wp-block-button__link:hover'));
  const ctaHoverDark = declarations(block('html[data-theme="dark"] .site-header .header-inner .header-controls .wp-block-buttons > .wp-block-button.nav-cta > .wp-block-button__link:hover'));
  assert.equal(ctaHover.background, 'var(--frictera-ink)');
  assert.equal(ctaHover.color, 'var(--frictera-white)');
  assert.equal(ctaHoverDark.background, 'var(--frictera-white)');
  assert.equal(ctaHoverDark.color, 'var(--frictera-ink)');
  assert.ok(contrast('#FFFFFF', '#087F78') >= 4.5);
  assert.ok(contrast('#FFFFFF', '#0B1220') >= 4.5);
  assert.ok(contrast('#0B1220', '#FFFFFF') >= 4.5);
  assert.ok(contrast(resolve(light['--frictera-text'], light), '#FFFFFF') >= 4.5);
  assert.ok(contrast(dark['--frictera-text'], dark['--frictera-surface']) >= 4.5);
  assert.ok(contrast(dark['--frictera-evidence'], dark['--frictera-surface-raised']) >= 3);
  const booking = declarations(block('html[data-theme="dark"] .contact-card'));
  assert.equal(booking.background, 'var(--frictera-surface)');
  assert.equal(booking.color, 'var(--frictera-text)');
  assert.equal(booking['border-color'], 'var(--frictera-border)');
  assert.ok(contrast(dark['--frictera-text'], dark['--frictera-surface']) >= 4.5);
  assert.ok(contrast(dark['--frictera-border'], dark['--frictera-surface']) >= 3);
});
