<?php
/**
 * Frictera Theme Functions
 *
 * @package Frictera
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme bootstrap script: apply stored/system theme before paint to prevent FOUC.
 */
function frictera_theme_bootstrap() {
    $script = <<<'JS'
(function () {
  try {
    var key = 'frictera-marketing-theme';
    var stored = window.localStorage.getItem(key);
    var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var theme = stored || (prefersDark ? 'dark' : 'light');
    if (theme === 'system') theme = prefersDark ? 'dark' : 'light';
    document.documentElement.dataset.theme = theme;
  } catch (_) {}
})();
JS;
    wp_register_script('frictera-theme-bootstrap', false, array(), null, false);
    wp_enqueue_script('frictera-theme-bootstrap');
    wp_add_inline_script('frictera-theme-bootstrap', $script);
}
add_action('wp_enqueue_scripts', 'frictera_theme_bootstrap', 0);

/**
 * Enqueue theme styles
 */
function frictera_enqueue_styles() {
    wp_enqueue_style(
        'frictera-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'frictera_enqueue_styles');

/**
 * Enqueue theme controller for interactive components (theme toggle, mobile nav).
 */
function frictera_enqueue_scripts() {
    wp_enqueue_script(
        'frictera-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'frictera_enqueue_scripts');

/**
 * Enqueue guided intake script for Contact and Research pages.
 */
function frictera_enqueue_intake_scripts() {
    if (!is_page('contact') && !is_page('research')) {
        return;
    }
    wp_enqueue_script(
        'frictera-intake',
        get_template_directory_uri() . '/assets/js/intake.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'frictera_enqueue_intake_scripts');

/**
 * Register navigation menus
 */
function frictera_register_menus() {
    register_nav_menus(array(
        'primary' => __('Primary Navigation', 'frictera'),
        'footer' => __('Footer Navigation', 'frictera'),
    ));
}
add_action('after_setup_theme', 'frictera_register_menus');

/**
 * Add theme support
 */
function frictera_theme_support() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
}
add_action('after_setup_theme', 'frictera_theme_support');

/**
 * Register block patterns category
 */
function frictera_register_pattern_categories() {
    register_block_pattern_category('frictera', array(
        'label' => __('Frictera', 'frictera')
    ));
}
add_action('init', 'frictera_register_pattern_categories');

/**
 * Custom excerpt length
 */
function frictera_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'frictera_excerpt_length');

/**
 * Custom excerpt more
 */
function frictera_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'frictera_excerpt_more');

/* ============================================================
 * Frictera Public Inbound Gateway — WordPress Handoff (WIG01)
 * Server-side signing, idempotency, and safe visitor feedback.
 * ============================================================ */

if (!defined('FRICTERA_INTAKE_ENABLED')) {
    define('FRICTERA_INTAKE_ENABLED', false);
}

if (!defined('FRICTERA_GATEWAY_BASE_URL')) {
    define('FRICTERA_GATEWAY_BASE_URL', '');
}

if (!defined('FRICTERA_GATEWAY_KEY_ID')) {
    define('FRICTERA_GATEWAY_KEY_ID', '');
}

if (!defined('FRICTERA_GATEWAY_SECRET')) {
    define('FRICTERA_GATEWAY_SECRET', '');
}

if (!defined('FRICTERA_GATEWAY_TIMEOUT')) {
    define('FRICTERA_GATEWAY_TIMEOUT', 15);
}

if (!defined('FRICTERA_GATEWAY_RETRIES')) {
    define('FRICTERA_GATEWAY_RETRIES', 2);
}

/* ============================================================
 * Frictera Call Scheduling — Cal.com Integration (FRICTERA_CALL_SCHEDULING_INTEGRATION_01)
 * ============================================================ */

if (!defined('FRICTERA_SCHEDULING_ENABLED')) {
    define('FRICTERA_SCHEDULING_ENABLED', false);
}

if (!defined('FRICTERA_SCHEDULING_PROVIDER')) {
    define('FRICTERA_SCHEDULING_PROVIDER', 'calcom');
}

if (!defined('FRICTERA_CALCOM_API_BASE')) {
    define('FRICTERA_CALCOM_API_BASE', 'https://api.cal.com');
}

if (!defined('FRICTERA_CALCOM_API_KEY')) {
    define('FRICTERA_CALCOM_API_KEY', '');
}

if (!defined('FRICTERA_CALCOM_EVENT_TYPE_SLUG')) {
    define('FRICTERA_CALCOM_EVENT_TYPE_SLUG', 'friction-review');
}

if (!defined('FRICTERA_CALCOM_EVENT_TYPE_ID')) {
    define('FRICTERA_CALCOM_EVENT_TYPE_ID', 0);
}

if (!defined('FRICTERA_CALCOM_USERNAME')) {
    define('FRICTERA_CALCOM_USERNAME', '');
}

if (!defined('FRICTERA_CALCOM_WEBHOOK_SECRET')) {
    define('FRICTERA_CALCOM_WEBHOOK_SECRET', '');
}

if (!defined('FRICTERA_SCHEDULING_TOKEN_SECRET')) {
    define('FRICTERA_SCHEDULING_TOKEN_SECRET', '');
}

if (!defined('FRICTERA_SCHEDULING_TOKEN_TTL_SECONDS')) {
    define('FRICTERA_SCHEDULING_TOKEN_TTL_SECONDS', 7 * DAY_IN_SECONDS);
}

if (!defined('FRICTERA_SCHEDULING_FROM_EMAIL')) {
    define('FRICTERA_SCHEDULING_FROM_EMAIL', get_option('admin_email'));
}

/**
 * Safely retrieve a server-side config value.
 */
function frictera_get_config($constant, $fallback = '') {
    return defined($constant) ? constant($constant) : $fallback;
}

/**
 * Check whether live intake submission is enabled and properly configured.
 */
function frictera_intake_is_live() {
    if (!FRICTERA_INTAKE_ENABLED) {
        return false;
    }
    if (empty(FRICTERA_GATEWAY_BASE_URL)) {
        return false;
    }
    if (empty(FRICTERA_GATEWAY_KEY_ID) || empty(FRICTERA_GATEWAY_SECRET)) {
        return false;
    }
    return true;
}

/**
 * Build the canonical string for the HMAC signature.
 */
function frictera_build_canonical_string($method, $path, $timestamp, $nonce, $body) {
    $body_hash = hash('sha256', $body);
    return strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $nonce . "\n" . $body_hash;
}

/**
 * Sign a gateway request using the proven PIGH01 HMAC contract.
 */
function frictera_sign_gateway_request($body) {
    $timestamp = (string) round(microtime(true) * 1000);
    $nonce = wp_generate_uuid4();
    $path = '/public/intake';
    $canonical = frictera_build_canonical_string('POST', $path, $timestamp, $nonce, $body);
    $signature = hash_hmac('sha256', $canonical, FRICTERA_GATEWAY_SECRET);
    return array(
        'timestamp'   => $timestamp,
        'nonce'       => $nonce,
        'signature'   => $signature,
        'canonical'   => $canonical,
    );
}

/**
 * Generate a bounded Idempotency-Key for a submission attempt.
 */
function frictera_generate_idempotency_key($source) {
    $session_token = wp_get_session_token();
    if (empty($session_token)) {
        $session_token = wp_generate_password(32, false);
    }
    $entropy = wp_rand(0, PHP_INT_MAX) . wp_generate_password(16, false);
    return hash('sha256', $source . '|' . $session_token . '|' . microtime(true) . '|' . $entropy);
}

/**
 * Build a standard signal record.
 */
function frictera_signal_record($signal_type, $signal_code, $other_text = '') {
    $record = array(
        'signalType' => $signal_type,
        'signalCode' => $signal_code,
        'source'     => 'user-selection',
        'confidence' => 'explicit',
    );
    if ($signal_code === 'other' && $other_text !== '') {
        $record['otherText'] = sanitize_text_field($other_text);
    }
    return $record;
}

/**
 * Map UI checkbox values to canonical codes using a lookup table.
 */
function frictera_map_checkbox_values($values, $map, $signal_type, $other_value = '') {
    $records = array();
    if (!is_array($values)) {
        $values = array();
    }
    foreach ($values as $value) {
        $value = sanitize_text_field($value);
        if ($value === '') {
            continue;
        }
        $code = isset($map[$value]) ? $map[$value] : '';
        if ($code === '') {
            continue;
        }
        $records[] = frictera_signal_record($signal_type, $code, $other_value);
    }
    return $records;
}

/**
 * Map team language UI value to canonical language codes.
 */
function frictera_map_team_languages($value) {
    $map = array(
        'english'        => array('en'),
        'arabic'         => array('ar'),
        'english-arabic' => array('en', 'ar'),
        'other'          => array('other'),
    );
    return isset($map[$value]) ? $map[$value] : array('other');
}

/**
 * Map client language UI values to canonical language codes.
 */
function frictera_map_client_languages($values, $other_value = '') {
    $map = array(
        'english'        => 'en',
        'arabic'         => 'ar',
        'russian'        => 'ru',
        'chinese'        => 'zh',
        'hindi'          => 'hi',
        'urdu'           => 'ur',
        'french'         => 'fr',
        'other'          => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'language', $other_value);
}

/**
 * Map business type UI value to canonical code.
 */
function frictera_map_business_type($value) {
    $map = array(
        'brokerage'                => 'brokerage',
        'property-management'      => 'property-management',
        'leasing'                  => 'leasing',
        'developer-sales'          => 'developer-sales',
        'holiday-homes'            => 'holiday-homes',
        'mixed'                    => 'mixed-property-operations',
        'other'                    => 'other',
    );
    return isset($map[$value]) ? $map[$value] : 'other';
}

/**
 * Map research role UI value to business type and optional other text.
 */
function frictera_map_research_role($value) {
    $map = array(
        'brokerage-leadership'    => array('code' => 'other', 'other' => 'Brokerage leadership'),
        'brokerage-operations'    => array('code' => 'other', 'other' => 'Brokerage operations'),
        'property-management'     => array('code' => 'property-management', 'other' => ''),
        'leasing'                 => array('code' => 'leasing', 'other' => ''),
        'developer-sales'         => array('code' => 'other', 'other' => 'Developer sales operations'),
        'other'                   => array('code' => 'other', 'other' => 'Other property operations'),
    );
    return isset($map[$value]) ? $map[$value] : array('code' => 'other', 'other' => '');
}

/**
 * Map urgency UI value to canonical code.
 */
function frictera_map_urgency($value) {
    $map = array(
        'exploring'              => 'exploring',
        'important-this-quarter' => 'this-quarter',
        'needs-attention-now'    => 'needs-attention-now',
        'actively-looking'       => 'actively-evaluating',
    );
    return isset($map[$value]) ? $map[$value] : 'exploring';
}

/**
 * Map engagement intent UI values to canonical codes.
 */
function frictera_map_engagement_intents($values) {
    $map = array(
        'friction-review'        => 'friction-review',
        'operational-assessment' => 'operational-assessment',
        'research-conversation'  => 'research-conversation',
        'product-demo'           => 'product-demonstration',
        'pilot-discussion'       => 'pilot-discussion',
        'not-sure'               => 'unsure',
    );
    $result = array();
    if (!is_array($values)) {
        $values = array();
    }
    foreach ($values as $value) {
        $value = sanitize_text_field($value);
        if (isset($map[$value])) {
            $result[] = $map[$value];
        }
    }
    return $result;
}

/**
 * Map friction UI values to canonical codes.
 */
function frictera_map_frictions($values, $other_value = '') {
    $map = array(
        'slow-response'         => 'slow-enquiry-response',
        'routing'               => 'lead-routing-delay',
        'missing-information'   => 'missing-information',
        'missed-followups'      => 'missed-follow-up',
        'visibility'            => 'attention-visibility',
        'manual-handoff'        => 'manual-channel-handoff',
        'pm-coordination'       => 'pm-coordination-delay',
        'landlord-approvals'    => 'landlord-approval-delay',
        'maintenance-tracking'  => 'maintenance-tracking',
        'management-visibility' => 'management-visibility',
        'identify-automation'   => 'automation-discovery',
        'other'                 => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'friction', $other_value);
}

/**
 * Map desired outcome UI values to canonical codes.
 */
function frictera_map_desired_outcomes($values, $other_value = '') {
    $map = array(
        'faster-first-response'   => 'faster-first-response',
        'fewer-missed'            => 'fewer-missed-enquiries',
        'better-routing'          => 'better-lead-routing',
        'better-followup'         => 'better-follow-up',
        'less-admin'              => 'less-manual-administration',
        'faster-incomplete'       => 'faster-incomplete-enquiry-resolution',
        'faster-pm-resolution'    => 'faster-pm-resolution',
        'better-visibility'       => 'better-management-visibility',
        'better-multilingual'     => 'better-multilingual-handling',
        'evidence-savings'        => 'evidence-of-time-cost-savings',
        'not-sure'                => 'needs-help-identifying-outcome',
        'other'                   => 'needs-help-identifying-outcome',
    );
    return frictera_map_checkbox_values($values, $map, 'outcome', $other_value);
}

/**
 * Map language friction UI values to canonical codes.
 */
function frictera_map_language_frictions($values, $other_value = '') {
    $map = array(
        'initial-understanding' => 'initial-understanding',
        'translating-messages'  => 'translating-messages',
        'assigning-staff'       => 'assignment',
        'responding'            => 'responding',
        'tone-accuracy'         => 'tone-accuracy',
        'following-up'          => 'following-up',
        'pm-communication'      => 'pm-communication',
        'none'                  => 'none',
        'other'                 => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'language-friction', $other_value);
}

/**
 * Map translation process UI values to canonical codes.
 */
function frictera_map_translation_processes($values, $other_value = '') {
    $map = array(
        'staff-manual'        => 'staff-manual',
        'multilingual-staff'  => 'multilingual-staff',
        'crm-translation'     => 'crm-translation',
        'ai-translation'      => 'ai-translation',
        'external-translator' => 'external-translator',
        'language-team'       => 'language-team',
        'no-formal-process'   => 'no-formal-process',
        'other'               => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'translation-process', $other_value);
}

/**
 * Map client market UI values to canonical codes.
 */
function frictera_map_client_markets($values, $other_value = '') {
    $map = array(
        'uae-residents'         => 'uae',
        'gcc'                   => 'gcc',
        'europe-uk'             => 'europe-uk',
        'south-asia'            => 'south-asia',
        'east-asia'             => 'east-asia',
        'russia-cis'            => 'russia-cis',
        'north-america'         => 'north-america',
        'africa'                => 'africa',
        'mixed'                 => 'mixed-international',
        'other'                 => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'market', $other_value);
}

/**
 * Map international friction UI values to canonical codes.
 */
function frictera_map_international_frictions($values, $other_value = '') {
    $map = array(
        'time-zones'            => 'timezone',
        'language'              => 'language',
        'out-of-hours-response' => 'out-of-hours-response',
        'incomplete-buyer-info' => 'incomplete-buyer-information',
        'multiple-channels'     => 'multi-channel',
        'reassigning'           => 'reassignment',
        'remote-viewing'        => 'remote-viewing-coordination',
        'follow-up-timezones'   => 'timezone-follow-up',
        'buying-stage'          => 'buyer-stage-qualification',
        'documentation'         => 'documentation-coordination',
        'developer-info'        => 'developer-information',
        'none'                  => 'none',
        'other'                 => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'international-friction', $other_value);
}

/**
 * Map out-of-hours process UI value to canonical code.
 */
function frictera_map_out_of_hours_process($value) {
    $map = array(
        'wait-next-day'       => 'next-business-day',
        'on-call-broker'      => 'on-call-broker',
        'auto-distributed'    => 'automatic-distribution',
        'timezone-teams'      => 'timezone-team-coverage',
        'outsourced'          => 'outsourced',
        'varies'              => 'varies',
        'not-sure'            => 'unknown',
    );
    return isset($map[$value]) ? $map[$value] : '';
}

/**
 * Map systems UI values to canonical codes.
 */
function frictera_map_systems($values, $other_value = '') {
    $map = array(
        'crm'                 => 'crm',
        'property-portals'    => 'property-portal',
        'website-forms'       => 'website-form',
        'email'               => 'email',
        'whatsapp'            => 'whatsapp',
        'phone'               => 'phone',
        'spreadsheet'         => 'spreadsheet',
        'pm-software'         => 'property-management-software',
        'developer-portal'    => 'developer-portal',
        'other'               => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'system', $other_value);
}

/**
 * Map routing method UI values to canonical codes.
 */
function frictera_map_routing_methods($values, $other_value = '') {
    $map = array(
        'round-robin'            => 'round-robin',
        'by-language'            => 'language',
        'by-property'            => 'property-project',
        'by-market'              => 'buyer-market',
        'by-expertise'           => 'broker-expertise',
        'by-availability'        => 'availability',
        'manual-manager'         => 'manual-manager',
        'crm-automation'         => 'crm-automation',
        'no-consistent-process'  => 'no-consistent-process',
        'other'                  => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'routing', $other_value);
}

/**
 * Map handoff friction UI values to canonical codes.
 */
function frictera_map_handoff_frictions($values, $other_value = '') {
    $map = array(
        'portal-crm'          => 'portal-to-crm',
        'email-crm'           => 'email-to-crm',
        'whatsapp-crm'        => 'whatsapp-to-crm',
        'broker-admin'        => 'broker-to-admin',
        'admin-manager'       => 'admin-to-manager',
        'landlord-pm'         => 'landlord-to-property-manager',
        'contractor-pm'       => 'contractor-to-property-manager',
        'between-shifts'      => 'between-teams',
        'follow-up'           => 'follow-up',
        'not-sure'            => 'unknown',
        'other'               => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'handoff-friction', $other_value);
}

/**
 * Map research topics to canonical signal records.
 */
function frictera_map_research_topics($values, $other_value = '') {
    $map = array(
        'enquiry-intake'      => 'enquiry-intake',
        'lead-routing'        => 'lead-routing',
        'missing-information' => 'missing-information',
        'follow-up'           => 'follow-up',
        'crm-gaps'            => 'crm-gaps',
        'multilingual'        => 'multilingual',
        'pm-exceptions'       => 'pm-exceptions',
        'maintenance'         => 'maintenance',
        'landlord-approvals'  => 'landlord-approvals',
        'visibility'          => 'visibility',
        'other'               => 'other',
    );
    return frictera_map_checkbox_values($values, $map, 'research-topic', $other_value);
}

/**
 * Build the schemaVersion 1 payload from a contact form submission.
 */
function frictera_build_contact_payload($data) {
    $business_type = frictera_map_business_type(sanitize_text_field($data['business_type'] ?? ''));
    $business_type_other = sanitize_text_field($data['business_type_other'] ?? '');

    $payload = array(
        'schemaVersion'       => 1,
        'source'              => 'website-contact',
        'vertical'            => 'property',
        'businessType'        => $business_type,
        'urgency'             => frictera_map_urgency(sanitize_text_field($data['urgency'] ?? '')),
        'additionalContext'   => sanitize_textarea_field($data['additional_context'] ?? ''),
        'privacyAcknowledged' => true,
        'contact'             => array(
            'name'      => sanitize_text_field($data['name'] ?? ''),
            'workEmail' => sanitize_email($data['email'] ?? ''),
            'company'   => sanitize_text_field($data['company'] ?? ''),
            'role'      => sanitize_text_field($data['role'] ?? ''),
            'teamSize'  => sanitize_text_field($data['team-size'] ?? ''),
        ),
        'signals'             => array(
            'frictions'            => frictera_map_frictions($data['frictions'] ?? array(), $data['frictions_other'] ?? ''),
            'desiredOutcomes'      => frictera_map_desired_outcomes($data['desired_outcomes'] ?? array(), $data['desired_outcomes_other'] ?? ''),
            'teamLanguages'        => frictera_map_team_languages(sanitize_text_field($data['team_language'] ?? '')),
            'clientLanguages'      => frictera_map_client_languages($data['client_languages'] ?? array(), $data['client_languages_other'] ?? ''),
            'languageFrictions'    => frictera_map_language_frictions($data['language_frictions'] ?? array(), $data['language_frictions_other'] ?? ''),
            'translationProcesses' => frictera_map_translation_processes($data['translation_process'] ?? array(), $data['translation_process_other'] ?? ''),
            'clientMarkets'        => frictera_map_client_markets($data['client_markets'] ?? array(), $data['client_markets_other'] ?? ''),
            'internationalFrictions' => frictera_map_international_frictions($data['international_frictions'] ?? array(), $data['international_frictions_other'] ?? ''),
            'outOfHoursProcess'    => frictera_map_out_of_hours_process(sanitize_text_field($data['out_of_hours_process'] ?? '')),
            'routingMethods'       => frictera_map_routing_methods($data['routing_method'] ?? array(), $data['routing_method_other'] ?? ''),
            'systems'              => frictera_map_systems($data['systems'] ?? array(), $data['systems_other'] ?? ''),
            'handoffFrictions'     => frictera_map_handoff_frictions($data['handoff_frictions'] ?? array(), $data['handoff_frictions_other'] ?? ''),
        ),
        'intent'              => array(
            'engagementIntents'   => frictera_map_engagement_intents($data['engagement_intent'] ?? array()),
            'pilotInterest'       => (sanitize_text_field($data['pilot_interest'] ?? '') === 'yes'),
            'pilotContactOptIn'   => (sanitize_text_field($data['pilot_contact_opt_in'] ?? '') === 'yes'),
            'callRequested'       => false,
            'bookingId'           => null,
            'bookingStatus'       => null,
        ),
    );

    if ($business_type === 'other' && $business_type_other !== '') {
        $payload['businessTypeOther'] = $business_type_other;
    }

    return $payload;
}

/**
 * Build the schemaVersion 1 payload from a research form submission.
 */
function frictera_build_research_payload($data) {
    $role_mapping = frictera_map_research_role(sanitize_text_field($data['research_role'] ?? ''));
    $call_requested = (sanitize_text_field($data['research_next_step'] ?? '') === 'schedule-call');

    $payload = array(
        'schemaVersion'       => 1,
        'source'              => 'website-research',
        'vertical'            => 'property',
        'businessType'        => $role_mapping['code'],
        'urgency'             => 'exploring',
        'additionalContext'   => sanitize_textarea_field($data['additional_context'] ?? ''),
        'privacyAcknowledged' => true,
        'contact'             => array(
            'name'      => sanitize_text_field($data['name'] ?? ''),
            'workEmail' => sanitize_email($data['email'] ?? ''),
            'company'   => sanitize_text_field($data['company'] ?? ''),
            'role'      => sanitize_text_field($data['role'] ?? ''),
        ),
        'signals'             => array(
            'frictions' => array(),
            'desiredOutcomes' => array(),
            'teamLanguages' => array(),
            'clientLanguages' => array(),
            'languageFrictions' => array(),
            'translationProcesses' => array(),
            'clientMarkets' => array(),
            'internationalFrictions' => array(),
            'routingMethods' => array(),
            'systems' => array(),
            'handoffFrictions' => array(),
            'researchTopics' => frictera_map_research_topics($data['research_topics'] ?? array(), $data['research_topics_other'] ?? ''),
        ),
        'intent'              => array(
            'engagementIntents'   => array('research-conversation'),
            'pilotInterest'       => false,
            'pilotContactOptIn'   => false,
            'callRequested'       => $call_requested,
            'bookingId'           => null,
            'bookingStatus'       => null,
        ),
    );

    if ($role_mapping['code'] === 'other' && $role_mapping['other'] !== '') {
        $payload['businessTypeOther'] = $role_mapping['other'];
    }

    return $payload;
}

/**
 * Forward a submission to the hardened Frictera gateway.
 */
function frictera_forward_to_gateway($payload, $idempotency_key) {
    $url = rtrim(FRICTERA_GATEWAY_BASE_URL, '/') . '/public/intake';
    $body = wp_json_encode($payload);
    $auth = frictera_sign_gateway_request($body);

    $headers = array(
        'Content-Type'        => 'application/json',
        'Idempotency-Key'     => $idempotency_key,
        'X-Frictera-Key-Id'   => FRICTERA_GATEWAY_KEY_ID,
        'X-Frictera-Timestamp'=> $auth['timestamp'],
        'X-Frictera-Nonce'    => $auth['nonce'],
        'X-Frictera-Signature'=> $auth['signature'],
        'X-Correlation-Id'    => wp_generate_uuid4(),
    );

    $attempt = 0;
    $max_attempts = max(1, intval(FRICTERA_GATEWAY_RETRIES));
    $last_error = null;

    while ($attempt < $max_attempts) {
        $attempt++;
        // Refresh auth for every attempt; idempotency key stays the same.
        if ($attempt > 1) {
            $auth = frictera_sign_gateway_request($body);
            $headers['X-Frictera-Timestamp'] = $auth['timestamp'];
            $headers['X-Frictera-Nonce']     = $auth['nonce'];
            $headers['X-Frictera-Signature'] = $auth['signature'];
        }

        $response = wp_remote_post($url, array(
            'body'        => $body,
            'headers'     => $headers,
            'timeout'     => intval(FRICTERA_GATEWAY_TIMEOUT),
            'data_format' => 'body',
        ));

        if (is_wp_error($response)) {
            $last_error = $response;
            $error_code = $response->get_error_code();
            // Retry on transient network errors and timeouts only.
            if (in_array($error_code, array('http_request_failed', 'curl_error', 'fsocket_error'), true) && $attempt < $max_attempts) {
                continue;
            }
            break;
        }

        $status = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Retry only on 5xx or 429; do not retry auth/validation errors.
        if (($status >= 500 || $status === 429) && $attempt < $max_attempts) {
            $last_error = new WP_Error('gateway_retry', 'Transient gateway status ' . $status);
            continue;
        }

        return array(
            'status' => $status,
            'body'   => $response_body,
            'headers'=> $headers,
        );
    }

    return array(
        'status'    => 0,
        'body'      => '',
        'wp_error'  => $last_error,
        'headers'   => array(),
    );
}

/**
 * Safely log intake outcome without PII or secrets.
 */
function frictera_log_intake_outcome($source, $status, $category, $correlation_id = '', $submission_id = '') {
    if (!function_exists('error_log')) {
        return;
    }
    $message = sprintf(
        '[Frictera Intake] source=%s status=%s category=%s correlation=%s submission=%s',
        sanitize_text_field($source),
        sanitize_text_field($status),
        sanitize_text_field($category),
        sanitize_text_field($correlation_id),
        sanitize_text_field($submission_id)
    );
    error_log($message);
}

/**
 * Process an intake submission and return a safe REST response.
 */
function frictera_process_intake_submission($source) {
    // Feature gate: live submissions require explicit activation and a configured HTTPS gateway.
    if (!frictera_intake_is_live()) {
        frictera_log_intake_outcome($source, '503', 'not_configured');
        return new WP_Error(
            'not_configured',
            __('Intake submission is not yet enabled.', 'frictera'),
            array('status' => 503)
        );
    }

    // CSRF nonce check (distinct from gateway auth nonce and idempotency key).
    $nonce = sanitize_text_field($_POST['_wpnonce'] ?? '');
    if (empty($nonce) || !wp_verify_nonce($nonce, 'frictera_intake_action')) {
        frictera_log_intake_outcome($source, '403', 'csrf_failed');
        return new WP_Error(
            'csrf_failed',
            __('Unable to verify your submission. Please refresh the page and try again.', 'frictera'),
            array('status' => 403)
        );
    }

    // Honeypot: server-side bot check. Must be present and empty.
    if (!isset($_POST['frictera_website']) || sanitize_text_field($_POST['frictera_website']) !== '') {
        frictera_log_intake_outcome($source, '403', 'honeypot_failed');
        return new WP_Error(
            'honeypot_failed',
            __('Unable to submit this form.', 'frictera'),
            array('status' => 403)
        );
    }

    $data = wp_unslash($_POST);
    $payload = ($source === 'website-research')
        ? frictera_build_research_payload($data)
        : frictera_build_contact_payload($data);

    $idempotency_key = frictera_generate_idempotency_key($source);
    $result = frictera_forward_to_gateway($payload, $idempotency_key);

    if (isset($result['wp_error']) && is_wp_error($result['wp_error'])) {
        frictera_log_intake_outcome($source, '0', 'gateway_unreachable', $result['headers']['X-Correlation-Id'] ?? '');
        return new WP_Error(
            'gateway_unreachable',
            __('We couldn\'t submit this right now. Please try again shortly.', 'frictera'),
            array('status' => 503)
        );
    }

    $status = intval($result['status']);
    $response_data = json_decode($result['body'], true);
    $correlation_id = $result['headers']['X-Correlation-Id'] ?? '';
    $submission_id = isset($response_data['submissionId']) ? sanitize_text_field($response_data['submissionId']) : '';

    if ($status === 201) {
        frictera_log_intake_outcome($source, '201', 'accepted', $correlation_id, $submission_id);
        return array(
            'accepted'     => true,
            'submissionId' => $submission_id,
            'message'      => frictera_success_message($source, $payload),
        );
    }

    if ($status === 429) {
        frictera_log_intake_outcome($source, '429', 'rate_limited', $correlation_id);
        return new WP_Error(
            'rate_limited',
            __('We are receiving a high volume of submissions. Please try again in a few minutes.', 'frictera'),
            array('status' => 429)
        );
    }

    if ($status === 409) {
        frictera_log_intake_outcome($source, '409', 'duplicate', $correlation_id);
        return new WP_Error(
            'duplicate',
            __('This submission has already been received.', 'frictera'),
            array('status' => 409)
        );
    }

    if ($status === 400 && isset($response_data['errors']) && is_array($response_data['errors'])) {
        frictera_log_intake_outcome($source, '400', 'validation_failed', $correlation_id);
        return new WP_Error(
            'validation_failed',
            __('Please review the form and try again.', 'frictera'),
            array(
                'status' => 400,
                'errors' => frictera_sanitize_validation_errors($response_data['errors']),
            )
        );
    }

    // All other failures: safe generic message. Never expose auth/database details.
    frictera_log_intake_outcome($source, strval($status), 'gateway_failed', $correlation_id);
    return new WP_Error(
        'gateway_failed',
        __('We couldn\'t submit this right now. Please try again shortly.', 'frictera'),
        array('status' => 502)
    );
}

/**
 * Build a calm success message appropriate to the submission.
 */
function frictera_success_message($source, $payload) {
    $base = ($source === 'website-research')
        ? __('Thank you. Your research response has been received.', 'frictera')
        : __('Thank you. Your Friction Review information has been received.', 'frictera');

    $notes = array();
    if (!empty($payload['intent']['callRequested'])) {
        $notes[] = __('Your request to arrange a conversation has been recorded.', 'frictera');
    }
    if (!empty($payload['intent']['pilotInterest'])) {
        $notes[] = __('Your interest in discussing a bounded Frictera pilot has been recorded.', 'frictera');
    }

    if (empty($notes)) {
        return $base;
    }
    return $base . ' ' . implode(' ', $notes);
}

/**
 * Sanitize gateway validation errors before returning to the browser.
 */
function frictera_sanitize_validation_errors($errors) {
    $safe = array();
    if (!is_array($errors)) {
        return $safe;
    }
    $allowed_codes = array('REQUIRED', 'INVALID_VALUE', 'TOO_MANY_VALUES', 'INVALID_EMAIL', 'TOO_LONG', 'UNKNOWN_FIELD', 'INVALID_TYPE', 'DUPLICATE_SUBMISSION');
    foreach ($errors as $error) {
        $code = isset($error['code']) ? sanitize_text_field($error['code']) : 'INVALID_VALUE';
        if (!in_array($code, $allowed_codes, true)) {
            $code = 'INVALID_VALUE';
        }
        $safe[] = array(
            'field'   => isset($error['field']) ? sanitize_text_field($error['field']) : '',
            'code'    => $code,
            'message' => isset($error['message']) ? sanitize_text_field($error['message']) : '',
        );
    }
    return $safe;
}

/**
 * Register REST API endpoints for intake submissions.
 */
function frictera_register_intake_routes() {
    register_rest_route('frictera/v1', '/intake/contact', array(
        'methods'             => 'POST',
        'callback'            => 'frictera_rest_contact_intake',
        'permission_callback' => 'frictera_intake_permission_check',
    ));

    register_rest_route('frictera/v1', '/intake/research', array(
        'methods'             => 'POST',
        'callback'            => 'frictera_rest_research_intake',
        'permission_callback' => 'frictera_intake_permission_check',
    ));

    register_rest_route('frictera/v1', '/friction-review', array(
        'methods'             => 'POST,OPTIONS',
        'callback'            => 'frictera_rest_friction_review',
        'permission_callback' => 'frictera_intake_permission_check',
    ));

    register_rest_route('frictera/v1', '/scheduling/slots', array(
        'methods'             => 'POST',
        'callback'            => 'frictera_rest_scheduling_slots',
        'permission_callback' => 'frictera_intake_permission_check',
    ));

    register_rest_route('frictera/v1', '/scheduling/book', array(
        'methods'             => 'POST',
        'callback'            => 'frictera_rest_scheduling_book',
        'permission_callback' => 'frictera_intake_permission_check',
    ));

    register_rest_route('frictera/v1', '/scheduling/webhook', array(
        'methods'             => 'POST',
        'callback'            => 'frictera_rest_scheduling_webhook',
        'permission_callback' => 'frictera_intake_permission_check',
    ));
}
add_action('rest_api_init', 'frictera_register_intake_routes');

/**
 * REST permission callback: allow anonymous access.
 *
 * The public intake endpoints are protected by:
 *   - feature gate (FRICTERA_INTAKE_ENABLED)
 *   - server-side nonce verification inside frictera_process_intake_submission()
 *   - honeypot field
 *   - payload validation
 *   - gateway HMAC authentication
 *   - gateway rate limiting
 *
 * WordPress REST cookie authentication is intentionally not required because
 * these endpoints are public anonymous forms. The dedicated nonce is verified
 * by the handler, not by the REST permission layer, to avoid the logged-in
 * nonce contract that is incompatible with cached anonymous pages.
 */
function frictera_intake_permission_check() {
    return true;
}

/**
 * Contact intake REST callback.
 */
function frictera_rest_contact_intake() {
    $result = frictera_process_intake_submission('website-contact');
    return frictera_build_contact_response($result);
}

/**
 * Research intake REST callback.
 */
function frictera_rest_research_intake() {
    $result = frictera_process_intake_submission('website-research');
    return frictera_format_intake_response($result);
}

/**
 * Forward a Friction Review submission to the Frictera gateway.
 *
 * The public gateway endpoint is authenticated by rate limiting and the
 * browser-supplied idempotency key only; it does not use the intake HMAC
 * contract. The caller's idempotency key is preserved verbatim.
 */
function frictera_forward_friction_review_to_gateway($body, $idempotency_key) {
    $url = rtrim(FRICTERA_GATEWAY_BASE_URL, '/') . '/public/friction-review';

    $headers = array(
        'Content-Type'    => 'application/json',
        'Idempotency-Key' => $idempotency_key,
        'X-Correlation-Id'=> wp_generate_uuid4(),
    );

    $response = wp_remote_post($url, array(
        'body'        => $body,
        'headers'     => $headers,
        'timeout'     => intval(FRICTERA_GATEWAY_TIMEOUT),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        return array(
            'status'   => 0,
            'body'     => '',
            'wp_error' => $response,
        );
    }

    return array(
        'status'  => wp_remote_retrieve_response_code($response),
        'body'    => wp_remote_retrieve_body($response),
        'headers' => $headers,
    );
}

/**
 * Friction Review REST callback.
 *
 * Proxies the anonymous browser submission to the gateway Friction Review
 * endpoint. The gateway owns validation, rate limiting, idempotency, and
 * persistence. No server-side WordPress nonce is required because the route
 * is a transparent pass-through and the gateway enforces its own abuse limits.
 */
function frictera_rest_friction_review($request) {
    if (empty(FRICTERA_GATEWAY_BASE_URL)) {
        frictera_log_intake_outcome('friction-review', '503', 'not_configured');
        return new WP_Error(
            'not_configured',
            __('Submission is not yet enabled.', 'frictera'),
            array('status' => 503)
        );
    }

    $method = strtoupper($request->get_method());
    if ($method === 'OPTIONS') {
        $response = new WP_REST_Response(null, 204);
        $response->header('Allow', 'POST, OPTIONS');
        return $response;
    }

    $idempotency_key = sanitize_text_field($request->get_header('idempotency-key') ?? '');
    if ($idempotency_key === '') {
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'INVALID_REQUEST',
            'message'  => 'Idempotency key is required.',
        ), 400);
    }

    $body = $request->get_body();
    if (empty($body)) {
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'INVALID_BODY',
            'message'  => 'Request body is required.',
        ), 400);
    }

    $result = frictera_forward_friction_review_to_gateway($body, $idempotency_key);

    if (isset($result['wp_error']) && is_wp_error($result['wp_error'])) {
        frictera_log_intake_outcome('friction-review', '0', 'gateway_unreachable', $result['headers']['X-Correlation-Id'] ?? '');
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'SERVICE_UNAVAILABLE',
            'message'  => 'We could not store your request right now. Please try again in a moment.',
            'retryable'=> true,
        ), 503);
    }

    $status = intval($result['status']);
    $response_data = json_decode($result['body'], true);
    if (!is_array($response_data)) {
        $response_data = array();
    }

    // Preserve gateway semantics exactly: 201 created, 200 replay, 202 uncertain,
    // 400 validation, 409 conflict, 429 rate limited, 503 unavailable.
    if ($status === 201 || $status === 200) {
        frictera_log_intake_outcome('friction-review', strval($status), 'accepted', $result['headers']['X-Correlation-Id'] ?? '', $response_data['reviewId'] ?? '');
        return new WP_REST_Response($response_data, $status);
    }

    if ($status === 202) {
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'OUTCOME_UNCERTAIN',
            'message'  => isset($response_data['message']) ? sanitize_text_field($response_data['message']) : 'Submission status is uncertain. Please retry with the same idempotency key.',
            'retryable'=> true,
        ), 202);
    }

    if ($status === 400) {
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => isset($response_data['code']) ? sanitize_text_field($response_data['code']) : 'INVALID_REQUEST',
            'field'    => isset($response_data['field']) ? sanitize_text_field($response_data['field']) : null,
            'message'  => isset($response_data['message']) ? sanitize_text_field($response_data['message']) : 'Please check the information you provided and try again.',
        ), 400);
    }

    if ($status === 409) {
        return new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'IDEMPOTENCY_CONFLICT',
            'message'  => isset($response_data['message']) ? sanitize_text_field($response_data['message']) : 'This request was already submitted with different details.',
        ), 409);
    }

    if ($status === 429) {
        $retry_after = wp_remote_retrieve_header($result, 'retry-after');
        $response = new WP_REST_Response(array(
            'accepted' => false,
            'code'     => 'RATE_LIMITED',
            'message'  => isset($response_data['message']) ? sanitize_text_field($response_data['message']) : 'Too many submissions. Please wait a moment and try again.',
        ), 429);
        if ($retry_after !== '') {
            $response->header('Retry-After', sanitize_text_field($retry_after));
        }
        return $response;
    }

    frictera_log_intake_outcome('friction-review', strval($status), 'gateway_failed', $result['headers']['X-Correlation-Id'] ?? '');
    return new WP_REST_Response(array(
        'accepted' => false,
        'code'     => 'SERVICE_UNAVAILABLE',
        'message'  => 'We could not store your request right now. Please try again in a moment.',
        'retryable'=> true,
    ), 503);
}

/**
 * Format the result as a REST response.
 */
function frictera_format_intake_response($result) {
    if (is_wp_error($result)) {
        $data = $result->get_error_data();
        $status = isset($data['status']) ? intval($data['status']) : 500;
        $response = array('accepted' => false, 'error' => $result->get_error_code());
        if ($result->get_error_code() === 'validation_failed' && isset($data['errors'])) {
            $response['errors'] = $data['errors'];
        }
        return new WP_REST_Response($response, $status);
    }
    return new WP_REST_Response($result, 201);
}

/**
 * Determine whether a contact submission is eligible for immediate scheduling.
 *
 * Eligibility rules (approved architecture):
 * - business_type is provided;
 * - at least one friction area is selected;
 * - engagement_intent includes 'friction-review';
 * - required contact fields are present.
 */
function frictera_is_scheduling_eligible($data) {
    if (empty(sanitize_text_field($data['business_type'] ?? ''))) {
        return false;
    }

    $frictions = isset($data['frictions']) && is_array($data['frictions']) ? $data['frictions'] : array();
    if (empty($frictions)) {
        return false;
    }

    $engagement_intents = isset($data['engagement_intent']) && is_array($data['engagement_intent']) ? $data['engagement_intent'] : array();
    $engagement_intents = array_map('sanitize_text_field', $engagement_intents);
    if (!in_array('friction-review', $engagement_intents, true)) {
        return false;
    }

    $required = array('name', 'email', 'company', 'role');
    foreach ($required as $field) {
        if (empty(sanitize_text_field($data[$field] ?? ''))) {
            return false;
        }
    }

    return true;
}

/**
 * Create a signed opaque scheduling token tied to a submission identifier.
 */
function frictera_create_scheduling_token($submission_id) {
    if (empty(FRICTERA_SCHEDULING_TOKEN_SECRET) || strlen(FRICTERA_SCHEDULING_TOKEN_SECRET) < 32) {
        return '';
    }
    $payload = wp_json_encode(array(
        'sub' => sanitize_text_field($submission_id),
        'exp' => time() + intval(FRICTERA_SCHEDULING_TOKEN_TTL_SECONDS),
        'iat' => time(),
    ));
    $encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $encoded, FRICTERA_SCHEDULING_TOKEN_SECRET);
    return 'fst_' . $encoded . '.' . $signature;
}

/**
 * Verify and decode a scheduling token.
 */
function frictera_verify_scheduling_token($token) {
    if (empty(FRICTERA_SCHEDULING_TOKEN_SECRET) || strlen(FRICTERA_SCHEDULING_TOKEN_SECRET) < 32) {
        return null;
    }
    if (!is_string($token) || substr($token, 0, 4) !== 'fst_') {
        return null;
    }
    $parts = explode('.', substr($token, 4), 2);
    if (count($parts) !== 2) {
        return null;
    }
    list($encoded, $signature) = $parts;
    $expected = hash_hmac('sha256', $encoded, FRICTERA_SCHEDULING_TOKEN_SECRET);
    if (!hash_equals($expected, $signature)) {
        return null;
    }
    $payload_json = base64_decode(str_pad(strtr($encoded, '-_', '+/'), strlen($encoded) % 4, '=', STR_PAD_RIGHT));
    $payload = json_decode($payload_json, true);
    if (empty($payload) || empty($payload['exp']) || empty($payload['sub'])) {
        return null;
    }
    if (intval($payload['exp']) < time()) {
        return null;
    }
    return array('submission_id' => sanitize_text_field($payload['sub']));
}

/**
 * Check whether Cal.com scheduling is configured enough to function.
 */
function frictera_scheduling_is_configured() {
    if (!FRICTERA_SCHEDULING_ENABLED) {
        return false;
    }
    if (FRICTERA_SCHEDULING_PROVIDER !== 'calcom') {
        return false;
    }
    if (empty(FRICTERA_CALCOM_API_KEY)) {
        return false;
    }
    if (empty(FRICTERA_CALCOM_USERNAME) && empty(FRICTERA_CALCOM_EVENT_TYPE_ID)) {
        return false;
    }
    return true;
}

/**
 * Call the Cal.com API server-to-server.
 */
function frictera_calcom_request($path, $method = 'GET', $body = null) {
    if (!frictera_scheduling_is_configured()) {
        return new WP_Error('scheduling_not_configured', __('Scheduling is not configured.', 'frictera'), array('status' => 503));
    }

    $url = rtrim(FRICTERA_CALCOM_API_BASE, '/') . '/' . ltrim($path, '/');
    $headers = array(
        'Authorization' => 'Bearer ' . FRICTERA_CALCOM_API_KEY,
        'Content-Type'  => 'application/json',
        'cal-api-version' => '2024-06-11',
    );

    $args = array(
        'method'  => strtoupper($method),
        'headers' => $headers,
        'timeout' => 20,
    );

    if ($body !== null) {
        $args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $decoded = json_decode($response_body, true);

    if ($status >= 400) {
        $message = isset($decoded['message']) ? sanitize_text_field($decoded['message']) : __('Scheduling request failed.', 'frictera');
        return new WP_Error('calcom_error', $message, array('status' => $status));
    }

    return $decoded;
}

/**
 * Resolve the Cal.com event type identifier.
 */
function frictera_calcom_event_type_id() {
    if (!empty(FRICTERA_CALCOM_EVENT_TYPE_ID)) {
        return intval(FRICTERA_CALCOM_EVENT_TYPE_ID);
    }
    if (empty(FRICTERA_CALCOM_USERNAME) || empty(FRICTERA_CALCOM_EVENT_TYPE_SLUG)) {
        return 0;
    }
    $response = frictera_calcom_request('/v2/event-types?' . http_build_query(array('usernameList' => FRICTERA_CALCOM_USERNAME)));
    if (is_wp_error($response) || empty($response['data']) || !is_array($response['data'])) {
        return 0;
    }
    foreach ($response['data'] as $event_type) {
        if (isset($event_type['slug']) && $event_type['slug'] === FRICTERA_CALCOM_EVENT_TYPE_SLUG) {
            return isset($event_type['id']) ? intval($event_type['id']) : 0;
        }
    }
    return 0;
}

/**
 * REST handler: fetch available Cal.com slots for a verified scheduling token.
 */
function frictera_rest_scheduling_slots($request) {
    $token = sanitize_text_field($request->get_param('token') ?? '');
    $verified = frictera_verify_scheduling_token($token);
    if (empty($verified)) {
        return new WP_REST_Response(array(
            'available' => false,
            'message'   => 'Unable to load available times. Please try again or contact us.',
        ), 403);
    }

    if (!frictera_scheduling_is_configured()) {
        return new WP_REST_Response(array(
            'available' => false,
            'message'   => 'Scheduling is not available right now. We will be in touch by email.',
        ), 503);
    }

    $event_type_id = frictera_calcom_event_type_id();
    if (empty($event_type_id)) {
        return new WP_REST_Response(array(
            'available' => false,
            'message'   => 'Scheduling is not available right now. We will be in touch by email.',
        ), 503);
    }

    $start_date = gmdate('Y-m-d');
    $end_date = gmdate('Y-m-d', strtotime('+14 days'));
    $timezone = sanitize_text_field($request->get_param('timezone') ?? 'UTC');
    $timezone = in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : 'UTC';

    $query = http_build_query(array(
        'eventTypeId' => $event_type_id,
        'startTime'   => $start_date,
        'endTime'     => $end_date,
        'timeZone'    => $timezone,
    ));

    $response = frictera_calcom_request('/v2/slots?' . $query);
    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'available' => false,
            'message'   => 'We couldn\'t load available times right now. We will be in touch by email.',
        ), 503);
    }

    $slots = array();
    if (isset($response['data']['slots']) && is_array($response['data']['slots'])) {
        foreach ($response['data']['slots'] as $date => $day_slots) {
            if (!is_array($day_slots)) {
                continue;
            }
            foreach ($day_slots as $slot) {
                $time = isset($slot['time']) ? sanitize_text_field($slot['time']) : '';
                if ($time !== '') {
                    $slots[] = array(
                        'time'     => $time,
                        'date'     => sanitize_text_field($date),
                        'timezone' => $timezone,
                    );
                }
            }
        }
    }

    return new WP_REST_Response(array(
        'available' => !empty($slots),
        'slots'     => $slots,
        'timezone'  => $timezone,
        'eventType' => array(
            'duration' => 20,
            'title'    => 'Friction Review',
        ),
    ), 200);
}

/**
 * REST handler: create a Cal.com booking.
 */
function frictera_rest_scheduling_book($request) {
    $token = sanitize_text_field($request->get_param('token') ?? '');
    $verified = frictera_verify_scheduling_token($token);
    if (empty($verified)) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'Unable to confirm your booking. Please try again or contact us.',
        ), 403);
    }

    if (!frictera_scheduling_is_configured()) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'Scheduling is not available right now. We will be in touch by email.',
        ), 503);
    }

    $params = $request->get_json_params();
    $slot_time = sanitize_text_field($params['time'] ?? '');
    $timezone = sanitize_text_field($params['timezone'] ?? 'UTC');
    $timezone = in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : 'UTC';

    if (empty($slot_time)) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'Please select a valid appointment time.',
        ), 400);
    }

    $event_type_id = frictera_calcom_event_type_id();
    if (empty($event_type_id)) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'Scheduling is not available right now. We will be in touch by email.',
        ), 503);
    }

    $booking_body = array(
        'eventTypeId' => $event_type_id,
        'start'       => $slot_time,
        'timeZone'    => $timezone,
        'responses'   => array(
            'name'  => sanitize_text_field($params['name'] ?? ''),
            'email' => sanitize_email($params['email'] ?? ''),
            'guests' => array(),
        ),
        'metadata'    => array(
            'fricteraSubmissionId' => $verified['submission_id'],
            'source'               => 'website-contact-scheduling',
        ),
    );

    $response = frictera_calcom_request('/v2/bookings', 'POST', $booking_body);
    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'We couldn\'t confirm your booking right now. We will be in touch by email.',
        ), 503);
    }

    $booking_id = isset($response['data']['id']) ? sanitize_text_field($response['data']['id']) : '';
    $booking_status = isset($response['data']['status']) ? sanitize_text_field($response['data']['status']) : 'accepted';
    $start_time = isset($response['data']['start']) ? sanitize_text_field($response['data']['start']) : $slot_time;

    if (empty($booking_id)) {
        return new WP_REST_Response(array(
            'booked'  => false,
            'message' => 'We couldn\'t confirm your booking right now. We will be in touch by email.',
        ), 503);
    }

    // In a full implementation the gateway intake record would be updated here.
    // For this lane we return the booking details to the client without exposing internals.
    return new WP_REST_Response(array(
        'booked'    => true,
        'bookingId' => $booking_id,
        'status'    => $booking_status,
        'start'     => $start_time,
        'timezone'  => $timezone,
        'message'   => 'Your Friction Review is confirmed. You will receive a calendar invitation shortly.',
    ), 201);
}

/**
 * REST handler: receive Cal.com webhooks.
 */
function frictera_rest_scheduling_webhook($request) {
    $body = $request->get_body();
    $headers = $request->get_headers();
    $signature = isset($headers['x_cal_signature_v2']) ? sanitize_text_field($headers['x_cal_signature_v2']) : '';

    if (empty(FRICTERA_CALCOM_WEBHOOK_SECRET)) {
        return new WP_REST_Response(array('ok' => false), 503);
    }

    $expected = hash_hmac('sha256', $body, FRICTERA_CALCOM_WEBHOOK_SECRET);
    if (!hash_equals($expected, $signature)) {
        return new WP_REST_Response(array('ok' => false), 401);
    }

    $payload = json_decode($body, true);
    if (empty($payload) || empty($payload['triggerEvent']) || empty($payload['payload'])) {
        return new WP_REST_Response(array('ok' => false), 400);
    }

    $event = sanitize_text_field($payload['triggerEvent']);
    $booking = $payload['payload'];
    $booking_id = isset($booking['bookingId']) ? sanitize_text_field($booking['bookingId']) : '';
    $submission_id = isset($booking['metadata']['fricteraSubmissionId']) ? sanitize_text_field($booking['metadata']['fricteraSubmissionId']) : '';

    if (empty($booking_id)) {
        return new WP_REST_Response(array('ok' => false), 400);
    }

    $status_map = array(
        'BOOKING_CREATED'   => 'confirmed',
        'BOOKING_RESCHEDULED' => 'rescheduled',
        'BOOKING_CANCELLED' => 'cancelled',
        'BOOKING_REJECTED'  => 'rejected',
    );
    $status = isset($status_map[$event]) ? $status_map[$event] : 'unknown';

    // In a full implementation this would update the gateway intake record via an internal call.
    // For this lane we log safely without exposing internals.
    frictera_log_intake_outcome('website-contact', '200', 'scheduling_webhook_' . $status, '', $booking_id);

    return new WP_REST_Response(array('ok' => true), 200);
}

/**
 * Build the success response for contact intake, attaching scheduling eligibility when appropriate.
 */
function frictera_build_contact_response($result) {
    $result = frictera_format_intake_response($result);
    if ($result->get_status() !== 201) {
        return $result;
    }

    $data = $result->get_data();
    if (empty($data['accepted']) || empty($data['submissionId'])) {
        return $result;
    }

    $post_data = wp_unslash($_POST);
    $data['schedulingEligible'] = frictera_is_scheduling_eligible($post_data);
    if ($data['schedulingEligible'] && frictera_scheduling_is_configured()) {
        $data['schedulingToken'] = frictera_create_scheduling_token($data['submissionId']);
        $data['schedulingUrl'] = rest_url('frictera/v1/scheduling');
    }

    return new WP_REST_Response($data, 201);
}

/**
 * Expose a nonce and REST URL to the intake JavaScript.
 */
function frictera_localize_intake_script() {
    if (!is_page('contact') && !is_page('research')) {
        return;
    }
    $localize = array(
        'restUrl'   => esc_url_raw(rest_url('frictera/v1/')),
        'nonce'     => wp_create_nonce('frictera_intake_action'),
        'enabled'   => frictera_intake_is_live(),
    );

    if (is_page('contact')) {
        $localize['scheduling'] = array(
            'enabled' => frictera_scheduling_is_configured(),
            'restUrl' => esc_url_raw(rest_url('frictera/v1/scheduling/')),
        );
    }

    wp_localize_script('frictera-intake', 'fricteraIntake', $localize);
}
add_action('wp_enqueue_scripts', 'frictera_localize_intake_script', 20);

/**
 * Inject server-rendered honeypot and nonce fallback into intake forms.
 */
function frictera_inject_intake_form_fields() {
    if (!is_page('contact') && !is_page('research')) {
        return;
    }
    $nonce = wp_create_nonce('frictera_intake_action');
    $honeypot = '<input type="hidden" name="frictera_website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" />';
    $nonce_field = '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '" />';
    echo '<!-- Frictera intake security fields -->';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".guided-intake").forEach(function(f){if(!f.querySelector("[name=frictera_website]")){var h=document.createElement("input");h.type="hidden";h.name="frictera_website";h.value="";h.setAttribute("tabindex","-1");h.setAttribute("autocomplete","off");h.setAttribute("aria-hidden","true");f.appendChild(h);}if(!f.querySelector("[name=_wpnonce]")){var n=document.createElement("input");n.type="hidden";n.name="_wpnonce";n.value="' . esc_js($nonce) . '";f.appendChild(n);}});});</script>';
}
add_action('wp_footer', 'frictera_inject_intake_form_fields', 5);
