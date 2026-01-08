# Patron Platform - Patreon-like Video Sharing Platform

Kompletná Patreon-like platforma s podporou jednorázových príspevkov a mesačného predplatného cez Stripe, s administračným portálom pre nahrávanie videí, lajkami a komentármi.

## Funkcie

- ✅ **Autentifikácia používateľov** - Registrácia, prihlásenie, odhlásenie
- ✅ **Stripe integrácia** - Jednorázové príspevky a mesačné predplatné
- ✅ **Video nahrávanie** - Admin portál pre správu videí
- ✅ **Tier systém** - Rôzne úrovne predplatného s prístupom k exkluzívnemu obsahu
- ✅ **Likes systém** - Používatelia môžu dávať videám páči sa mi to
- ✅ **Komentáre** - Diskusia pod videami
- ✅ **Responzívny dizajn** - Funguje na všetkých zariadeniach

## Technológie

- **Backend:** PHP 7.4+
- **Databáza:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Platby:** Stripe API
- **Server:** Apache/Nginx

## Inštalácia

### 1. Požiadavky

- PHP 7.4 alebo vyšší
- MySQL 5.7 alebo vyšší
- Apache/Nginx web server
- Stripe účet (pre platby)

### 2. Klónovanie projektu

```bash
git clone <repository-url>
cd patron
```

### 3. Nastavenie databázy

Vytvorte databázu a importujte schému:

```bash
mysql -u root -p < database/schema.sql
```

Alebo importujte manuálne cez phpMyAdmin alebo príkazový riadok MySQL.

### 4. Konfigurácia

Upravte súbor `config/config.php`:

```php
// Databázové nastavenia
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'patron_platform');

// Stripe kľúče (získajte na https://stripe.com)
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY');
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET');

// URL vašej stránky
define('SITE_URL', 'http://localhost');
```

### 5. Nastavenie adresárov

Vytvorte adresár pre nahrávanie súborov:

```bash
mkdir -p uploads/videos
chmod 755 uploads
chmod 755 uploads/videos
```

### 6. Nastavenie Stripe Webhookov

1. V Stripe Dashboard prejdite na Developers > Webhooks
2. Pridajte nový webhook endpoint: `https://vasa-domena.sk/api/webhook.php`
3. Vyberte udalosti:
   - `payment_intent.succeeded`
   - `checkout.session.completed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
4. Skopírujte webhook secret do konfigurácie

### 7. Apache nastavenie (.htaccess)

Uistite sa, že máte povolený mod_rewrite v Apache.

## Použitie

### Predvolený admin účet

Po importovaní databázy môžete použiť:
- **Email:** admin@patron.local
- **Heslo:** admin123

⚠️ **DÔLEŽITÉ:** Zmeňte toto heslo po prvom prihlásení!

### Nahrávanie videí

1. Prihláste sa ako admin
2. Prejdite na Admin Panel
3. Kliknite na "Nahrať video"
4. Vyplňte informácie a vyberte video súbor
5. Voliteľne nastavte tier pre exkluzívny obsah

### Vytvorenie predplatného

1. Používateľ klikne na "Podpora"
2. Vyberie tier predplatného
3. Presmeruje sa na Stripe Checkout
4. Po úspešnej platbe je predplatné aktivované

### Jednorázový príspevok

1. Používateľ klikne na "Podpora"
2. Zadá sumu a voliteľne správu
3. Vyplní platobné údaje (Stripe)
4. Príspevok je spracovaný

## Štruktúra projektu

```
patron/
├── admin/                  # Admin portál
│   ├── index.php          # Dashboard
│   ├── upload-video.php   # Nahrávanie videí
│   └── manage-videos.php  # Správa videí
├── api/                   # API endpointy
│   ├── create-payment-intent.php
│   ├── create-subscription.php
│   ├── webhook.php
│   ├── like.php
│   ├── comment.php
│   └── get-comments.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── video.js
├── config/
│   ├── config.php         # Hlavná konfigurácia
│   └── database.php       # Databázové pripojenie
├── database/
│   └── schema.sql         # SQL schéma
├── includes/
│   ├── functions.php      # Helper funkcie
│   ├── header.php
│   └── footer.php
├── uploads/               # Nahrané súbory
│   └── videos/
├── index.php             # Hlavná stránka
├── watch.php             # Prehrávač videí
├── login.php
├── register.php
├── logout.php
├── support.php           # Stránka podpory
└── subscription-success.php
```

## Testovanie Stripe

Pre testovanie použite tieto testovacie karty:

- **Úspešná platba:** 4242 4242 4242 4242
- **Zamietnutá platba:** 4000 0000 0000 0002
- CVC: akékoľvek 3 číslice
- Dátum expirácie: akýkoľvek budúci dátum

## Zabezpečenie

Pre produkčné nasadenie:

1. Zmeňte predvolené admin heslo
2. Nastavte `display_errors = 0` v `config.php`
3. Použite HTTPS (SSL certifikát)
4. Nastavte správne práva na súbory (755 pre adresáre, 644 pre súbory)
5. Použite silné databázové heslo
6. Pravidelne aktualizujte PHP a MySQL
7. Zálohujte databázu pravidelne

## Riešenie problémov

### Video sa nenahrá

- Skontrolujte veľkosť súboru (max 500MB)
- Zvýšte `upload_max_filesize` a `post_max_size` v php.ini
- Skontrolujte práva na adresári `uploads/`

### Stripe platby nefungujú

- Skontrolujte, či sú správne nastavené API kľúče
- Otvorte konzolu prehliadača a skontrolujte chyby
- Overte, že webhook endpoint je dostupný z internetu

### Databázové chyby

- Skontrolujte databázové prihlásenie v `config/config.php`
- Overte, že databáza a tabuľky existujú
- Skontrolujte MySQL chybové logy

## Licencia

Tento projekt je open-source a môžete ho použiť podľa potreby.

## Podpora

Pre problémy a otázky vytvorte issue v GitHub repozitári.

## Autor

Vytvorené pre Patreon-like platformu s kompletnou funkcionalitou.
