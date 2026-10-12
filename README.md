# Task Monitor (workshop base)

A small Laravel 13 task register for the workshop **Bengkel Vibe Coding: From Idea to Innovation**. Fake data only.

## Requirements
PHP 8.3 or newer, Composer 2, Git. No Node or database server is needed (SQLite is a file).

## Set up (about 5 minutes)

Copy this folder to your laptop, open a terminal inside it, then:

**Windows PowerShell**
```
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite
php artisan migrate --seed
php artisan test
php artisan serve
```

**macOS / Linux**
```
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan test
php artisan serve
```

Open http://127.0.0.1:8000 and sign in with a training account:

| Email | Password |
|---|---|
| amina@example.test | password |
| daniel@example.test | password |

You are ready when `php artisan test` shows all tests passing and you can sign in. Each user sees only their own tasks.

## Before you start work
1. Create your branch: `git checkout -b my-work`
2. Read `AGENTS.md`. It is the instruction file that AI agents read in this project.
3. Never put real data or a real API key in this project. A key belongs in `.env` only.

## Where things are
- `app/Http/Controllers/TaskController.php`: the task list and the add-task form
- `app/Models/Task.php`, `app/Enums/`: the task and its allowed values
- `database/seeders/DatabaseSeeder.php`: the two training users and their tasks
- `tests/Feature/`: the tests
