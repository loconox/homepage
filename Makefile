
clean:
	rm -rf public/assets/
	rm -rf dist/

assets-prod:
	php bin/console tailwind:build --minify --env=prod && php bin/console asset-map:compile --env=prod

prod: clean assets-prod
	php bin/console app:build-static --env=prod
