# Mini Order Admin

Interni trening projekat — Laravel aplikacija za vođenje kupaca, proizvoda, porudžbina i lagera.

## Requirements
- PHP 8.2+
- Composer
- MySQL (npr. kroz XAMPP)
- Node.js / NPM

## Installation
1. `composer install`
2. `cp .env.example .env`
3. Podesi DB podatke u `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
4. Napravi praznu bazu `mini_order_admin` u MySQL-u
5. `php artisan key:generate`
6. `php artisan migrate`
7. `npm install`
8. `npm run dev`
9. `php artisan serve`

Aplikacija se pokreće na http://127.0.0.1:8000