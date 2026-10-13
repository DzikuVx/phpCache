# phpCache

## Running PHP, Composer and tests

PHP and Composer are not installed on the host. Always run them, including the test suite, inside Docker containers. Never try to install or call `php`, `composer` or `vendor/bin/phpunit` directly on the host.

- Use the `app` service from [docker-compose.yml](docker-compose.yml). It is built from [Dockerfile](Dockerfile) (PHP 8.2 with memcached and mbstring) and starts the `redis` and `memcached` services that the tests need.
- Composer: `docker compose run --rm app composer <command>`
- Tests: `docker compose run --rm app vendor/bin/phpunit`
- Any other PHP command: `docker compose run --rm app php <args>`

Notes:
- `vendor/` is created by the container and is owned by root. Run `composer install` and `composer update` through the `app` service so they can write to it.
- Do not use a standalone `composer` or `php` image for installs. It lacks the memcached extension and has no Redis or Memcached services.
- After any dependency change, run the full test suite in the container before reporting the work as done.
