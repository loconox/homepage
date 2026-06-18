# jeremielibeau.fr

Page d'accueil personnelle / CV en ligne — propulsée par **Symfony 7** + **Tailwind CSS 4** (via Symfonycasts Tailwind Bundle et AssetMapper), pré-rendue en HTML statique pour le déploiement.

## Structure

```
.
├── assets/                  # Sources JS + CSS (Tailwind input)
│   ├── app.js
│   ├── email-obfuscation.js
│   └── styles/app.css
├── bin/console
├── config/
│   ├── content.yaml         # Contenu du site (profil, services, expériences…)
│   ├── packages/
│   └── services.yaml
├── public/                  # Assets statiques (favicons, images) + index.php
├── src/
│   ├── Command/BuildStaticCommand.php
│   ├── Content/ContentProvider.php
│   └── Controller/HomeController.php
├── templates/
│   ├── base.html.twig
│   ├── index.html.twig
│   ├── _partials/header.html.twig
│   └── sections/
│       ├── hero.html.twig
│       ├── quote.html.twig
│       ├── services.html.twig
│       ├── experience.html.twig
│       ├── skills.html.twig
│       ├── education.html.twig
│       └── contact.html.twig
└── dist/                    # Sortie du build statique (générée)
```

## Pré-requis

- PHP 8.3+
- Composer 2
- [Symfony CLI](https://symfony.com/download) (optionnel mais pratique)

## Installation

```sh
composer install
```

Le binaire Tailwind est téléchargé automatiquement au premier `tailwind:build`.

## Développement

```sh
# Lancer le serveur Symfony
symfony serve

# Dans un autre terminal, watcher Tailwind
php bin/console tailwind:build --watch
```

Ouvrir [http://localhost:8000](http://localhost:8000).

## Édition du contenu

Tout le contenu éditorial (profil, services, expériences, compétences, formation) est centralisé dans `config/content.yaml`. Aucune base de données.

> ⚠️ Les caractères `%` dans les valeurs YAML doivent être doublés (`%%`) car Symfony les interprète comme des références de paramètres.

## Build statique pour la production

```sh
composer install --no-dev --optimize-autoloader
php bin/console tailwind:build --minify --env=prod
php bin/console asset-map:compile --env=prod
php bin/console app:build-static --env=prod
```

Le dossier `dist/` contient alors :
- `index.html` pré-rendu
- `assets/` (CSS + JS hashés)
- `favicon.*`, `images/`

Servable directement par n'importe quel hébergeur statique.

## Déploiement

GitHub Actions (`.github/workflows/deploy.yml`) construit et envoie `dist/` via SCP à chaque push sur `main`.

Secrets requis : `SSH_PRIVATE_KEY`, `SSH_HOST`, `SSH_USER`, `SSH_TARGET_PATH`.
