# BRIO-IMMO

Application Laravel de gestion locative pour les bailleurs et les locataires.

## Lancer le projet en local

### Prérequis

- PHP 8.3 ou supérieur, avec l'extension `pdo_mysql`
- Composer
- MySQL
- Node.js et npm

Vérifiez que le pilote MySQL de PHP est activé :

```bash
php -m | grep pdo_mysql
```

La commande doit afficher `pdo_mysql`.

### 1. Démarrer MySQL et créer la base

Démarrez votre serveur MySQL (par exemple via XAMPP), puis créez la base :

```sql
CREATE DATABASE brio_immo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
```

### 2. Configurer l'environnement

Depuis la racine du projet, copiez le fichier d'exemple :

```bash
cp .env.example .env
```

Dans `.env`, vérifiez les paramètres de l'application et adaptez les identifiants
MySQL à votre installation :

```env
APP_NAME=BRIO-IMMO
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brio_immo
DB_USERNAME=root
DB_PASSWORD=
```

Remplacez `DB_USERNAME` et `DB_PASSWORD` par les identifiants de votre serveur
MySQL si nécessaire. Ne partagez pas le fichier `.env` : il contient des
paramètres locaux et potentiellement des secrets.

### 3. Installer les dépendances

```bash
composer install
npm install
```

### 4. Générer la clé Laravel et créer les tables

```bash
php artisan key:generate
php artisan migrate
```

La structure de la base est définie par les migrations PHP dans
[`database/migrations`](database/migrations) ; aucun fichier `.sql` n'est
nécessaire pour initialiser la base.

### 5. Démarrer l'application

Ouvrez deux terminaux dans le dossier du projet et laissez les deux commandes
actives.

Terminal 1 — compiler les ressources frontend :

```bash
npm run dev
```

Terminal 2 — démarrer Laravel :

```bash
php artisan serve
```

Ouvrez ensuite [http://127.0.0.1:8000](http://127.0.0.1:8000). Vous pouvez créer
un compte bailleur depuis la page d'inscription.

### Dépannage

- `could not find driver` : activez ou installez l'extension PHP `pdo_mysql`.
- `Unknown database 'brio_immo'` : vérifiez que MySQL est démarré, que la base a
  été créée et que les paramètres `DB_*` de `.env` sont corrects.
- `Vite manifest not found` : gardez `npm run dev` actif ou exécutez
  `npm run build`.
