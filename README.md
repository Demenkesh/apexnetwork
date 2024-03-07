# Laravel Sample
## Getting started

This project runs with Laravel version 10.47.0.
This project runs with php version 8.1.

Assuming you don't have this installed on your machine: [Laravel](https://laravel.com), [Composer](https://getcomposer.org).

``` bash
# install dependencies
composer update
npm install

# create .env file and generate the application key
cp .env.example .env
php artisan key:generate
```

Then launch the server:

``` bash
php artisan serve
```

The Laravel sample project is now up and running! Access it at http://localhost:8000 or http://127.0.0.1:8000.
