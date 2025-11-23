# Deploy PostgreSQL Bridge

## Option 1: Heroku (Free)
1. Create account at heroku.com
2. Create new app: `postgres-bridge-yourname`
3. Upload `simple_bridge.php` as `index.php`
4. Update FinanceController with your Heroku URL

## Option 2: Railway (Free)
1. Go to railway.app
2. Deploy from GitHub or upload files
3. Use your Railway URL in FinanceController

## Option 3: Vercel (Free)
1. Go to vercel.com
2. Create new project
3. Upload `simple_bridge.php`
4. Use Vercel URL in FinanceController

## Update Controller
Replace this line in FinanceController.php:
```php
$herokuUrl = 'https://your-app-name.herokuapp.com/bridge.php';
```

## Test Bridge
Visit your deployed URL with: `?action=tables`
Should return JSON with PostgreSQL table names.