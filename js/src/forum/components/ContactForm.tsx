import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Alert from 'flarum/common/components/Alert';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type RequestError from 'flarum/common/utils/RequestError';
import Turnstile from './Turnstile';
import { fieldLabel, fieldPlaceholder, type ContactPageConfig } from '../../common/config';

export interface ContactFormAttrs extends ComponentAttrs {
  config: ContactPageConfig;
  onsuccess: () => void;
}

type FieldName = 'name' | 'email' | 'subject' | 'message' | 'category' | 'phone' | 'privacy';

export default class ContactForm extends Component<ContactFormAttrs> {
  private name = Stream('');
  private email = Stream('');
  private subject = Stream('');
  private message = Stream('');
  private category = Stream('');
  private phone = Stream('');
  private privacy = Stream(false);
  private honeypot = Stream('');

  private loading = false;
  private errors: Partial<Record<FieldName, string>> = {};
  private serverError: string | null = null;
  private startedAt = 0;
  private turnstileToken = '';
  private turnstileKey = 0;
  private turnstileError: string | null = null;

  oninit(vnode: Mithril.Vnode<ContactFormAttrs, this>): void {
    super.oninit(vnode);

    this.startedAt = performance.now();

    const config = this.attrs.config;
    const user = app.session.user;

    if (config.prefillAuthenticated && user) {
      this.name(user.displayName() || '');
      this.email(user.email() || '');
    }

    if (config.turnstile.enabled && !config.turnstile.siteKey) {
      this.turnstileError = this.trans('errors.turnstile_unavailable');
    }
  }

  view(): Mithril.Children {
    const config = this.attrs.config;

    return (
      <form className="Form ContactPage-form" onsubmit={(e: SubmitEvent) => this.submit(e)} novalidate>
        {this.serverError && (
          <Alert type="error" dismissible={false}>
            {this.serverError}
          </Alert>
        )}

        <div className="ContactPage-formRow">
          {config.fields.name.enabled && this.renderText('name', 'text', { autocomplete: 'name' })}
          {config.fields.email.enabled && this.renderText('email', 'email', { autocomplete: 'email' })}
        </div>

        <div className="ContactPage-formRow">
          {config.fields.subject.enabled && this.renderText('subject', 'text', { maxLength: config.subjectMaxLength })}
          {config.fields.category.enabled && config.categories.length > 0 && this.renderCategory()}
        </div>

        {config.fields.phone.enabled && this.renderText('phone', 'tel', { autocomplete: 'tel' })}
        {config.fields.message.enabled && this.renderMessage()}
        {config.fields.privacy.enabled && this.renderPrivacy()}

        <div className="ContactPage-honeypot" aria-hidden="true">
          <input
            id="contact-contact_hp"
            type="text"
            tabindex={-1}
            autocomplete="off"
            aria-hidden="true"
            value={this.honeypot()}
            oninput={(e: InputEvent) => this.honeypot((e.target as HTMLInputElement).value)}
          />
        </div>

        {config.turnstile.enabled && (
          <div className="Form-group ContactPage-turnstileGroup">
            <span className="ContactPage-label">{this.trans('turnstile_label')}</span>
            {this.turnstileKey >= 0 && config.turnstile.siteKey && (
              <Turnstile
                key={this.turnstileKey}
                siteKey={config.turnstile.siteKey}
                theme={config.turnstile.theme}
                appearance={config.turnstile.appearance}
                onToken={(token: string) => {
                  this.turnstileToken = token;
                  this.turnstileError = null;
                }}
                onError={() => {
                  this.turnstileToken = '';
                  this.turnstileError = this.trans('turnstile_error');
                  m.redraw();
                }}
                onExpire={() => {
                  this.turnstileToken = '';
                  this.turnstileError = this.trans('turnstile_expired');
                  m.redraw();
                }}
              />
            )}
            {this.turnstileError && (
              <p className="ContactPage-fieldError" role="alert">
                {this.turnstileError}
              </p>
            )}
          </div>
        )}

        <div className="Form-group Form-controls ContactPage-actions">
          <Button type="submit" className="Button Button--primary" loading={this.loading} disabled={this.loading || !!this.turnstileError}>
            {this.loading ? this.trans('sending') : this.trans('submit_button')}
          </Button>
        </div>
      </form>
    );
  }

  private renderText(field: 'name' | 'email' | 'subject' | 'phone', type: string, attrs: Record<string, unknown> = {}): Mithril.Children {
    const config = this.attrs.config;
    const id = `contact-${field}`;
    const error = this.errors[field];
    const helpId = `${id}-help`;
    const placeholder = fieldPlaceholder(config, field);
    const label = fieldLabel(config, field);
    const required = config.fields[field].required;

    return (
      <div className={`Form-group ContactPage-field ${error ? 'ContactPage-field--invalid' : ''}`}>
        <label for={id} className="ContactPage-label">
          {label}
          {required && (
            <span className="ContactPage-required" aria-hidden="true">
              {' '}
              *
            </span>
          )}
        </label>
        <input
          id={id}
          type={type}
          className="FormControl"
          value={this[field]()}
          placeholder={placeholder}
          required={required}
          aria-required={required}
          aria-invalid={!!error}
          aria-describedby={error ? helpId : undefined}
          disabled={this.loading}
          oninput={(e: InputEvent) => {
            (this[field] as Stream<string>)((e.target as HTMLInputElement).value);
            if (error) this.clearError(field);
          }}
          {...attrs}
        />
        {error && (
          <p id={helpId} className="ContactPage-fieldError" role="alert">
            {error}
          </p>
        )}
      </div>
    );
  }

  private renderMessage(): Mithril.Children {
    const config = this.attrs.config;
    const id = 'contact-message';
    const error = this.errors.message;
    const label = fieldLabel(config, 'message');
    const required = config.fields.message.required;
    const length = this.charLength(this.message());

    return (
      <div className={`Form-group ContactPage-field ${error ? 'ContactPage-field--invalid' : ''}`}>
        <label for={id} className="ContactPage-label">
          {label}
          {required && (
            <span className="ContactPage-required" aria-hidden="true">
              {' '}
              *
            </span>
          )}
        </label>
        <textarea
          id={id}
          className="FormControl"
          value={this.message()}
          placeholder={fieldPlaceholder(config, 'message')}
          rows={8}
          maxlength={config.messageMaxLength}
          required={required}
          aria-required={required}
          aria-invalid={!!error}
          aria-describedby={error ? `${id}-error` : `${id}-count`}
          disabled={this.loading}
          oninput={(e: InputEvent) => {
            this.message((e.target as HTMLTextAreaElement).value);
            if (error) this.clearError('message');
          }}
        />
        <div className="ContactPage-messageMeta">
          {error ? (
            <p id={`${id}-error`} className="ContactPage-fieldError" role="alert">
              {error}
            </p>
          ) : (
            <span />
          )}
          <span id={`${id}-count`} className="helpText" aria-live="polite">
            {this.trans('character_count', { count: length, max: config.messageMaxLength })}
          </span>
        </div>
      </div>
    );
  }

  private renderCategory(): Mithril.Children {
    const config = this.attrs.config;
    const id = 'contact-category';
    const error = this.errors.category;
    const label = fieldLabel(config, 'category');
    const required = config.fields.category.required;

    return (
      <div className={`Form-group ContactPage-field ${error ? 'ContactPage-field--invalid' : ''}`}>
        <label for={id} className="ContactPage-label">
          {label}
          {required && (
            <span className="ContactPage-required" aria-hidden="true">
              {' '}
              *
            </span>
          )}
        </label>
        <select
          id={id}
          className="FormControl"
          value={this.category()}
          required={required}
          aria-required={required}
          aria-invalid={!!error}
          aria-describedby={error ? `${id}-error` : undefined}
          disabled={this.loading}
          onchange={(e: Event) => {
            this.category((e.target as HTMLSelectElement).value);
            if (error) this.clearError('category');
          }}
        >
          <option value="">{this.trans('select_category')}</option>
          {config.categories.map((category) => (
            <option value={category}>{category}</option>
          ))}
        </select>
        {error && (
          <p id={`${id}-error`} className="ContactPage-fieldError" role="alert">
            {error}
          </p>
        )}
      </div>
    );
  }

  private renderPrivacy(): Mithril.Children {
    const config = this.attrs.config;
    const error = this.errors.privacy;
    const id = 'contact-privacy';
    const label = fieldLabel(config, 'privacy');
    const required = config.fields.privacy.required;

    return (
      <div className={`Form-group ContactPage-field ContactPage-privacy ${error ? 'ContactPage-field--invalid' : ''}`}>
        <label className="checkbox ContactPage-checkbox" for={id}>
          <input
            id={id}
            type="checkbox"
            checked={!!this.privacy()}
            required={required}
            aria-required={required}
            aria-invalid={!!error}
            aria-describedby={error ? `${id}-error` : undefined}
            disabled={this.loading}
            onchange={(e: Event) => {
              this.privacy((e.target as HTMLInputElement).checked);
              if (error) this.clearError('privacy');
            }}
          />
          <span>
            {label}
            {required && (
              <span className="ContactPage-required" aria-hidden="true">
                {' '}
                *
              </span>
            )}
            {config.privacyUrl && (
              <>
                {' '}
                <a href={config.privacyUrl} target="_blank" rel="noopener noreferrer">
                  {this.trans('privacy_link')}
                </a>
              </>
            )}
          </span>
        </label>
        {error && (
          <p id={`${id}-error`} className="ContactPage-fieldError" role="alert">
            {error}
          </p>
        )}
      </div>
    );
  }

  private submit(e: SubmitEvent): void {
    e.preventDefault();

    if (this.loading) {
      return;
    }

    this.serverError = null;
    this.errors = {};

    const errors = this.validateClient();
    if (Object.keys(errors).length > 0) {
      this.errors = errors;
      this.focusFirstError();
      return;
    }

    const config = this.attrs.config;

    if (config.turnstile.enabled) {
      if (!config.turnstile.siteKey) {
        this.serverError = this.trans('errors.turnstile_unavailable');
        return;
      }

      if (!this.turnstileToken) {
        this.serverError = this.trans('errors.turnstile_missing');
        return;
      }
    }

    const body: Record<string, unknown> = {
      elapsedMs: Math.max(0, Math.round(performance.now() - this.startedAt)),
      contact_hp: this.honeypot(),
    };

    if (config.fields.name.enabled) body.name = this.name();
    if (config.fields.email.enabled) body.email = this.email();
    if (config.fields.subject.enabled) body.subject = this.subject();
    if (config.fields.message.enabled) body.message = this.message();
    if (config.fields.category.enabled && config.categories.length > 0) body.category = this.category();
    if (config.fields.phone.enabled) body.phone = this.phone();
    if (config.fields.privacy.enabled) body.privacy = this.privacy() ? '1' : '0';
    if (config.turnstile.enabled) body.turnstileToken = this.turnstileToken;

    this.loading = true;

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl') as string}/contact`,
        body,
        errorHandler: (error: RequestError) => this.handleError(error),
      })
      .then(() => {
        this.loading = false;
        this.attrs.onsuccess();
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  private handleError(error: RequestError): void {
    this.loading = false;

    const errors = error.response?.errors;
    const codes = Array.isArray(errors) ? errors.map((entry) => String(entry.code ?? '')) : [];
    const onlyValidationError = error.status === 422 && codes.length > 0 && codes.every((code) => code === 'validation_error');

    // A validation error happens before Turnstile is consumed, so keep a valid
    // token for the corrected submission. Every other failure may have consumed
    // or invalidated it, so ask the visitor to verify again.
    if (!onlyValidationError) {
      this.resetTurnstile();
    }

    let general: string | null = null;

    if (Array.isArray(errors) && errors.length > 0) {
      errors.forEach((entry) => {
        const detail = typeof entry.detail === 'string' ? entry.detail : null;
        const source = entry.source as { pointer?: string } | undefined;
        const field = source?.pointer?.split('/').pop() as FieldName | undefined;

        if (field && field in this.attrs.config.fields) {
          this.errors[field] = detail || this.trans('errors.generic');
        } else if (detail) {
          general = detail;
        }
      });
    }

    if (!general && Object.keys(this.errors).length === 0) {
      general = this.trans('errors.generic');
    }

    this.serverError = general;
    this.focusFirstError();
    m.redraw();
  }

  private validateClient(): Partial<Record<FieldName, string>> {
    const config = this.attrs.config;
    const errors: Partial<Record<FieldName, string>> = {};

    const checkRequired = (field: FieldName, value: string) => {
      if (config.fields[field].enabled && config.fields[field].required && value.trim() === '') {
        errors[field] = this.trans('validation.required', { field: fieldLabel(config, field) });
      }
    };

    if (config.fields.name.enabled) {
      checkRequired('name', this.name());
      const length = this.charLength(this.name().trim());
      if (this.name().trim() !== '' && length < 2) {
        errors.name = this.trans('validation.name_too_short');
      } else if (length > 100) {
        errors.name = this.trans('validation.name_too_long');
      }
    }

    if (config.fields.email.enabled) {
      checkRequired('email', this.email());
      const email = this.email().trim();
      if (email !== '' && (email.length > 254 || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))) {
        errors.email = this.trans('validation.email_invalid');
      }
    }

    if (config.fields.subject.enabled) {
      checkRequired('subject', this.subject());
      if (this.charLength(this.subject()) > config.subjectMaxLength) {
        errors.subject = this.trans('validation.subject_too_long', { max: config.subjectMaxLength });
      }
    }

    if (config.fields.message.enabled) {
      checkRequired('message', this.message());
      const length = this.charLength(this.message().trim());
      if (this.message().trim() !== '' && length < config.messageMinLength) {
        errors.message = this.trans('validation.message_too_short', { min: config.messageMinLength });
      } else if (length > config.messageMaxLength) {
        errors.message = this.trans('validation.message_too_long', { max: config.messageMaxLength });
      }
    }

    if (config.fields.category.enabled && config.categories.length > 0) {
      checkRequired('category', this.category());
      if (this.category() !== '' && !config.categories.includes(this.category())) {
        errors.category = this.trans('validation.category_invalid');
      }
    }

    if (config.fields.phone.enabled) {
      checkRequired('phone', this.phone());
      if (this.phone().trim() !== '' && !/^[0-9+().\-\s]{3,30}$/.test(this.phone().trim())) {
        errors.phone = this.trans('validation.phone_invalid');
      }
    }

    if (config.fields.privacy.enabled && config.fields.privacy.required && !this.privacy()) {
      errors.privacy = this.trans('validation.privacy_required', { field: fieldLabel(config, 'privacy') });
    }

    return errors;
  }

  private clearError(field: FieldName): void {
    delete this.errors[field];
    m.redraw();
  }

  private resetTurnstile(): void {
    this.turnstileToken = '';
    this.turnstileError = null;
    this.turnstileKey += 1;
  }

  private focusFirstError(): void {
    const field = Object.keys(this.errors)[0] as FieldName | undefined;

    if (!field) {
      return;
    }

    window.setTimeout(() => {
      document.getElementById(`contact-${field}`)?.focus();
    }, 0);
  }

  private trans(key: string, params: Record<string, unknown> = {}): string {
    return app.translator.trans(`cengizkemec-contact-page.forum.${key}`, params, true) as string;
  }

  private charLength(value: string): number {
    return Array.from(value).length;
  }
}
