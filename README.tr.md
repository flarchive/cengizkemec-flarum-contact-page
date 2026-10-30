# Flarum 2.0 için İletişim Sayfası

[![Backend](https://github.com/cengizkemec/flarum-contact-page/actions/workflows/backend.yml/badge.svg)](https://github.com/cengizkemec/flarum-contact-page/actions/workflows/backend.yml)
[![Frontend](https://github.com/cengizkemec/flarum-contact-page/actions/workflows/frontend.yml/badge.svg)](https://github.com/cengizkemec/flarum-contact-page/actions/workflows/frontend.yml)
[![Lisans: MIT](https://img.shields.io/badge/lisans-MIT-blue.svg)](LICENSE)

Flarum 2.0 için yapılandırılabilir, spam korumalı bir iletişim sayfası. Foruma
native görünümlü `/contact` sayfası, yönetilebilir bir kenar çubuğu bağlantısı ve
dinamik bir iletişim formu ekler. Ayrı bir SMTP yapılandırması oluşturmaz;
Flarum'un mevcut posta altyapısını kullanır.

İngilizce dokümantasyon: [README.md](README.md)

## Özellikler

- Native Flarum görünümüne sahip, mobil uyumlu `/contact` sayfası.
- Yalnızca erişimi olan ziyaretçilere gösterilen kenar çubuğu bağlantısı.
- Ad soyad, e-posta, konu, mesaj, kategori, telefon ve gizlilik onayı alanları.
  Her alan etkinleştirilebilir, devre dışı bırakılabilir, zorunlu veya isteğe
  bağlı yapılabilir.
- Dört erişim modu: herkes, yalnızca kayıtlı kullanıcılar, yalnızca misafirler,
  devre dışı.
- Misafir/kayıtlı kullanıcı gönderim anahtarları ve isteğe bağlı e-posta doğrulama
  zorunluluğu.
- Giriş yapmış kullanıcılar için ad ve e-posta ön doldurma.
- Satır bazında yönetilen iletişim kategorileri.
- Yapılandırılabilir mesaj ve konu uzunluk sınırları.
- Flarum'un mevcut posta taşıyıcısı üzerinden gönderim; ayrı SMTP ayarı yok.
- HTML ve düz metin e-posta sürümleri.
- Güvenli gönderen adresi Flarum `mail_from` ayarından alınır; ziyaretçi
  e-postası yalnızca doğrulandıktan sonra ve ayar açıksa `Reply-To` olur.
- İsteğe bağlı Cloudflare Turnstile; sunucu tarafında hostname, action, tazelik
  ve tekrar kullanım kontrolleriyle doğrulanır.
- Bal küpü, zaman tuzağı, bekleme süresi, IP başına hız sınırı, yinelenen gönderim
  algılama, istek boyutu sınırı ve Flarum CSRF koruması.
- Gizlilik dostu hız sınırlama: IP adresleri kuruluma özel tuzla HMAC'lenir ve
  yalnızca kısa ömürlü önbellek anahtarları olarak tutulur.
- Sunucu tarafında ayar doğrulama ve yalnızca güvenli ayarları içeren genel yapılandırma.
- Tam İngilizce ve Türkçe çeviriler.
- Migrasyon yok, çekirdek dosya değişikliği yok, kalıcı mesaj kaydı yok.

## Gereksinimler

| Bileşen | Desteklenen |
| --- | --- |
| Flarum | 2.0.0 veya üzeri |
| PHP | 8.3 veya üzeri |
| Veritabanı | MySQL 8+, MariaDB 10.6+, PostgreSQL 10+, SQLite 3.35+ |
| Posta | Flarum'da yapılandırılmış ve gerçekten gönderim yapabilen bir posta sürücüsü |

Bu depodaki test paketi Flarum **2.0.2**, PHP **8.4.27** ve SQLite ile
çalıştırılmıştır. Flarum'un kendi paket kısıtı PHP `^8.3` gerektirir; GitHub
Actions matrisi PHP 8.3 ve 8.4 sürümlerini kapsar.

## Kurulum

```bash
composer require cengizkemec/flarum-contact-page
```

Paket
[Packagist](https://packagist.org/packages/cengizkemec/flarum-contact-page)
üzerinde yayınlanmaktadır. Composer en güncel etiketli sürümü otomatik olarak
çözer.

Ardından:

1. **Yönetim → Uzantılar** bölümünü açın.
2. **İletişim Sayfası** uzantısını etkinleştirin.
3. Uzantı ayarlarını açın.
4. **Genel** bölümünde sayfayı etkinleştirin; başlık, kategoriler ve menü
   ayarlarını yapılandırın.
5. **E-posta** bölümünde alıcı e-posta adresini girin ve Flarum'un genel posta
   ayarlarının çalıştığını doğrulayın.
6. İsteğe bağlı olarak **Güvenlik** bölümünde Cloudflare Turnstile'ı etkinleştirin.
7. Gerekirse önbelleği temizleyin:

```bash
php flarum cache:clear
```

Sayfa `/contact` adresinde açılır.

## Yapılandırma

Ayrıntılı ayar referansı: [docs/configuration.md](docs/configuration.md)
(İngilizce).

### Alıcı e-posta

**İletişim Sayfası → E-posta → Alıcı e-posta adresi** bölümünden ayarlanır.
Geçerli ve gerçekten teslim edilebilir bir adres olmalıdır. Alıcı hiçbir zaman
ziyaretçi tarafından belirlenemez.

### E-posta gönderimi

Uzantı Flarum'un mevcut posta taşıyıcısını ve genel `mail_from` adresini kullanır.
İkinci bir SMTP yapılandırma sistemi eklemez.

- HTML ve düz metin görünümleri birlikte sağlanır. Flarum'un `mail_format` ayarı
  multipart, yalnızca HTML veya yalnızca düz metin gönderimini belirler.
- Ziyaretçi e-postası yalnızca doğrulandıysa ve ayar açıksa `Reply-To` olarak
  kullanılır.
- Posta sürücüsü gönderim yapamıyorsa veya taşıyıcı hata verirse ziyaretçi başarı
  mesajı görmez. Form başarısız durumda kapanır; gönderilmiş gibi davranmaz.
- Log ve Null sürücüleri gerçek e-posta teslim etmez; uzantı bunları kullanılamaz
  olarak değerlendirir.

### Cloudflare Turnstile

Turnstile isteğe bağlıdır ancak **herkese açık misafir formları için şiddetle
önerilir**.

1. Cloudflare panelinde bir Turnstile bileşeni oluşturun.
2. Foruma hizmet veren alan adını ekleyin.
3. Site anahtarını ve gizli anahtarı **Güvenlik → Turnstile** bölümüne girin.
4. Turnstile'ı etkinleştirip kaydedin.
5. Forumunuz istek ana makinesini değiştiren bir vekil sunucu arkasındaysa
   beklenen ana makine adını doğrulayın.

Gizli anahtar forum ön yüzüne gönderilmez ve günlüklere yazılmaz. Belirteçler
Cloudflare'in resmi Siteverify servisine HTTPS üzerinden gönderilir; `contact`
action'ı, ana makine adı ve tazelik kontrol edilir; tekrar kullanımı engellemek
için belirtecin HMAC özeti kısa süreliğine saklanır.

### Erişim modları

| Mod | Misafir | Kayıtlı kullanıcı |
| --- | --- | --- |
| Herkes | İzinli | İzinli |
| Yalnızca kayıtlı kullanıcılar | Reddedilir | İzinli |
| Yalnızca misafirler | İzinli | Reddedilir |
| Devre dışı | Reddedilir | Reddedilir |

Ek anahtarlar erişimi daha da daraltabilir. Tüm kontroller sunucuda uygulanır;
menü öğesini gizlemek yalnızca bir kolaylıktır.

## Ekran görüntüleri

| Masaüstü, açık tema | Mobil |
| --- | --- |
| ![Masaüstünde iletişim sayfası](docs/screenshots/contact-page-desktop.png) | ![Mobilde iletişim sayfası](docs/screenshots/contact-page-mobile.png) |

| Masaüstü, koyu tema | Yönetim |
| --- | --- |
| ![Koyu temada iletişim sayfası](docs/screenshots/contact-page-dark.png) | ![İletişim Sayfası ayarları](docs/screenshots/admin-settings.png) |

Ekran görüntüleri, bu uzantının çalıştığı gerçek bir Flarum 2.0.2 kurulumundan
alınmıştır. Ayrıntılar: [docs/screenshots](docs/screenshots/README.md).

## Güvenlik

Ayrıntılı tehdit modeli: [docs/security.md](docs/security.md) (İngilizce).

- Her gönderim sunucuda yetkilendirilir ve doğrulanır.
- Flarum'un normal CSRF koruması API uç noktasında etkin kalır.
- Turnstile doğrulaması sunucu tarafındadır, hata durumunda kapanır ve tekrar
  kullanımı engeller.
- E-posta adresleri Symfony Mime ile ayrıştırılır; CR/LF başlık enjeksiyonu ve
  görünen ad üzerinden başlık kaçırma reddedilir.
- Hız sınırı ve yinelenen gönderim anahtarları yalnızca HMAC içerir. Ham IP
  adresleri kalıcı olarak saklanmaz.
- Turnstile gizli anahtarı, alıcı adresi, hız sınırı ayarları ve güvenlik tuzu
  genel forum yükünde asla yer almaz.
- Günlüklere mesaj içeriği, ziyaretçi adı, e-postası veya ham IP yazılmaz.
- Turnstile isteğe bağlıdır; ancak herkese açık misafir formları için etkinleştirilmelidir.

Güvenlik açığı bildirimi için [SECURITY.md](SECURITY.md) dosyasını izleyin.

## Geliştirme

```bash
composer install
composer test:setup
composer test
composer analyse
composer validate --strict
```

Ön yüz:

```bash
cd js
npm ci
npm run check-typings
npm run build
npm run format-check
```

## Çeviriler

Uzantı `locale/en.yml` ve `locale/tr.yml` dosyalarını içerir. Kullanıcıya görünen
tüm metinler Flarum'un çeviri sistemi üzerinden gelir. Otomatik test, iki dil
dosyasının anahtar eşitliğini kontrol eder.

## Lisans

MIT © Cengiz Kemeç
