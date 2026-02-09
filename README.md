# Paysera Fund Transfer API

A robust, scalable API for transferring funds between accounts, built with **Symfony 7.4**, **PHP 8.2**, **MySQL**, and **Redis**.

This project demonstrates modern PHP development practices with:
- ✅ JWT-based authentication
- ✅ Asynchronous processing with Redis queues
- ✅ Transaction integrity with pessimistic locking
- ✅ Comprehensive test coverage
- ✅ RESTful API design

---

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Running the Application](#-running-the-application)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Architecture](#-architecture)
- [Project Structure](#-project-structure)

---

## ✨ Features

- **Secure Authentication**: JWT-based authentication using `lexik/jwt-authentication-bundle`
- **Account Management**: Create and update user accounts with balance tracking
- **Fund Transfers**: Asynchronous fund transfers between accounts
- **Transaction Integrity**: Pessimistic locking to prevent race conditions
- **Ownership Validation**: Users can only transfer from their own accounts
- **Comprehensive Logging**: Detailed logging of all operations
- **Full Test Coverage**: Integration and unit tests with PHPUnit

---

## 🛠 Technology Stack

| Component | Technology |
|-----------|-----------|
| **Framework** | Symfony 7.4 |
| **Language** | PHP 8.2+ |
| **Database** | MySQL 8.0 / MariaDB |
| **Cache/Queue** | Redis 7.x |
| **Authentication** | JWT (JSON Web Tokens) |
| **Testing** | PHPUnit 11.x |
| **ORM** | Doctrine ORM |

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2 or higher** with extensions:
  - `pdo_mysql`
  - `redis`
  - `json`
  - `mbstring`
- **Composer** (latest version)
- **MySQL 8.0** or **MariaDB 10.5+**
- **Redis 7.x**
- **Symfony CLI** (optional, but recommended)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/pchandra191/paysera.git
cd paysera
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Generate JWT Keys

```bash
php bin/console lexik:jwt:generate-keypair
```

This will create:
- `config/jwt/private.pem`
- `config/jwt/public.pem`

---

## ⚙️ Configuration

### 1. Environment Variables

Copy `.env` to `.env.local` and configure:

```env
# Database Configuration
DATABASE_URL="mysql://root:password@127.0.0.1:3306/paysera?serverVersion=8.0"

# Redis Configuration
REDIS_URL="redis://127.0.0.1:6379"

# JWT Configuration (auto-generated)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

### 2. Create Databases

```bash
# Development database
php bin/console doctrine:database:create

# Test database
php bin/console doctrine:database:create --env=test
```

### 3. Run Migrations

```bash
# Development
php bin/console doctrine:migrations:migrate

# Test environment
php bin/console doctrine:migrations:migrate --env=test
```

---

## 🏃 Running the Application

### 1. Start MySQL

Ensure MySQL is running on port 3306.

### 2. Start Redis

```bash
# Linux/macOS
redis-server

# Windows (using Memurai or Redis for Windows)
redis-server.exe
```

Verify Redis is running:
```bash
redis-cli ping
# Expected output: PONG
```

### 3. Start Symfony Server

```bash
symfony server:start
```

Or using PHP built-in server:
```bash
php -S localhost:8000 -t public
```

The API will be available at: `http://localhost:8000`

### 4. Start Transfer Worker

In a separate terminal, start the background worker to process transfers:

```bash
php bin/console app:process-transfers
```

**Note**: In production, use a process manager like **Supervisor** to keep this worker running.

---

## 📚 API Documentation

### Base URL
```
http://localhost:8000
```

---

### 🔐 **1. User Registration**

Create a new user account.

**Endpoint:** `POST /api/register`  
**Authentication:** Not required

**Request:**
```json
{
  "email": "user@example.com",
  "password": "securepassword123"
}
```

**Response (201 Created):**
```json
{
  "message": "User registered successfully"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "alice@example.com",
    "password": "password123"
  }'
```

---

### 🔑 **2. Login (Get JWT Token)**

Authenticate and receive a JWT token.

**Endpoint:** `POST /api/login_check`  
**Authentication:** Not required

**Request:**
```json
{
  "username": "user@example.com",
  "password": "securepassword123"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "alice@example.com",
    "password": "password123"
  }'
```

**Save the token for subsequent requests:**
```bash
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGci..."
```

---

### 💰 **3. Create/Update Account (Upsert)**

Create a new account or update existing account balance.

**Endpoint:** `POST /api/account`  
**Authentication:** Required (Bearer Token)

**Request:**
```json
{
  "balance": 1000.00
}
```

**Response - First Call (201 Created):**
```json
{
  "accountId": 1,
  "owner": "alice@example.com",
  "balance": "1000.00",
  "status": "CREATED"
}
```

**Response - Subsequent Calls (200 OK):**
```json
{
  "accountId": 1,
  "owner": "alice@example.com",
  "balance": "2500.00",
  "status": "UPDATED"
}
```

**Important Notes:**
- Each user can only have **ONE** account
- Subsequent POST requests will **update** the existing account balance
- The new balance **replaces** the old balance (not added to it)

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/account \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"balance": 5000.00}'
```

---

### 💸 **4. Transfer Funds**

Transfer funds from your account to another account.

**Endpoint:** `POST /api/transfer`  
**Authentication:** Required (Bearer Token)

**Request:**
```json
{
  "fromAccountId": 1,
  "toAccountId": 2,
  "amount": 100.00
}
```

**Response (200 OK):**
```json
{
  "transactionId": "txn_63f8a2b4c1d5e",
  "status": "QUEUED"
}
```

**Important Notes:**
- You can only transfer from accounts you own
- Transfers are processed **asynchronously** by the worker
- The worker must be running to process transfers
- Pessimistic locking ensures transaction integrity

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/transfer \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "fromAccountId": 1,
    "toAccountId": 2,
    "amount": 250.00
  }'
```

---

### 📝 **Complete Usage Flow**

```bash
# 1. Register User A
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email": "alice@example.com", "password": "pass123"}'

# 2. Login as User A
TOKEN_A=$(curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "alice@example.com", "password": "pass123"}' \
  | jq -r '.token')

# 3. Create Account for User A
curl -X POST http://localhost:8000/api/account \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"balance": 5000.00}'

# 4. Register User B
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email": "bob@example.com", "password": "pass456"}'

# 5. Login as User B
TOKEN_B=$(curl -X POST http://localhost:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"username": "bob@example.com", "password": "pass456"}' \
  | jq -r '.token')

# 6. Create Account for User B
curl -X POST http://localhost:8000/api/account \
  -H "Authorization: Bearer $TOKEN_B" \
  -H "Content-Type: application/json" \
  -d '{"balance": 1000.00}'

# 7. Transfer from Alice (Account 1) to Bob (Account 2)
curl -X POST http://localhost:8000/api/transfer \
  -H "Authorization: Bearer $TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"fromAccountId": 1, "toAccountId": 2, "amount": 500.00}'
```

---

## 🧪 Testing

### Run All Tests

```bash
php bin/phpunit
```

### Run Specific Test Suite

```bash
# Account tests
php bin/phpunit tests/Controller/AccountControllerTest.php

# Transfer tests
php bin/phpunit tests/Controller/TransferControllerTest.php

# Authentication tests
php bin/phpunit tests/AuthenticationTest.php
```

### Test Coverage

- ✅ **9 tests, 23 assertions**
- User registration and login
- Account creation and update
- Fund transfers with ownership validation
- Error handling and validation

---

## 🏗 Architecture

### Asynchronous Processing

Transfers are not processed immediately during the HTTP request. Instead:

1. **API Request** → Validates and queues transfer to Redis
2. **Worker Process** → Continuously processes transfers from queue
3. **Database Transaction** → Updates balances with pessimistic locking

### Transaction Integrity

- **Optimistic Check**: Quick validation before queueing (insufficient funds, account existence)
- **Pessimistic Lock**: `SELECT ... FOR UPDATE` during processing to prevent race conditions
- **Database Transactions**: All balance updates wrapped in transactions with rollback on failure

### Security

- **JWT Authentication**: Stateless authentication using RS256 algorithm
- **Ownership Validation**: Users can only transfer from their own accounts
- **Input Validation**: All inputs validated before processing
- **Error Handling**: Comprehensive error handling with appropriate HTTP status codes

---

## 📁 Project Structure

```
paysera/
├── config/
│   ├── packages/
│   │   ├── security.yaml              # Security & JWT configuration
│   │   ├── lexik_jwt_authentication.yaml
│   │   └── monolog.yaml               # Logging configuration
│   ├── routes/
│   │   └── lexik_jwt_authentication.yaml
│   └── services.yaml                  # Service container configuration
├── migrations/                        # Database migrations
├── src/
│   ├── Command/
│   │   └── ProcessTransfersCommand.php  # Background worker
│   ├── Controller/
│   │   ├── AccountController.php        # Account endpoints
│   │   ├── RegistrationController.php   # User registration
│   │   └── TransferController.php       # Transfer endpoints
│   ├── Entity/
│   │   ├── Account.php                  # Account entity
│   │   ├── Transaction.php              # Transaction entity
│   │   └── User.php                     # User entity
│   ├── Repository/
│   │   ├── AccountRepository.php
│   │   ├── TransactionRepository.php
│   │   └── UserRepository.php
│   └── Service/
│       ├── RedisService.php             # Redis queue service
│       └── TransferService.php          # Transfer business logic
├── tests/
│   ├── Controller/
│   │   ├── AccountControllerTest.php
│   │   └── TransferControllerTest.php
│   └── AuthenticationTest.php
└── README.md
```

---

## 🐳 Optional: Docker Setup

Create a `docker-compose.yml`:

```yaml
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
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

Start services:
```bash
docker-compose up -d
```

---

## 📝 Future Improvements

- [ ] Implement retry mechanism with dead-letter queue
- [ ] Add idempotency key enforcement
- [ ] Implement transaction history endpoint
- [ ] Add OpenAPI/Swagger documentation
- [ ] Implement rate limiting
- [ ] Add balance inquiry endpoint
- [ ] Implement webhook notifications for completed transfers
- [ ] Add support for multiple currencies

---

## 📄 License

This project is created as a technical assignment demonstration.

---

## 👨‍💻 Author

**Pawan Chandra**  
GitHub: [@pchandra191](https://github.com/pchandra191)

---

## 🙏 Acknowledgments

- Symfony Framework
- Doctrine ORM
- LexikJWTAuthenticationBundle
- Redis
