# Agri Storage: Cloud Deployment Guide

This guide provides step-by-step instructions for deploying **Agri Storage** to cloud platforms including **Vercel**, **Railway**, and **Render**.

---

## Important Notice: PHP & MySQL in the Cloud

**Agri Storage** is built with **PHP 8** and **MySQL (PDO)**.

* **Vercel** is a serverless platform designed for stateless compute. It runs PHP using the `@vercel/php` community serverless runtime, but **Vercel does NOT host MySQL databases**.
* Therefore, to deploy to Vercel, you need:
  1. A **Free Cloud MySQL Database** (takes 2 minutes to create on Aiven, Railway, or Clever Cloud).
  2. The code deployed on Vercel with environment variables pointing to your cloud MySQL.

If you want **1-click deployment where both PHP and MySQL are hosted together**, we recommend **Railway.app** (see Option B below).

---

## Option A: Deploying on Vercel (Step-by-Step)

### Step 1: Create a Free Cloud MySQL Database
Choose any free cloud MySQL provider:

#### Recommended: Aiven for MySQL (Free Tier)
1. Go to [https://aiven.io/](https://aiven.io/) and create a free account.
2. Click **Create Service** &rarr; Select **MySQL** &rarr; Choose the **Free Plan**.
3. Once active, note the connection details:
   - **Host** (e.g. `mysql-xxxx.aivencloud.com`)
   - **Port** (e.g. `12345`)
   - **Database Name** (`defaultdb` or `agristorage`)
   - **User** (`avnadmin`)
   - **Password** (shown in Aiven console)

#### Alternative: Railway.app (Free / Trial)
1. Go to [https://railway.app/](https://railway.app/).
2. Click **New Project** &rarr; **Provision MySQL**.
3. Under the **Connect** tab, copy the host, port, user, password, and database name.

---

### Step 2: Import `database.sql` into Your Cloud Database
You can import your database using any of these methods:

#### Using Command Line:
```bash
mysql -h <YOUR_DB_HOST> -P <YOUR_DB_PORT> -u <YOUR_DB_USER> -p <YOUR_DB_NAME> < database.sql
```

#### Using DBeaver / TablePlus / MySQL Workbench:
1. Create a new connection using the cloud host, port, username, and password.
2. Open `database.sql` and click **Execute / Run Script**.
3. Verify that 3 tables (`users`, `cold_storages`, `bookings`) are populated.

---

### Step 3: Deploy to Vercel

#### Method 1: Using GitHub + Vercel Dashboard (Recommended)
1. Push your project to a GitHub repository:
   ```bash
   git init
   git add .
   git commit -m "Agri Storage release"
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/agristorage.git
   git push -u origin main
   ```
2. Go to [https://vercel.com/new](https://vercel.com/new) and import your GitHub repository.
3. In the **Environment Variables** section, add the following variables:

| Variable Name | Example Value | Description |
|---|---|---|
| `BASE_URL` | `/` | Sets root web path |
| `DB_HOST` | `mysql-xxxx.aivencloud.com` | Your cloud MySQL host |
| `DB_PORT` | `3306` (or Aiven port) | MySQL port |
| `DB_NAME` | `agristorage` (or `defaultdb`) | Database name |
| `DB_USER` | `avnadmin` | MySQL username |
| `DB_PASS` | `your_secret_password` | MySQL password |

4. Click **Deploy**.
5. Vercel will automatically read [`vercel.json`](file:///c:/Users/CHINU/.gemini/antigravity-ide/scratch/coldconnect/vercel.json), install the PHP serverless runtime, and publish your site at `https://your-project.vercel.app`!

#### Method 2: Using Vercel CLI
```bash
# Install Vercel CLI
npm install -g vercel

# Login to Vercel
vercel login

# Deploy
vercel --prod
```
During the CLI prompt, configure the environment variables when prompted or add them via the Vercel web dashboard.

---

## Option B: 1-Click Deployment on Railway.app (Easiest for PHP + MySQL)

Railway natively supports both PHP and MySQL in the same project with zero configuration:

1. Push your code to GitHub.
2. Open [https://railway.app/new](https://railway.app/new) and choose **Deploy from GitHub repo**.
3. In the project canvas, click **+ New** &rarr; **Database** &rarr; **Add MySQL**.
4. Railway automatically provides variables: `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`.
   * **Agri Storage's `includes/db.php` is already pre-configured to detect these exact Railway variables automatically!**
5. Connect to the Railway MySQL service and import `database.sql`.
6. Click **Generate Domain** in Railway under your web service settings.
7. Your app is live with full database connectivity!

---

## Option C: Free Traditional PHP Hosting (InfinityFree / 000webhost)

If you prefer classic cPanel hosting with built-in phpMyAdmin:
1. Register at [https://www.infinityfree.com/](https://www.infinityfree.com/).
2. Create an account and open the **cPanel**.
3. Go to **MySQL Databases** and create `agristorage`.
4. Open **phpMyAdmin**, click `Import`, and upload `database.sql`.
5. Open **File Manager** &rarr; navigate to `htdocs/` &rarr; upload all project files.
6. Edit `includes/db.php` or create a `.env` with the cPanel database credentials.

---

## Local Development (XAMPP Verification)

Your project remains 100% functional locally with zero breaking changes:
1. Open XAMPP Control Panel & ensure **Apache** and **MySQL** are running.
2. Access the app at:
   👉 **[http://localhost/coldconnect/](http://localhost/coldconnect/)** (or `http://localhost/agristorage/` if you rename the folder).
3. Demo login accounts:
   - **Farmer:** `demo@coldconnect.test` / `demo123`
   - **Storage Owner:** `owner@coldconnect.test` / `owner123`
