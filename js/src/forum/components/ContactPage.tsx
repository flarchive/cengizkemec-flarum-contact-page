import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import PageStructure from 'flarum/forum/components/PageStructure';
import Alert from 'flarum/common/components/Alert';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import ContactHero from './ContactHero';
import ContactForm from './ContactForm';
import { denialKey, getContactPageConfig, type ContactPageConfig } from '../../common/config';

export default class ContactPage extends Page {
  private config: ContactPageConfig = getContactPageConfig();
  private submitted = false;

  oninit(vnode: Mithril.Vnode<Record<string, unknown>, this>): void {
    super.oninit(vnode);

    this.config = getContactPageConfig();
  }

  oncreate(vnode: Mithril.VnodeDOM<Record<string, unknown>, this>): void {
    super.oncreate(vnode);

    app.setTitle(this.pageTitle());
    app.setTitleCount(0);
  }

  view(): Mithril.Children {
    const denial = denialKey(this.config);

    return (
      <PageStructure className="ContactPage" hero={() => <ContactHero title={this.pageTitle()} description={this.pageDescription()} />}>
        <div className="ContactPage-card">{denial ? this.renderDenial(denial) : this.submitted ? this.renderSuccess() : this.renderForm()}</div>
      </PageStructure>
    );
  }

  private renderForm(): Mithril.Children {
    return <ContactForm config={this.config} onsuccess={() => this.onSubmitted()} />;
  }

  private renderDenial(key: string): Mithril.Children {
    return (
      <div className="ContactPage-message">
        <Alert type="error" dismissible={false}>
          {app.translator.trans(key, {}, true) as string}
        </Alert>
        <p className="ContactPage-back">
          <a href={app.route('index')}>{app.translator.trans('core.forum.index.all_discussions_link')}</a>
        </p>
      </div>
    );
  }

  private renderSuccess(): Mithril.Children {
    const message = this.config.successMessage || (app.translator.trans('cengizkemec-contact-page.forum.success_message', {}, true) as string);

    return (
      <div className="ContactPage-message" role="status">
        <Alert type="success" dismissible={false}>
          {message}
        </Alert>
        <div className="ContactPage-actions">
          <Button
            className="Button Button--primary"
            icon="fas fa-paper-plane"
            onclick={() => {
              this.submitted = false;
              m.redraw();
            }}
          >
            {app.translator.trans('cengizkemec-contact-page.forum.send_another', {}, true) as string}
          </Button>
        </div>
      </div>
    );
  }

  private onSubmitted(): void {
    this.submitted = true;
    m.redraw();
  }

  private pageTitle(): string {
    return this.config.title || (app.translator.trans('cengizkemec-contact-page.forum.page_title', {}, true) as string);
  }

  private pageDescription(): string | null {
    return this.config.description || (app.translator.trans('cengizkemec-contact-page.forum.page_description', {}, true) as string);
  }
}
