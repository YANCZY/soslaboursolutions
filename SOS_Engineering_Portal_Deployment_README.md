# SOS Engineering Portal — UAT Deployment README

This README documents how the UAT instance was deployed from local Git Bash into the hosting server through SSH.

Production deployment is intentionally not included yet. It will be added later once the Production instance is ready.

---

## 1. UAT Instance Details

### UAT URL

```text
https://uat.portal.sosengineeringsolutions.com.au/
```

### Environment

```text
UAT
```

### Git Branch

```bash
uat
```

### Application Stack

The UAT instance is a Laravel + Vue.js application.

The project uses:

- Laravel backend
- Vue.js frontend
- PostgreSQL database
- Composer for PHP dependencies
- pnpm for frontend dependencies
- GitHub for source control
- Crazy Domains / cPanel hosting
- SSH access from local machine to hosting server

---

## 2. Deployment Flow Summary

The deployment flow is:

```text
Local Git Bash
→ SSH into hosting server
→ Go to the exact UAT folder
→ Clone the GitHub repository
→ Checkout/use the uat branch
→ Create and configure .env
→ Install backend dependencies
→ Install frontend dependencies
→ Build frontend assets
→ Run Laravel migrations
→ Clear Laravel cache
→ Test the UAT URL
```

---

## 3. Access Server from Local Bash to SSH Bash

Open **Git Bash** on your local computer.

Then connect to the server using SSH.

### SSH Command Format

```bash
ssh CPANEL_USERNAME@SERVER_HOST
```

If the server uses a custom SSH port:

```bash
ssh -p PORT_NUMBER CPANEL_USERNAME@SERVER_HOST
```

Example format:

```bash
ssh yourcpanelusername@server-ip-or-hostname
```

or:

```bash
ssh -p 22 yourcpanelusername@server-ip-or-hostname
```

Replace the following:

```text
CPANEL_USERNAME = your cPanel / hosting username
SERVER_HOST     = server IP address or server hostname
PORT_NUMBER     = SSH port provided by Crazy Domains / hosting provider
```

The common SSH port is:

```text
22
```

---

## 4. First Time SSH Access

The first time you connect, Git Bash may show this message:

```text
Are you sure you want to continue connecting (yes/no/[fingerprint])?
```

Type:

```bash
yes
```

Then press Enter.

After that, enter the SSH password.

Important:

When typing the password, Git Bash may not display any characters. This is normal.

After successful login, your terminal should change from your local machine to the server shell.

Example before SSH:

```bash
Yancy@DESKTOP MINGW64 ~
```

Example after SSH:

```bash
[cpanelusername@server ~]$
```

This means you are now inside the server SSH bash.

---

## 5. Go to the Exact UAT Folder

After logging in through SSH, go to the UAT folder.

For this UAT instance, the folder should be the subdomain folder:

```bash
cd ~/uat.portal.sosengineeringsolutions.com.au
```

If the folder is inside `public_html`, use:

```bash
cd ~/public_html/uat.portal.sosengineeringsolutions.com.au
```

Check your current location:

```bash
pwd
```

List the files/folders:

```bash
ls -la
```

The folder should be empty before cloning, or it should only contain files you are ready to replace.

If the folder has old test files and you are sure they are not needed, remove them carefully.

Example:

```bash
rm -rf *
```

Important:

Only run `rm -rf *` when you are already inside the correct UAT folder.

Always confirm first with:

```bash
pwd
```

---

## 6. Clone the UAT Branch from GitHub

Inside the UAT folder, clone the repository and use the `uat` branch.

Command format:

```bash
git clone -b uat REPOSITORY_URL .
```

Example:

```bash
git clone -b uat https://github.com/USERNAME/REPOSITORY_NAME.git .
```

Important:

The `.` at the end means the repository will be cloned directly into the current folder.

After cloning, confirm that the Laravel project files exist:

```bash
ls -la
```

You should see files/folders such as:

```text
artisan
app
bootstrap
composer.json
package.json
public
resources
routes
storage
```

Confirm that the branch is `uat`:

```bash
git branch
```

Expected result:

```bash
* uat
```

---

## 7. Create the Laravel `.env` File

Laravel requires an `.env` file.

After cloning, create `.env` from `.env.example`:

```bash
cp .env.example .env
```

Then edit the `.env` file using cPanel File Manager or terminal editor if available.

Example:

```bash
nano .env
```

If `nano` is not available, edit it through cPanel File Manager.

---

## 8. UAT `.env` Setup

Set the UAT application URL:

```env
APP_NAME="SOS Engineering Portal"
APP_ENV=uat
APP_DEBUG=false
APP_URL=https://uat.portal.sosengineeringsolutions.com.au
```

Set the PostgreSQL database details:

```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=YOUR_UAT_DATABASE_NAME
DB_USERNAME=YOUR_UAT_DATABASE_USERNAME
DB_PASSWORD=YOUR_UAT_DATABASE_PASSWORD
```

Notes:

- `APP_DEBUG=false` is recommended for UAT because it behaves closer to production.
- `APP_URL` must match the UAT URL.
- `DB_HOST` is usually `localhost` for Crazy Domains / cPanel PostgreSQL.
- Do not commit `.env` to GitHub.

---

## 9. Generate Laravel App Key

Run:

```bash
php artisan key:generate
```

This will populate the `APP_KEY` value inside `.env`.

If the default PHP command uses the wrong PHP version, try:

```bash
php8.4 artisan key:generate
```

or:

```bash
ea-php84 artisan key:generate
```

---

## 10. Install PHP / Laravel Dependencies

Run:

```bash
composer install
```

For optimized install without development packages:

```bash
composer install --no-dev --optimize-autoloader
```

For UAT, either can work depending on whether dev packages are needed.

Recommended for UAT:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 11. Install Frontend Dependencies

Run:

```bash
pnpm install
```

If `pnpm` is not available, try:

```bash
corepack enable
corepack prepare pnpm@latest --activate
pnpm install
```

If `corepack` is not available either, pnpm/Node may need to be enabled in cPanel or installed in the hosting environment.

---

## 12. Build Frontend Assets

Run:

```bash
pnpm build
```

This generates the compiled frontend assets used by Laravel/Vue.

---

## 13. Run Database Migrations

Run:

```bash
php artisan migrate
```

If the application needs seed data, run:

```bash
php artisan db:seed
```

Or run a specific seeder:

```bash
php artisan db:seed --class=SeederClassName
```

Important:

Do not run this on UAT unless you intentionally want to wipe database data:

```bash
php artisan migrate:fresh
```

---

## 14. Clear Laravel Cache

After setup, clear Laravel cache:

```bash
php artisan optimize:clear
```

Or manually:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Then cache config/routes/views for deployment:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If route caching causes an error, clear the route cache:

```bash
php artisan route:clear
```

---

## 15. File Permissions

If Laravel has permission issues, especially with logs/cache, run:

```bash
chmod -R 775 storage bootstrap/cache
```

If ownership needs to be fixed, this usually needs hosting/server-level support.

---

## 16. Test the UAT Instance

Open:

```text
https://uat.portal.sosengineeringsolutions.com.au/
```

Test the following:

- Login page loads
- CSS and JS are loading correctly
- Database connection works
- Dashboard opens after login
- New routes/features work
- No Laravel error is shown

If there is an error, check the Laravel logs:

```bash
tail -n 100 storage/logs/laravel.log
```

To keep watching logs live:

```bash
tail -f storage/logs/laravel.log
```

---

## 17. Full Initial UAT Setup Command Flow

Use this as the main copy-paste reference.

From local Git Bash:

```bash
ssh CPANEL_USERNAME@SERVER_HOST
```

or:

```bash
ssh -p PORT_NUMBER CPANEL_USERNAME@SERVER_HOST
```

Then inside the server SSH bash:

```bash
cd ~/uat.portal.sosengineeringsolutions.com.au

pwd
ls -la

git clone -b uat REPOSITORY_URL .

cp .env.example .env

php artisan key:generate

composer install --no-dev --optimize-autoloader

pnpm install
pnpm build

php artisan migrate

php artisan optimize:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then test:

```text
https://uat.portal.sosengineeringsolutions.com.au/
```

---

## 18. Full Initial UAT Setup Command Flow if Folder is Inside `public_html`

From local Git Bash:

```bash
ssh CPANEL_USERNAME@SERVER_HOST
```

Then inside the server SSH bash:

```bash
cd ~/public_html/uat.portal.sosengineeringsolutions.com.au

pwd
ls -la

git clone -b uat REPOSITORY_URL .

cp .env.example .env

php artisan key:generate

composer install --no-dev --optimize-autoloader

pnpm install
pnpm build

php artisan migrate

php artisan optimize:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then test:

```text
https://uat.portal.sosengineeringsolutions.com.au/
```

---

## 19. Updating the UAT Instance After New Commits

When there is a new commit pushed to the `uat` branch, do not clone again.

Instead, SSH into the server and pull the latest changes.

From local Git Bash:

```bash
ssh CPANEL_USERNAME@SERVER_HOST
```

Then inside the server SSH bash:

```bash
cd ~/uat.portal.sosengineeringsolutions.com.au

git checkout uat
git pull origin uat

composer install --no-dev --optimize-autoloader

pnpm install
pnpm build

php artisan migrate

php artisan optimize:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If the folder is inside `public_html`:

```bash
cd ~/public_html/uat.portal.sosengineeringsolutions.com.au

git checkout uat
git pull origin uat

composer install --no-dev --optimize-autoloader

pnpm install
pnpm build

php artisan migrate

php artisan optimize:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then test:

```text
https://uat.portal.sosengineeringsolutions.com.au/
```

---

## 20. Important Notes

- Initial setup uses `git clone`.
- Future updates use `git pull`.
- Do not clone again if the project is already deployed.
- Always confirm the folder using `pwd` before deleting or updating files.
- Always confirm the branch using `git branch`.
- Never commit `.env` to GitHub.
- Do not run `migrate:fresh` on UAT unless you intentionally want to delete the database tables/data.
- Rebuild frontend assets after frontend changes using `pnpm build`.
- Run migrations after database schema changes.
- Check Laravel logs if the page shows an error.

---

## 21. Useful Commands

### Exit SSH

```bash
exit
```

### Check Current Folder

```bash
pwd
```

### List Files

```bash
ls -la
```

### Check Git Status

```bash
git status
```

### Check Git Branch

```bash
git branch
```

### Check PHP Version

```bash
php -v
```

### Check Composer Version

```bash
composer --version
```

### Check Node Version

```bash
node -v
```

### Check pnpm Version

```bash
pnpm -v
```

### Laravel Logs

```bash
tail -n 100 storage/logs/laravel.log
```
