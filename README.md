## [Lend flow Assessment](https://www.lendflow.com/)
- [NYT Best Sellers](https://developer.nytimes.com/docs/books-product/1/routes/lists/best-sellers/history.json/get) List & Filter

## Requirements:
- PHP 8.2+
- MySQL 8.0
- Apache 2.5
- Supervisor
- Composer
- Schedule runner (CRON)
- SSl certificate and valid
- SMTP mail sender

## Installation.

- Clone the repository
````bash    
git clone https://github.com/erlandmuchasaj/laravel-nyt-api-wrapper.git
````

- Install dependencies
````bash    
composer install
````

- Run the following command to create a copy of the .env file
````bash
cp .env.example .env
````

- Generate the application key
````bash
php artisan key:generate
````

- Update the .env file with your credentials to connect to NYT Bestseller Book API.
````bash
NYT_KEY=
NYT_ENABLED=true #this is used to enable or disable the NYT API
````

### Access urls are:
- `/api/v1/bestsellers` - to get the bestsellers list
- `/health` - to check the health of the application


### API Documentation
- `/openapi/index.html` - to view the API documentation

> [!INFO] 
> 
> The API is configured with a rate limiter of 60 requests per minute per `user/ID` or `Ip address`.


### Code Formating and Analysis.
- Run the following command to format the code
````bash
  composer format
````

- Run the following command to run the code analysis 
````bash
  composer analyze
````

### Tests
- Run the following command to run the tests
````bash
  composer test
````

#### Miscellaneous
The application uses 2 custom middlewares. 
1. `laravel-gzip` a custom-made laravel package to gzip api responses for a lighter payload. 
2. `AddXHeader` This is a global middleware to add some security headers on responses.
3. `IdempotencyMiddleware` This middleware is used to check if the request is idempotent or not.

## Security Vulnerabilities

If you discover a security vulnerability within this application,
please send an e-mail to [erland.muchasaj@gmail.com](mailto:erland.muchasaj@gmail.com).
All security vulnerabilities will be promptly addressed.
