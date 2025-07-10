# GrainDryerWebAPI

## Installation 

```
composer install or composer update
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Inisialisasi

```
php artisan mqtt:subscribe (Terminal 1)
php artisan serve --host=0.0.0.0 --port=8000 (Terminal 2)
```