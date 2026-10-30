import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import LinkButton from 'flarum/common/components/LinkButton';
import { canUseContactPage, getContactPageConfig } from '../common/config';

export default function addContactNav(): void {
  extend(IndexSidebar.prototype, 'navItems', (items) => {
    const config = getContactPageConfig();

    if (!config.showInNavigation || !canUseContactPage(config)) {
      return;
    }

    const label = config.navLabel || (app.translator.trans('cengizkemec-contact-page.forum.nav', {}, true) as string);

    items.add(
      'contactPage',
      <LinkButton href={app.route('cengizkemec-contact-page.contact')} icon="fas fa-envelope">
        {label}
      </LinkButton>,
      80
    );
  });
}
