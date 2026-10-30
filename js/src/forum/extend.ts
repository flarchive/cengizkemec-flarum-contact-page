import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import ContactPage from './components/ContactPage';

export default [
  new Extend.Routes()
    .add('cengizkemec-contact-page.contact', '/contact', ContactPage)
    .helper('contactPage', () => app.route('cengizkemec-contact-page.contact')),
];
