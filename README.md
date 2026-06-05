# Closet Swap — Backend API

A Laravel REST API for **Closet Swap**, a clothing swap and donation platform built for the trans community. Users can list items from their wardrobe, browse and search listings, express interest, and arrange exchanges.

The companion React frontend lives at [`closetswap-FE`](https://github.com/daydevelops/closetswap-FE).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Database | MySQL 8 |
| File Storage | AWS S3 |
| Queue / Cache | Redis |
| Local Infrastructure | Docker + Docker Compose |

---

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- No local PHP or MySQL installation required — everything runs in Docker

---

## Local Setup

**1. Clone the repo and copy the environment file:**

```bash
git clone https://github.com/daydevelops/closet-swap-2.git clozit
cd clozit
cp .env.example .env
```

**2. Start Docker:**

```bash
docker-compose up -d
```

This spins up four containers:

| Container | Role | Port |
|---|---|---|
| `web` | Nginx | `8080` |
| `app` | PHP-FPM | 9000 (internal only) |
| `db` | MySQL 8 | 3306 (internal only) |
| `redis` | Redis | 6379 (internal only) |


**3. Install dependencies and run migrations:**

```bash
docker exec clozit_app composer install
docker exec clozit_app php artisan key:generate
docker exec clozit_app php artisan migrate
```


**Test account credentials:**
- Email: `mona@test.com`
- Password: `password`

The API is now available at `http://localhost:8080/api`.

---

## Environment Variables

Key variables to configure in `.env`:

```env
APP_URL=http://localhost:8080

DB_HOST=db
DB_DATABASE=clozit
DB_USERNAME=clozit
DB_PASSWORD=secret

REDIS_HOST=redis

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

FILESYSTEM_DISK=s3
```

---

## Running Tests

```bash
docker exec clozit_app php artisan test
```

Tests use `DatabaseTransactions` — each test runs inside a transaction that is rolled back, so the database is not modified between tests.

---

---

## Branching Strategy

| Branch | Purpose |
|---|---|
| `main` | Stable, production-ready |
| `staging` | Pre-production integration |
| `dev` | Active development (PRs merge here) |

Feature branches are named after their Jira ticket (e.g. `KAN-5`).
