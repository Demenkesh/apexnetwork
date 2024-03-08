# documentation Sample

## Getting started

This project runs with Laravel version 10.47.0.
This project runs with php version 8.1.

Assuming you don't have this installed on your machine: [Laravel](https://laravel.com), [Composer](https://getcomposer.org).

## How to clone the code.

```bash
git clone https://github.com/Demenkesh/apexnetwork.git
```

```bash
# install dependencies
composer update
npm install

# create .env file and generate the application key
cp .env.example .env
php artisan key:generate
```

Then set up the env files , with your details , like setting up the database connection and mail connection

Then migrate the database :

```bash
php artisan migrate
```

Then launch the server:

```bash
php artisan serve
```

The Laravel sample project is now up and running! Access it at http://localhost:8000 or http://127.0.0.1:8000.

Then launch the api documentation:
The Api documentation , is been documentated by SWAGGER UI {{ darkaonline/l5-swagger }}

Then to acsess the api's visit {{ url }}/api/documentation {{ http://127.0.0.1:8000/api/documentation }}

Make sure to replace {{ url }} with the actual URL of your Laravel application and /api/documentation with the correct route for accessing the Swagger documentation based on your configuration.

## How to run the tests.
I show only how to use swagger ui in api documentation , especially for on where to add the access token for login

```bash
https://megascrypto.com/public/video/apexnetwork.mov
```
