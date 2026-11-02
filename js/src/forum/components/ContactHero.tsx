import Hero from 'flarum/forum/components/Hero';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';

export interface ContactHeroAttrs extends ComponentAttrs {
  title: string;
  description: string | null;
}

export default class ContactHero extends Hero<ContactHeroAttrs> {
  className(): string {
    return 'ContactPage-hero';
  }

  bodyItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('title', <h1 className="ContactPage-heroTitle">{this.attrs.title}</h1>, 100);

    if (this.attrs.description) {
      items.add('description', <p className="ContactPage-heroDescription">{this.attrs.description}</p>, 90);
    }

    return items;
  }
}
