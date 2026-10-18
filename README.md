# Portal Atlas

Unofficial, community-made interactive map and raid companion for Active Matter.

> **Status:** Inactive — not in active development. Kept for reference and to run locally.

## Overview

### What is Portal Atlas?

Portal Atlas is a web application for exploring Active Matter's maps. Players can find extraction points, loot and objectives, look up items and whether to keep them, and plan raid routes. Every data point carries its source, a confidence score and the game version it was verified for, and anyone can report an entry that is wrong.

Portal Atlas is not produced, approved or endorsed by Gaijin Entertainment or Matter Team. See [docs/map-data-strategy.md](docs/map-data-strategy.md) for why the product is not named after the game.

### Key Features

- **Interactive Maps**: Find extraction points, loot and objectives on Active Matter's maps.
- **Item Lookup**: Look up items and whether they're worth keeping.
- **Raid Planning**: Plan raid routes.
- **Sourced Data**: Every data point carries its source, a confidence score and the game version it was verified for, and anyone can report an entry that's wrong.

## Getting Started

### Prerequisites

Ensure you have the following installed. You can verify each one by running the provided command in your terminal.

1. **PHP** (8.3+) is required for the application. Check it by running:

    ```bash
    php --version
    ```

2. **Composer** manages PHP dependencies. Check it by running:

    ```bash
    composer --version
    ```

3. **Node** and **NPM** manage frontend dependencies. Check them by running:

    ```bash
    node --version
    npm --version
    ```

### Installation

1. Duplicate the example environment file and configure it with your settings:

    ```bash
    cp .env.example .env
    ```

2. Install PHP and JavaScript dependencies:

    ```bash
    composer install
    npm install
    ```

3. Generate a new PHP application key:

    ```bash
    php artisan key:generate
    ```

4. Create the SQLite database file:

    ```bash
    touch database/database.sqlite
    ```

5. Apply database migrations and seed the map dataset, along with a local admin account (`admin@test.com` / `password`):

    ```bash
    php artisan migrate --seed
    ```

6. Link the public storage directory:

    ```bash
    php artisan storage:link
    ```

7. Start the development environment:

    ```bash
    composer dev
    ```

    Alternatively, run the backend and frontend separately:

    ```bash
    php artisan serve
    npm run dev
    ```

## Deployment

The app runs on Laravel Cloud with PostgreSQL. After deploying, seed the dataset and promote your account to admin:

```bash
php artisan db:seed --force
php artisan app:make-admin you@example.com
```

Laravel Cloud's filesystem is reset on every deploy, so map images uploaded by admins can't live on local storage. Attach an object storage bucket to the environment and set `MEDIA_DISK` to the bucket's disk name. See [docs/deployment.md](docs/deployment.md) for the full checklist.

## Testing

Run the Pest test suite along with Pint formatting and PHPStan checks:

```bash
composer test
```

## Data Licensing

Item names, categories and rarities partly come from [activematter.wiki.gg](https://activematter.wiki.gg) under **CC BY-SA 4.0**, and each record links to its source page. Most map positions, loot pools and drop chances are imported from [activematterhelp.ru](https://activematterhelp.ru/maps) (© Active Matter Help). No extracted game images are hosted. See [docs/data-import.md](docs/data-import.md) for details.

## Contributing

This project isn't in active development, so I'm not reviewing issues or pull requests. You're welcome to fork it and take it in your own direction under the license below.

## License

The code is released under the [MIT License](LICENSE). The map and item data come from the sources listed under [Data Licensing](#data-licensing) and keep their own licenses.
