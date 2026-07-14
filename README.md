# Mini Order Admin

Interni trening projekat — Laravel aplikacija za vođenje kupaca, proizvoda, porudžbina, lagera i poruka. Pojednostavljena verzija internog admin sistema: CRUD, interaktivni ekrani, admin panel, simulirana ERP sinhronizacija i AI draft flow za poruke.

## Tehnologije
- PHP 8.2+
- Laravel 12
- Livewire
- Filament (admin panel)
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
6. `npm run dev`
7. `php artisan serve`

Aplikacija se pokreće na http://127.0.0.1:8000
Admin panel je na http://127.0.0.1:8000/admin

Baza je SQLite — fajl se kreira automatski, ništa se ne podešava ručno.

## Test nalozi (za admin panel)
Kreiraju se seedom. Role kontrolišu pristup:

| Rola | Email | Lozinka | Pristup |
|------|-------|---------|---------|
| Admin | admin@test.com | admin123 | sve (kupci, proizvodi, porudžbine) |
| Sales | sales@test.com | sales123 | kupci i porudžbine (bez proizvoda) |
| Warehouse | warehouse@test.com | warehouse123 | proizvodi i porudžbine, samo pregled (bez kupaca) |

## Kako je projekat organizovan

Projekat je građen u fazama, i svaka faza je dodala jedan sloj nad istim domenom i istom bazom. Slojevi postoje paralelno — nisu zamene, nego pokazuju isti sistem kroz tri pristupa:

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

### Gde je poslovna logika
Poslovna logika je izdvojena iz UI slojeva (controller / Livewire / Filament) u zasebne klase, da bi bila na jednom mestu i da je svi slojevi mogu koristiti:

- **`app/Services/OrderConfirmationService.php`** — potvrda porudžbine: provera lagera, skidanje lagera i promena statusa, sve u jednoj DB transakciji sa `lockForUpdate` (zaštita od istovremenih potvrda poslednjeg komada).
- **`app/Services/OrderCreationService.php`** — kreiranje porudžbine sa stavkama i računanje ukupne vrednosti, u transakciji. Koriste ga i klasična forma (controller) i Livewire forma.
- **`app/Observers/OrderItemObserver.php`** — automatski preračunava `total_amount` porudžbine kad se stavka doda, izmeni ili obriše.
- **`app/Policies/`** — autorizacija po roli (admin/sales/warehouse), koju Filament poštuje u UI i na serveru.

UI slojevi (controller, Livewire, Filament) samo validiraju ulaz i pozivaju ove klase — ne sadrže poslovnu logiku.

## Struktura foldera (najvažnije)
```
app/
  Console/Commands/
    SyncProducts.php          ERP sinhronizacija (php artisan app:sync-products)
  Filament/
    Resources/                admin resursi (Customers, Products, Orders, Conversations)
    Widgets/                  dashboard widgeti (statistika, grafikon, poslednje porudžbine)
    Imports/                  CustomerImporter (CSV import)
  Http/Controllers/           klasični CRUD — Faza 1 (Customer, Product, Order)
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
routes/
  web.php                     rute grupisane po fazama
storage/app/
  erp-products.json           simulirani ERP izvor (za sync komandu)
tests/Feature/                testovi najvažnijih tokova
```