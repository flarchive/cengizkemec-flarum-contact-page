import app from 'flarum/forum/app';
import addContactNav from './addContactNav';

export { default as extend } from './extend';

app.initializers.add('cengizkemec-contact-page', () => {
  addContactNav();
});
