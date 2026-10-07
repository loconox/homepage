
IMAGE ?= homepage:latest

dev:
	php -S 127.0.0.1:8000 -t public/

# Build the self-contained Docker image (runs on the server over SSH from CI).
build:
	docker build -t $(IMAGE) .

# (Re)start the container from the freshly built image.
# Reads APP_LOCAL_PORT (and other vars) from .env.local if present.
deploy:
	bash -c 'set -a; [ -f .env.local ] && . .env.local; set +a; IMAGE=$(IMAGE) docker compose up -d --remove-orphans'

# XDEBUG_MODE=off keeps the suite from stalling when a step-debug listener
# (e.g. the IDE) is active, and makes it run noticeably faster.
test:
	XDEBUG_MODE=off php bin/phpunit

clean:
	rm -rf public/assets/
	rm -rf dist/

assets-prod:
	php bin/console tailwind:build --minify --env=prod && php bin/console asset-map:compile --env=prod

prod: clean assets-prod
	php bin/console app:build-static --env=prod

# FrankenPHP deployment: assets + pre-rendered pages dumped straight into public/,
# alongside index.php which serves the live /mcp endpoint.
prod-server: clean assets-prod
	php bin/console app:build-static --output=public --env=prod
