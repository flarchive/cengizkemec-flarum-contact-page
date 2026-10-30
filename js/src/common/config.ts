import app from 'flarum/forum/app';
import type User from 'flarum/common/models/User';

export type AccessMode = 'everyone' | 'registered' | 'guests' | 'disabled';
export type TurnstileTheme = 'auto' | 'light' | 'dark';
export type TurnstileAppearance = 'always' | 'interaction-only';

export interface ContactFieldConfig {
  enabled: boolean;
  required: boolean;
  label: string | null;
  placeholder: string | null;
}

export interface ContactPageConfig {
  enabled: boolean;
  showInNavigation: boolean;
  navLabel: string | null;
  title: string | null;
  description: string | null;
  successMessage: string | null;
  accessMode: AccessMode;
  allowGuestSubmissions: boolean;
  allowRegisteredSubmissions: boolean;
  requireEmailVerification: boolean;
  prefillAuthenticated: boolean;
  categories: string[];
  fields: {
    name: ContactFieldConfig;
    email: ContactFieldConfig;
    subject: ContactFieldConfig;
    message: ContactFieldConfig;
    category: ContactFieldConfig;
    phone: ContactFieldConfig;
    privacy: ContactFieldConfig;
  };
  messageMinLength: number;
  messageMaxLength: number;
  subjectMaxLength: number;
  privacyUrl: string | null;
  turnstile: {
    enabled: boolean;
    siteKey: string | null;
    theme: TurnstileTheme;
    appearance: TurnstileAppearance;
  };
}

const EMPTY_FIELD: ContactFieldConfig = {
  enabled: false,
  required: false,
  label: null,
  placeholder: null,
};

export const EMPTY_CONFIG: ContactPageConfig = {
  enabled: false,
  showInNavigation: false,
  navLabel: null,
  title: null,
  description: null,
  successMessage: null,
  accessMode: 'disabled',
  allowGuestSubmissions: false,
  allowRegisteredSubmissions: false,
  requireEmailVerification: false,
  prefillAuthenticated: false,
  categories: [],
  fields: {
    name: { ...EMPTY_FIELD },
    email: { ...EMPTY_FIELD },
    subject: { ...EMPTY_FIELD },
    message: { ...EMPTY_FIELD },
    category: { ...EMPTY_FIELD },
    phone: { ...EMPTY_FIELD },
    privacy: { ...EMPTY_FIELD },
  },
  messageMinLength: 1,
  messageMaxLength: 5000,
  subjectMaxLength: 150,
  privacyUrl: null,
  turnstile: {
    enabled: false,
    siteKey: null,
    theme: 'auto',
    appearance: 'always',
  },
};

/**
 * Reads the whitelisted server-side config. Falls back to a disabled form if
 * the attribute is missing (for example with a stale cached page).
 */
export function getContactPageConfig(): ContactPageConfig {
  const raw = app.forum.attribute('contactPageConfig') as Partial<ContactPageConfig> | undefined;

  if (!raw || typeof raw !== 'object') {
    return EMPTY_CONFIG;
  }

  return {
    ...EMPTY_CONFIG,
    ...raw,
    categories: Array.isArray(raw.categories) ? raw.categories.filter((category): category is string => typeof category === 'string') : [],
    fields: {
      ...EMPTY_CONFIG.fields,
      ...(raw.fields ?? {}),
    },
    turnstile: {
      ...EMPTY_CONFIG.turnstile,
      ...(raw.turnstile ?? {}),
    },
  };
}

export function denialKey(config: ContactPageConfig, user: User | null = app.session.user ?? null): string | null {
  if (!config.enabled || config.accessMode === 'disabled') {
    return 'cengizkemec-contact-page.forum.access.disabled';
  }

  if (!['everyone', 'registered', 'guests'].includes(config.accessMode)) {
    return 'cengizkemec-contact-page.forum.access.disabled';
  }

  if (!user) {
    if (!['everyone', 'guests'].includes(config.accessMode)) {
      return 'cengizkemec-contact-page.forum.access.guests_not_allowed';
    }

    if (!config.allowGuestSubmissions) {
      return 'cengizkemec-contact-page.forum.access.guest_submissions_disabled';
    }

    return null;
  }

  if (config.accessMode === 'guests') {
    return 'cengizkemec-contact-page.forum.access.registered_not_allowed';
  }

  if (!config.allowRegisteredSubmissions) {
    return 'cengizkemec-contact-page.forum.access.registered_submissions_disabled';
  }

  if (config.requireEmailVerification && !user.isEmailConfirmed()) {
    return 'cengizkemec-contact-page.forum.access.email_verification_required';
  }

  return null;
}

export function canUseContactPage(config: ContactPageConfig, user: User | null = app.session.user ?? null): boolean {
  return denialKey(config, user) === null;
}

export function fieldLabel(config: ContactPageConfig, field: keyof ContactPageConfig['fields']): string {
  const custom = config.fields[field].label;

  return custom || (app.translator.trans(`cengizkemec-contact-page.forum.fields.${field}_label`, {}, true) as string);
}

export function fieldPlaceholder(config: ContactPageConfig, field: keyof ContactPageConfig['fields']): string {
  const custom = config.fields[field].placeholder;

  return custom || (app.translator.trans(`cengizkemec-contact-page.forum.fields.${field}_placeholder`, {}, true) as string);
}
