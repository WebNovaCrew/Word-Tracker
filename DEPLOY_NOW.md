# 🚀 RAILWAY DEPLOYMENT - FINAL SETUP

## ✅ CONFIGURATION COMPLETE

Your project is now configured to deploy the PHP backend to Railway with the correct database port (36666).

---

## 📋 DEPLOYMENT STEPS

### 1. Set Environment Variables in Railway

Go to your Railway project → Variables tab and add:

```
MYSQLHOST=shuttle.proxy.rlwy.net
MYSQLPORT=36666
MYSQLUSER=root
MYSQLPASSWORD=WiGhctjnxmSBDWukfTiCLzvLGrXRmQdt
MYSQLDATABASE=railway
```

### 2. Deploy to Railway

```bash
git add .
git commit -m "Deploy PHP backend with correct database port 36666"
git push origin main
```

Railway will automatically:
- Detect PHP project
- Install dependencies
- Start PHP server: `php -S 0.0.0.0:$PORT -t backend-php`
- Serve all PHP files from `backend-php/` folder

### 3. Verify Deployment

After deployment, test these endpoints:

```bash
# Health check (if you have one)
curl https://word-tracker-production.up.railway.app/test_deployment.php

# Login endpoint
curl -X POST https://word-tracker-production.up.railway.app/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'

# Get plans
curl https://word-tracker-production.up.railway.app/get_plans.php?user_id=1
```

---

## 🔧 WHAT WAS CONFIGURED

### 1. railway.json
```json
{
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t backend-php"
  }
}
```

### 2. .railwayignore
Excludes frontend and Node.js files from deployment

### 3. Database Configuration
- Port: 36666 ✅
- Host: shuttle.proxy.rlwy.net ✅
- All credentials configured ✅

### 4. Frontend
- Already configured to call production backend ✅
- No localhost references ✅
- CORS safe ✅

---

## 📁 DEPLOYMENT STRUCTURE

```
Railway will deploy:
backend-php/
├── api/              (All API endpoints)
├── config/           (Database config)
├── login.php         (Login endpoint)
├── register.php      (Register endpoint)
├── get_plans.php     (Get plans endpoint)
└── ... (all other PHP files)

Railway will ignore:
- frontend/
- node_modules/
- server.js (Node.js backend)
- *.md files
```

---

## ✅ VERIFICATION CHECKLIST

After deployment:

- [ ] Railway build succeeds
- [ ] PHP server starts on port $PORT
- [ ] Database connection works (port 36666)
- [ ] `/test_deployment.php` returns success
- [ ] `/login.php` accepts POST requests
- [ ] `/get_plans.php` returns data
- [ ] Frontend can call all endpoints
- [ ] CORS headers work correctly

---

## 🎯 EXPECTED RESULTS

### Backend URLs (After Deployment)
```
https://word-tracker-production.up.railway.app/login.php
https://word-tracker-production.up.railway.app/register.php
https://word-tracker-production.up.railway.app/get_plans.php
https://word-tracker-production.up.railway.app/create_plan.php
https://word-tracker-production.up.railway.app/api/get_stats.php
... (all other endpoints)
```

### Frontend Configuration
```typescript
apiUrl: 'https://word-tracker-production.up.railway.app'
// Calls: ${apiUrl}/get_plans.php
```

---

## 🔍 TROUBLESHOOTING

### If deployment fails:

1. **Check Railway logs** for errors
2. **Verify environment variables** are set
3. **Check database connection** - port must be 36666
4. **Test PHP syntax** locally first

### If endpoints return 404:

1. Check that PHP files exist in `backend-php/` folder
2. Verify `startCommand` is correct in railway.json
3. Check Railway deployment logs

### If database connection fails:

1. Verify `MYSQLPORT=36666` is set in Railway
2. Check other database env vars are correct
3. Test connection with `test_db_direct.php`

---

## 🎉 READY TO DEPLOY!

Everything is configured. Just:

1. Set environment variables in Railway
2. Push to GitHub
3. Railway will auto-deploy
4. Test endpoints
5. Deploy frontend to Netlify

---

**Status:** 🟢 READY FOR PRODUCTION DEPLOYMENT  
**Database Port:** 36666 ✅  
**Backend:** PHP ✅  
**Frontend:** Configured ✅
