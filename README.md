# documentation Sample
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

Then launch the api documentation:
Then Api documentation , is been documentated by  swagger ui {{ darkaonline/l5-swagger }}

Then to acsess the api's  visit {{ url }}/api/documentation


Make sure to replace {{ url }} with the actual URL of your Laravel application and /api/documentation with the correct route for accessing the Swagger documentation based on your configuration.
