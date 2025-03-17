## [Lend flow Assessment](https://www.lendflow.com/)
- [NYT Best Sellers](https://developer.nytimes.com/docs/books-product/1/routes/lists/best-sellers/history.json/get) List & Filter


## Security Vulnerabilities

If you discover a security vulnerability within this application,
please send an e-mail to [erland.muchasaj@gmail.com](mailto:erland.muchasaj@gmail.com).
All security vulnerabilities will be promptly addressed.

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
git clone 
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
NYT_SECRET=
NYT_APP_ID=
NYT_ENABLED=true
````

Access urls are:
- _/api/v1/bestsellers_ - to get the bestsellers list
- _/health_ - to check the health of the application

[!Info]
The API is configured with a rate limiter of 60 requests per minute per user/ID or Ip address.


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
