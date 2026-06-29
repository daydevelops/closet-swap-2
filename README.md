# Closet Swap — Backend API

Closet Swap is a clothing swap and donation platform built for the trans community. Users can list items from their wardrobe, browse and search listings, express interest, and arrange exchanges with others in their area.

This repo is the **Laravel 12 REST API**. The companion React frontend lives at [`clozit-app`](https://github.com/daydevelops/clozit-app).

**Live:** [`https://api.closetswap.org`](https://api.closetswap.org)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Database | MySQL 8 (RDS in staging, Docker locally) |
| File Storage | AWS S3 |
| Queue / Cache | Redis |
| Email | AWS SES |
| Local Dev | Docker + Docker Compose |
| Hosting | AWS EC2 + RDS + CloudFront |

---

## Quick Start

```bash
git clone https://github.com/daydevelops/closet-swap-2.git clozit
cd clozit
cp .env.example .env
docker compose up -d
```

The API will be available at `http://localhost:8080/api`. Migrations and seed data run automatically.

