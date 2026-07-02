# Mini Order Admin

Interni trening projekat — Laravel aplikacija za vođenje kupaca, proizvoda, porudžbina i lagera.

## Requirements
- PHP 8.2+
- Composer
- SQLite
- Node.js / NPM

## Installation
1. `composer install`
2. `cp .env.example .env`
3. Baza je SQLite — ništa se ne podešava ručno
4. `php artisan key:generate`
5. `php artisan migrate`
6. `npm install`
7. `npm run dev`
8. `php artisan serve`

Aplikacija se pokreće na http://127.0.0.1:8000

## Struktura (Faza 1)
- **Customers** — kupci mogu biti fizička lica ili firme (jedna tabela, polje `type`). Firme imaju naziv firme i PIB.
- **Products** — proizvodi sa cenom (decimal), lagerom i statusom aktivnosti.
- CRUD za oba modula kroz controller + Blade stranice, sa server-side validacijom.
- Demo podaci se generišu preko `php artisan migrate:fresh --seed` (30 kupaca, 20 proizvoda).

## Kako je projekat pokrenut
Projekat je napravljen komandom `composer create-project laravel/laravel`. Baza je SQLite. Posle clone-a pokreće se: composer install, kopiranje .env, php artisan key:generate, php artisan migrate, npm install, npm run dev, php artisan serve.