import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

const PREFIX = 'cengizkemec-contact-page.';
const admin = new Extend.Admin();
let priority = 10000;

const t = (key: string, params: Record<string, unknown> = {}) => app.translator.trans(`cengizkemec-contact-page.admin.${key}`, params);

const section = (key: string): void => {
  admin.customSetting(() => <h3 className="ExtensionPage-subHeader ContactPageSettings-section">{t(`sections.${key}`)}</h3>, (priority -= 10));
};

const setting = (
  key: string,
  type: string,
  extra: Record<string, unknown> | (() => Record<string, unknown>) = {},
  helpKey: string | null = `${key}_help`
): void => {
  admin.setting(() => {
    const help = helpKey ? t(`settings.${helpKey}`) : undefined;
    const extraValues = typeof extra === 'function' ? extra() : extra;

    return {
      setting: PREFIX + key,
      label: t(`settings.${key}_label`),
      help,
      type,
      ...extraValues,
    } as any;
  }, (priority -= 10));
};

section('general');
setting('enabled', 'bool');
setting('show_in_navigation', 'bool');
setting('nav_label', 'text');
setting('page_title', 'text');
setting('page_description', 'textarea');
setting('success_message', 'textarea');
setting('categories', 'textarea');

section('access');
setting('access_mode', 'select', () => ({
  options: {
    everyone: t('settings.access_mode_everyone'),
    registered: t('settings.access_mode_registered'),
    guests: t('settings.access_mode_guests'),
    disabled: t('settings.access_mode_disabled'),
  },
  default: 'everyone',
}));
setting('allow_guest_submissions', 'bool');
setting('allow_registered_submissions', 'bool');
setting('require_email_verification', 'bool');
setting('prefill_authenticated', 'bool');

section('form');
section('fields');

const fieldSettings = (field: string, hasPlaceholder = true): void => {
  setting(`field_${field}_enabled`, 'bool', {}, 'field_enabled_help');
  setting(`field_${field}_required`, 'bool', {}, 'field_required_help');
  setting(`field_${field}_label`, 'text', {}, 'field_label_help');

  if (hasPlaceholder) {
    setting(`field_${field}_placeholder`, 'text', {}, 'field_placeholder_help');
  }
};

fieldSettings('name');
fieldSettings('email');
fieldSettings('subject');
fieldSettings('message');
fieldSettings('category');
fieldSettings('phone');
fieldSettings('privacy', false);

section('limits');
setting('message_min_length', 'number', { min: 1, max: 50000 }, 'message_min_help');
setting('message_max_length', 'number', { min: 1, max: 50000 }, 'message_max_help');
setting('subject_max_length', 'number', { min: 1, max: 500 }, 'subject_max_help');
setting('privacy_url', 'url');

section('email');
setting('recipient_email', 'email');
setting('email_subject_prefix', 'text');
setting('sender_name', 'text');
setting('reply_to_enabled', 'bool');

section('security');
section('turnstile');
setting('turnstile_enabled', 'bool');
setting('turnstile_site_key', 'text');
setting('turnstile_secret_key', 'password', { autocomplete: 'off' });
setting('turnstile_theme', 'select', () => ({
  options: {
    auto: t('settings.turnstile_theme_auto'),
    light: t('settings.turnstile_theme_light'),
    dark: t('settings.turnstile_theme_dark'),
  },
  default: 'auto',
}));
setting('turnstile_appearance', 'select', () => ({
  options: {
    always: t('settings.turnstile_appearance_always'),
    'interaction-only': t('settings.turnstile_appearance_interaction_only'),
  },
  default: 'always',
}));
setting('turnstile_timeout', 'number', { min: 1, max: 30 });
setting('turnstile_expected_hostname', 'text');

section('abuse');
setting('rate_limit_enabled', 'bool');
setting('rate_limit_max', 'number', { min: 1, max: 1000 });
setting('rate_limit_window', 'number', { min: 10, max: 86400 });
setting('cooldown_seconds', 'number', { min: 0, max: 3600 });
setting('honeypot_enabled', 'bool');
setting('time_trap_enabled', 'bool');
setting('time_trap_seconds', 'number', { min: 0, max: 60 });
setting('duplicate_window_seconds', 'number', { min: 0, max: 86400 });
setting('max_request_bytes', 'number', { min: 1024, max: 1048576 });

section('localization');
setting('default_language', 'select', () => ({
  options: {
    auto: t('settings.default_language_auto'),
    en: t('settings.language_en'),
    tr: t('settings.language_tr'),
  },
  default: 'auto',
}));

admin.permission(
  () => ({
    icon: 'fas fa-envelope',
    label: t('permissions.bypass_rate_limit_label'),
    permission: 'cengizkemec-contact-page.bypassRateLimit',
  }),
  'moderate',
  90
);

export default [admin];
