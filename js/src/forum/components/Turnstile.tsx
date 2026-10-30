import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type { TurnstileAppearance, TurnstileTheme } from '../../common/config';

interface TurnstileApi {
  render(container: HTMLElement, options: Record<string, unknown>): string;
  remove(widgetId: string): void;
  reset(widgetId?: string): void;
}

declare global {
  interface Window {
    turnstile?: TurnstileApi;
  }
}

export interface TurnstileAttrs extends ComponentAttrs {
  siteKey: string;
  theme: TurnstileTheme;
  appearance: TurnstileAppearance;
  onToken: (token: string) => void;
  onError: () => void;
  onExpire: () => void;
}

const SCRIPT_SRC = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let scriptPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
  if (window.turnstile) {
    return Promise.resolve();
  }

  if (scriptPromise) {
    return scriptPromise;
  }

  scriptPromise = new Promise<void>((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>(`script[src="${SCRIPT_SRC}"]`);
    const script = existing ?? document.createElement('script');

    const cleanUp = () => {
      script.removeEventListener('load', onLoad);
      script.removeEventListener('error', onError);
    };

    const onLoad = () => {
      cleanUp();

      if (window.turnstile) {
        resolve();
        return;
      }

      // An existing script may have finished loading before this component was
      // created. Poll briefly for the global rather than hanging forever.
      let attempts = 0;
      const timer = window.setInterval(() => {
        attempts += 1;

        if (window.turnstile || attempts > 100) {
          window.clearInterval(timer);

          if (window.turnstile) {
            resolve();
          } else {
            scriptPromise = null;
            reject(new Error('Turnstile did not initialise'));
          }
        }
      }, 100);
    };

    const onError = () => {
      cleanUp();
      script.remove();
      scriptPromise = null;
      reject(new Error('Turnstile script failed to load'));
    };

    script.addEventListener('load', onLoad);
    script.addEventListener('error', onError);

    if (!existing) {
      script.src = SCRIPT_SRC;
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);
    }
  });

  return scriptPromise;
}

export default class Turnstile extends Component<TurnstileAttrs> {
  private widgetId: string | null = null;
  private disposed = false;

  view(): Mithril.Children {
    return (
      <div
        className="ContactPage-turnstile"
        aria-label={app.translator.trans('cengizkemec-contact-page.forum.turnstile_label', {}, true) as string}
      />
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<TurnstileAttrs, this>): void {
    super.oncreate(vnode);

    this.mountWidget();
  }

  onremove(vnode: Mithril.VnodeDOM<TurnstileAttrs, this>): void {
    this.disposed = true;

    if (this.widgetId && window.turnstile) {
      try {
        window.turnstile.remove(this.widgetId);
      } catch {
        // The iframe may already have been removed by the browser.
      }
    }

    this.widgetId = null;
    super.onremove(vnode);
  }

  private async mountWidget(): Promise<void> {
    try {
      await loadScript();
    } catch (e) {
      if (!this.disposed) {
        this.attrs.onError();
      }
      return;
    }

    if (this.disposed || !this.element || !window.turnstile) {
      return;
    }

    try {
      this.widgetId = window.turnstile.render(this.element as HTMLElement, {
        sitekey: this.attrs.siteKey,
        theme: this.attrs.theme,
        appearance: this.attrs.appearance,
        action: 'contact',
        language: app.translator.getLocale().split('-')[0],
        callback: (token: string) => this.attrs.onToken(token),
        'error-callback': () => {
          this.attrs.onError();
          return true;
        },
        'expired-callback': () => this.attrs.onExpire(),
        'timeout-callback': () => this.attrs.onExpire(),
      });
    } catch (e) {
      this.attrs.onError();
    }
  }
}
