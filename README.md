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