# Mini Order Admin

Interni trening projekat — Laravel aplikacija za vođenje kupaca, proizvoda, porudžbina, lagera i poruka. Pojednostavljena verzija internog admin sistema: CRUD, interaktivni ekrani, admin panel, simulirana ERP sinhronizacija, AI draft flow za poruke i korisnička prodavnica.

## Tehnologije
- PHP 8.2+
- Laravel 12
- Livewire
- Filament (admin panel)
- Inertia.js + React (korisnička strana)
- SQLite (baza)
- Blade
- PHPUnit (testovi)

## Requirements
- PHP 8.2+
- Composer
- Node.js / NPM
- SQLite (dolazi uz PHP, ništa se posebno ne instalira)

## Instalacija (pokretanje od nule)
1. `composer install`
2. `cp .env.example .env`
3. `php artisan key:generate`
4. `php artisan migrate --seed` (kreira tabele i puni demo podatke)
5. `npm install`
6. `npm run dev` (obavezno — Vite servira React deo)
7. `php artisan serve` (u drugom terminalu)

Aplikacija se pokreće na http://127.0.0.1:8000
Admin panel je na http://127.0.0.1:8000/admin
Prodavnica je na http://127.0.0.1:8000/shop

Baza je SQLite — fajl se kreira automatski, ništa se ne podešava ručno.

## Test nalozi (za admin panel)
Kreiraju se seedom. Role kontrolišu pristup:

| Rola | Email | Lozinka | Pristup |
|------|-------|---------|---------|
| Admin | admin@test.com | admin123 | sve (kupci, proizvodi, porudžbine) |
| Sales | sales@test.com | sales123 | kupci i porudžbine (bez proizvoda) |
| Warehouse | warehouse@test.com | warehouse123 | proizvodi i porudžbine, samo pregled (bez kupaca) |

Kupci se registruju sami kroz `/register` i nemaju pristup admin panelu.

## Kako je projekat organizovan

Projekat je građen u fazama, i svaka faza je dodala jedan sloj nad istim domenom i istom bazom. Slojevi postoje paralelno — nisu zamene, nego pokazuju isti sistem kroz različite pristupe:

**1. Klasični Laravel (Blade + controller)** — Faza 1
CRUD za kupce, proizvode i porudžbine kroz obične controller-e i Blade stranice, sa server-side validacijom. Rute: `/customers`, `/products`, `/orders`. Ovo je osnova — bez interaktivnosti, klasičan request/response.

**2. Livewire ekrani** — Faza 2
Interaktivni ekrani nad istim podacima: pretraga kupaca, filteri proizvoda, dinamička forma porudžbine, status board, inline editovanje, CSV import, dashboard, konverzacije. Rute: `/customers-search`, `/products-filters`, `/orders-create`, `/orders-board`, `/products-inline`, `/customers-import`, `/dashboard`, `/conversations`.

**3. Filament admin panel** — Faza 3
Produkcijski admin panel na `/admin`: resursi za kupce, proizvode, porudžbine i konverzacije, sa formama, tabelama, filterima, custom akcijama, dashboard widgetima i kontrolom pristupa po roli (Policy klase).

**Faza 4 — integracije i AI flow**
- ERP sinhronizacija: `php artisan app:sync-products` (čita `storage/app/erp-products.json`, kreira/ažurira proizvode po `external_id`, bez duplikata)
- AI draft flow: u Filament konverzacijama dugme "Generate draft reply" pravi predlog odgovora (statički za sad), koji čovek pregleda i odobrava pre slanja
- Filament import kupaca iz CSV-a sa validacijom i izveštajem o greškama

**Faza 5 — cleanup i finalni review**
Poslovna logika izvučena iz kontrolera u servise, uklonjen mrtav kod, sređene rute, dodat UserSeeder (projekat radi od nule), kompletiran README.

**Faza 6 — Inertia + React (korisnička strana)**
Instaliran Laravel Breeze sa Inertia i React (`resources/js/`). Inertia povezuje Laravel backend sa React frontendom bez zasebnog API-ja: controller vraća `Inertia::render('Stranica', [podaci])`, a React komponenta te podatke dobija kao props. Auth (registracija, prijava, profil) su React stranice.

Korisnička strana (prodavnica): katalog proizvoda, detalj, korpa (sesija), poručivanje i pregled sopstvenih porudžbina. Poručivanje poziva postojeći `OrderCreationService` — isti servis koji koriste klasična Blade forma i Livewire forma, pa porudžbina napravljena iz prodavnice izgleda identično onoj iz admina i odmah je vidljiva u `/admin/orders`.

Pristup: kupci se registruju kroz `/register` i dobijaju i `User` nalog i povezani `Customer` zapis (`users.customer_id`). Kupci nemaju pristup Filament panelu — `User::canAccessPanel()` pušta samo interne role (admin/sales/warehouse).

Napomena: Breeze podrazumevano koristi `/dashboard`, ali ta ruta je već zauzeta Livewire dashboard-om (Faza 2), pa je Breeze stranica premeštena na `/account` — postojeći ekrani su ostali netaknuti.

## Rute (mapa aplikacije)

### Klasični Laravel — Faza 1 (controller + Blade)
| Metoda | Ruta | Opis |
|--------|------|------|
| GET | `/customers` | lista kupaca + pretraga |
| GET/POST | `/customers/create`, `/customers` | nov kupac |
| GET/PUT | `/customers/{id}/edit` | izmena kupca |
| DELETE | `/customers/{id}` | brisanje kupca |
| GET | `/products` | lista proizvoda + pretraga |
| GET/POST | `/products/create`, `/products` | nov proizvod |
| GET/PUT | `/products/{id}/edit` | izmena proizvoda |
| DELETE | `/products/{id}` | brisanje proizvoda |
| GET | `/orders` | lista porudžbina |
| GET/POST | `/orders/create`, `/orders` | nova porudžbina |
| GET | `/orders/{id}` | detalji porudžbine |
| POST | `/orders/{id}/confirm` | potvrda porudžbine (skida lager) |

### Livewire ekrani — Faza 2 (interaktivni)
| Ruta | Ekran |
|------|-------|
| `/customers-search` | pretraga kupaca (live, debounce, paginacija) |
| `/products-filters` | filteri proizvoda (status, lager, sortiranje) |
| `/orders-create` | dinamička forma porudžbine |
| `/orders-board` | status board sa dozvoljenim prelazima |
| `/products-inline` | inline izmena naziva proizvoda |
| `/customers-import` | CSV import kupaca |
| `/dashboard` | dashboard sa filterom po periodu |
| `/conversations` | konverzacije (poruke, statusi, kanali) |

### Filament admin panel — Faza 3
| Ruta | Opis |
|------|------|
| `/admin` | admin panel (login, dashboard sa widgetima) |
| `/admin/customers` | resurs kupaca (+ CSV import) |
| `/admin/products` | resurs proizvoda |
| `/admin/orders` | resurs porudžbina (+ Confirm akcija, stavke) |
| `/admin/conversations` | konverzacije (+ Generate draft reply) |

### Artisan komande — Faza 4
| Komanda | Opis |
|---------|------|
| `php artisan app:sync-products` | ERP sinhronizacija proizvoda iz JSON-a |

### Inertia + React — Faza 6
| Ruta | Opis |
|------|------|
| `/register` | registracija kupca (React) |
| `/login` | prijava (React) |
| `/account` | korisnički nalog (React) |
| `/profile` | izmena profila (React) |
| `/shop` | katalog proizvoda (pretraga, paginacija) |
| `/shop/{id}` | detalj proizvoda + dodavanje u korpu |
| `/cart` | korpa (izmena količine, uklanjanje) |
| `/checkout` | poručivanje (poziva OrderCreationService) |
| `/my-orders` | porudžbine ulogovanog kupca |
| `/my-orders/{id}` | detalj sopstvene porudžbine |

### Gde je poslovna logika
Poslovna logika je izdvojena iz UI slojeva (controller / Livewire / Filament / Inertia) u zasebne klase, da bi bila na jednom mestu i da je svi slojevi mogu koristiti:

- **`app/Services/OrderConfirmationService.php`** — potvrda porudžbine: provera lagera, skidanje lagera i promena statusa, sve u jednoj DB transakciji sa `lockForUpdate` (zaštita od istovremenih potvrda poslednjeg komada).
- **`app/Services/OrderCreationService.php`** — kreiranje porudžbine sa stavkama i računanje ukupne vrednosti, u transakciji. Koriste ga klasična forma (controller), Livewire forma i checkout u prodavnici.
- **`app/Observers/OrderItemObserver.php`** — automatski preračunava `total_amount` porudžbine kad se stavka doda, izmeni ili obriše.
- **`app/Policies/`** — autorizacija po roli (admin/sales/warehouse), koju Filament poštuje u UI i na serveru.

UI slojevi samo validiraju ulaz i pozivaju ove klase — ne sadrže poslovnu logiku. Isto važi i za prodavnicu (Faza 6): `CheckoutController` validira korpu i poziva `OrderCreationService`, ne duplira logiku kreiranja porudžbine.

## Struktura foldera (najvažnije)
```
app/
  Console/Commands/
    SyncProducts.php          ERP sinhronizacija (php artisan app:sync-products)
  Filament/
    Resources/                admin resursi (Customers, Products, Orders, Conversations)
    Widgets/                  dashboard widgeti (statistika, grafikon, poslednje porudžbine)
    Imports/                  CustomerImporter (CSV import)
  Http/Controllers/           klasični CRUD (Faza 1), prodavnica (Faza 6), Auth (Breeze)
    ShopController.php        katalog i detalj proizvoda
    CartController.php        korpa u sesiji
    CheckoutController.php    poručivanje (poziva OrderCreationService)
    MyOrdersController.php    porudžbine ulogovanog kupca
  Livewire/                   interaktivni ekrani — Faza 2
  Models/                     Eloquent modeli i relacije
  Observers/
    OrderItemObserver.php     automatski preračun total_amount
  Policies/                   autorizacija po roli (Customer, Order, Product)
  Providers/
    Filament/                 AdminPanelProvider (podešavanje /admin panela)
  Services/
    OrderConfirmationService.php   potvrda porudžbine (lager + transakcija)
    OrderCreationService.php       kreiranje porudžbine sa stavkama
database/
  migrations/                 struktura svih tabela
  seeders/
    DatabaseSeeder.php        glavni seeder (poziva ostale)
    UserSeeder.php            test nalozi (admin, sales, warehouse)
    OrderSeeder.php           demo porudžbine
    ConversationSeeder.php    demo konverzacije i poruke
  factories/                  generisanje demo kupaca i proizvoda
resources/
  views/                      Blade stranice (Faza 1) i Livewire šabloni (Faza 2)
  js/                         React komponente kroz Inertia (Faza 6)
    Pages/Shop/               prodavnica (katalog, detalj, korpa, checkout, porudžbine)
    Pages/Auth/               registracija, prijava, reset lozinke
    Layouts/ShopLayout.jsx    navigacija prodavnice (korpa sa brojem stavki)
    Components/               React komponente (Breeze)
routes/
  web.php                     rute grupisane po fazama
storage/app/
  erp-products.json           simulirani ERP izvor (za sync komandu)
tests/Feature/                testovi najvažnijih tokova
```

## Testovi
```
php artisan test
```
Testovi pokrivaju najvažnija poslovna pravila: kreiranje kupca i validaciju, kreiranje porudžbine, potvrdu koja skida lager, odbijanje potvrde kad nema lagera (atomarnost transakcije), Livewire formu i pretragu, povezivanje naloga sa kupcem pri registraciji, zabranu pristupa admin panelu za kupce, i poručivanje kroz prodavnicu (kreiranje porudžbine na pravog kupca, provera lagera, zabrana pristupa tuđoj porudžbini).