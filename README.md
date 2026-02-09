# Paysera Fund Transfer API

A secure API for transferring funds between accounts, built with Symfony 7.1, PHP 8.2, MySQL, and Redis.  
This project demonstrates scalable, reliable financial components with transaction integrity and async processing.

---

## Setup & Usage (Step by Step)

Clone the repository and move into the project folder:
git clone https://github.com/<your-username>/paysera.git
cd paysera

Install dependencies:
composer install

Configure environment by copying `.env` to `.env.local` and setting DB + Redis:
DATABASE_URL="mysql://root:password@127.0.0.1:3306/paysera"
REDIS_URL="redis://127.0.0.1:6379"

For tests:
DATABASE_URL="mysql://root:password@127.0.0.1:3306/paysera_test"

Create databases and run migrations:
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test

Run Symfony server:
symfony server:start

Start Redis server:
- Linux/macOS:
  redis-server
- Windows (Memurai or Redis for Windows port):
  redis-server.exe

Test Redis:
redis-cli ping
Expected output:
PONG

Start Redis worker:
php bin/console app:process-transfers

---

## API Endpoints

Create Account:
POST /api/account
Body:
{
  "owner": "Alice",
  "balance": 1000.00
}
Response:
{
  "accountId": 1,
  "owner": "Alice",
  "balance": 1000,
  "status": "CREATED"
}

Transfer Funds:
POST /api/transfer
Body:
{
  "fromAccountId": 1,
  "toAccountId": 2,
  "amount": 100.00
}
Response:
{
  "transactionId": "txn_63f8a9d2b7c1a",
  "status": "QUEUED"
}

---

## Tests

Run PHPUnit:
php bin/phpunit

---

## Optional: Docker Compose Setup

You can run MySQL and Redis with Docker Compose:

version: '3.8'
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: paysera
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  db_data:

Start services:
docker-compose up -d

---

## Time Spent
Approx. ~X hours

---

## AI Tools Used
- GitHub Copilot
- ChatGPT (Microsoft Copilot)
- Prompts: “setup redis in Symfony”, “create account API”, “write PHPUnit tests”, etc.

---

## Notes
- This is a demo assignment, not a full payment system.
- Improvements I’d add in production:
  - Symfony Messenger for retries/dead-letter queues
  - JWT authentication
  - OpenAPI/Swagger documentation
  - Docker Compose for full stack setup
