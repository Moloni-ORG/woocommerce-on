# WordPress Plugin – Docker, Build & Run Instructions

This document explains how to install dependencies, compile assets, and run the WordPress plugin using Docker.

---

## Prerequisites

Make sure you have the following installed on your system (native or WSL):

* PHP & Composer
* Node.js & npm
* Docker & Docker Compose

---

## 1. Install PHP Dependencies

From the **root of the project**, run:

```bash
composer install
```

This will install all required PHP dependencies for the plugin.

---

## 2. Install Frontend Dependencies

Navigate to the `.dev` folder:

```bash
cd .dev
```

Then install the Node.js dependencies:

```bash
npm install
```

---

## 3. Compile Assets (CSS & JavaScript)

Still inside the `.dev` folder, run:

```bash
npm run build-prod
```

⚠️ **Important:**

* This command must be run **every time you make changes to CSS or JavaScript files**.
* The compiled assets are required for the plugin to work correctly.

---

## 4. Start the Store Using Docker

From the **root of the project**, run:

```bash
docker compose up -d
```

This starts MariaDB and WordPress (official images) in the background. The project directory is
bind-mounted into the container at `wp-content/plugins/moloni-on`, so changes you make locally are
reflected inside the store.

On the first run a one-shot `setup` container installs WordPress and WooCommerce and activates the
plugin (store currency EUR, country Portugal). It exits when done and is skipped on later runs. Follow it
with `docker compose logs -f setup`.

To start again from a clean store, remove the volumes: `docker compose down -v`.

---

## 5. Access the Website

Once Docker is running, open your browser and navigate to:

```
http://localhost:8080/wp-admin
```

⏳ **First startup notice:**

* The first time you run Docker, it may take a few minutes.
* During this time, the store and database are being configured.

Log in with `admin` / `123456789`.

---

## Summary

1. `composer install` (project root)
2. `npm install` (inside `.dev`)
3. `npm run build-prod` (inside `.dev`, required after JS/CSS changes)
4. `docker compose up -d` (project root)
5. Open `http://localhost:8080/wp-admin`

---

You're now ready to develop and run the WordPress plugin locally using Docker 🚀
