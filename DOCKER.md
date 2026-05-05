# Docker Setup

This project now includes a Docker stack with:

- Laravel app on `http://localhost:8011`
- phpMyAdmin on `http://localhost:8012`
- MySQL on host port `33061`

## Services

- App container: `pos-hadmie-app`
- Database container: `pos-hadmie-db`
- phpMyAdmin container: `pos-hadmie-phpmyadmin`

## Default database credentials

- Database: `ultimate_pos`
- Username: `ultimate_pos`
- Password: `ultimate_pos`
- Root password: `root`

## Start

```bash
docker compose up -d --build
```

Or with `make`:

```bash
make up
```

On first boot, the app container will:

- copy `.env.docker` to `.env` if `.env` does not exist
- run `composer install` if `vendor/` is missing
- create the Laravel storage symlink if needed

## Stop

```bash
docker compose down
```

Or:

```bash
make down
```

To also remove the MySQL volume:

```bash
docker compose down -v
```

This also removes the Docker-managed `vendor/` volume, so the next boot will reinstall PHP dependencies.

## Make Commands

```bash
make help
make build
make up
make down
make restart
make ps
make logs
make shell
make root-shell
make artisan c=about
make composer c=install
make migrate
make fresh
make seed
make mysql
make test-user
```

Default test user command:

```bash
make test-user
```

Custom example:

```bash
make test-user TEST_BUSINESS_ID=2 TEST_USERNAME=testadmin TEST_PASSWORD=test123456
```
