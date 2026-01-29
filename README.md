🚀 Features

REST API for translations with:

Key prefix search

Locale filtering

Tag-based filtering

Combined filters

Cursor-based pagination for fast large-dataset access

CSV & JSON export (streamed, memory-safe)

Optimized SQL queries with proper indexing

Response caching for frequently accessed queries

Fully tested with ~95% code coverage

SQLite-based test environment (safe & fast)

🛠 Tech Stack

Laravel 11

PHP 8.2

MySQL (production)

SQLite (in-memory) for testing

PHPUnit for testing

📦 Setup Instructions

1️⃣ Clone the repository

git clone 
cd translation_service

2️⃣ Install dependencies
composer install

3️⃣ Environment setup

Copy the environment file:

cp .env.example .env


Update database credentials in .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=translation_service
DB_USERNAME=root
DB_PASSWORD=


Generate app key:

php artisan key:generate

4️⃣ Run migrations & seeders
php artisan migrate
php artisan db:seed
php artisan translations:seed


Seeders generate sample locales, tags, and translations.

5️⃣ Start the application
php artisan serve


API will be available at:

http://localhost:8000/api

🔑 Authentication

All API endpoints are protected.

Use Laravel Sanctum token authentication.

Example:

Authorization: Bearer <token>

📡 API Endpoints
Get Translations
GET /api/translations


Query parameters:

q – key prefix search

locale – locale code (e.g. en)

tags – comma-separated tag names

per_page – items per page (cursor pagination)

Export Translations
GET /api/translations/export


Query parameters:

format – csv or json

q, locale, tags – same as index endpoint

CSV exports are streamed to keep memory usage low.

🧪 Testing
Run the full test suite
php artisan test

Code coverage (requires Xdebug or PCOV)
php artisan test --coverage


Or generate an HTML report:

php artisan test --coverage-html=coverage
open coverage/index.html


Tests run against an in-memory SQLite database, ensuring the real database is never modified.

⚙️ Performance & Design Choices
Cursor Pagination

Used instead of offset pagination for better performance on large datasets

Reduces query cost and avoids slow offsets

Query Optimization

Explicit JOINs instead of heavy Eloquent relationships in read paths

Proper composite indexes added for:

(key, locale_id)

tag pivot lookups

Avoids N+1 queries

Streaming CSV Export

Uses StreamedResponse + chunkById

Safe for large exports without memory spikes

Caching

Cached translation list responses using request-based cache keys

Short TTL to balance freshness and performance

Testing Strategy

Feature tests cover:

Authentication

Filtering logic

Pagination

Export formats

SQLite enforces stricter constraints, catching edge cases early
