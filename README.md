# GestorServidores

A Symfony-based web application for multitenant server management that allows clients to manage their contracted resources include web and API access.

## Description

LoginWeb is a web application built with Symfony 7.4 that provides:
- User authentication with JWT tokens
- Server management interface via EasyAdmin
- RESTful API documentation with Swagger
- Role-based access control (RBAC)

## Requirements

- PHP 8.3+
- Composer
- MySQL/MariaDB database
- Docker (optional, for containerized setup)

## Installation

### Without Docker

1. Clone the repository:
```bash
git clone <repository-url>
cd loginWeb/login
```

2. Install dependencies:
```bash
composer install
```

3. Configure environment:
```bash
cp .env .env.local
# Edit .env.local with your database credentials
```

4. Create database:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Start the server:
```bash
symfony server:start
# or
php -S 127.0.0.1:8000 -t public/
```

### With Docker

```bash
cd loginWeb/login
docker-compose up -d
```

## Usage

### Running the Application

- Development: `symfony server:start`
- Production: Configure web server (nginx/Apache) to point to `public/`

### Accessing the Application

- Main application: `http://127.0.0.1:8000`
- API Documentation: `http://127.0.0.1:8000/api/doc`

### Logging In

1. Navigate to `/login`
2. Enter credentials
3. JWT token will be returned for API authentication

