# 🚂 Railway Deployment Guide - Word Tracker

## ✅ Repository Successfully Pushed!

Your code is now at: **https://github.com/WebNovaCrew/Word-Tracker**

---

## 🚀 Deploy to Railway (30 Minutes)

### **STEP 1: Create Railway Project & Deploy Backend** (15 min)

#### 1.1 Sign Up / Login to Railway
- Go to: **https://railway.app**
- Sign up or login with GitHub

#### 1.2 Create New Project
1. Click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. Choose **"WebNovaCrew/Word-Tracker"**
4. Railway will create a new project

#### 1.3 Add MySQL Database
1. In your Railway project dashboard, click **"+ New"**
2. Select **"Database"** → **"Add MySQL"**
3. Railway automatically creates these environment variables:
   - `MYSQLHOST`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLPORT`

#### 1.4 Configure Backend Service
1. Click on the service that was auto-created (should show your repo)
2. Go to **"Settings"** tab
3. Under **"Root Directory"**, leave it **EMPTY** (deploy from root)
4. Under **"Start Command"**, it should auto-detect from `railway.json`
5. Click **"Deploy"**

#### 1.5 Get Backend URL
1. Go to **"Settings"** → **"Networking"**
2. Click **"Generate Domain"**
3. Copy the URL (e.g., `https://word-tracker-production.up.railway.app`)
4. **SAVE THIS URL** - you'll need it for frontend!

#### 1.6 Initialize Database
1. Visit: `https://YOUR-BACKEND-URL/init_railway_db.php`
2. You should see: `{"status":"success","message":"Database initialized"}`
3. This creates all necessary tables

#### 1.7 Test Backend
- Visit: `https://YOUR-BACKEND-URL/api/ping.php`
- Should return: `{"status":"success","message":"API is running"}`

---

### **STEP 2: Update Frontend Configuration** (5 min)

#### 2.1 Update Production Environment File
1. Edit: `frontend/src/environments/environment.prod.ts`
2. Replace the URL with your actual Railway backend URL:

```typescript
export const environment = {
    production: true,
    apiUrl: 'https://YOUR-ACTUAL-BACKEND-URL.railway.app'
};
```

#### 2.2 Update CORS Configuration
1. Edit: `config/cors.php`
2. Add your frontend domain to the `$allowedOrigins` array (you'll get this after frontend deployment)

#### 2.3 Commit and Push Changes
```bash
git add frontend/src/environments/environment.prod.ts config/cors.php
git commit -m "Update production API URL"
git push
```

---

### **STEP 3: Deploy Frontend to Railway** (10 min)

#### Option A: Deploy Frontend on Railway

1. In your Railway project, click **"+ New"**
2. Select **"GitHub Repo"**
3. Choose **"WebNovaCrew/Word-Tracker"** again
4. Configure the service:
   - **Root Directory**: `frontend`
   - **Build Command**: `npm install && npm run build`
   - **Start Command**: Leave empty (Railway auto-detects)
5. Add environment variable:
   - Key: `NODE_VERSION`
   - Value: `18`
6. Click **"Deploy"**

#### Option B: Deploy Frontend on Vercel (Recommended - Better for Angular)

1. Go to: **https://vercel.com**
2. Click **"Add New"** → **"Project"**
3. Import **"WebNovaCrew/Word-Tracker"**
4. Configure:
   - **Framework Preset**: Angular
   - **Root Directory**: `frontend`
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist/word-tracker/browser`
5. Click **"Deploy"**

#### 3.1 Get Frontend URL
- Railway: Settings → Networking → Generate Domain
- Vercel: Automatically provided after deployment

#### 3.2 Update CORS (Important!)
1. Edit `config/cors.php`
2. Add your frontend URL to `$allowedOrigins`:
```php
$allowedOrigins = [
    'http://localhost:4200',
    'https://YOUR-FRONTEND-URL.vercel.app',  // Add this
    // or
    'https://YOUR-FRONTEND-URL.railway.app',  // Add this
];
```
3. Commit and push:
```bash
git add config/cors.php
git commit -m "Add frontend URL to CORS"
git push
```

---

## 🎯 Verification Checklist

After deployment, test these features:

- [ ] Visit frontend URL - loads without errors
- [ ] Backend health check: `/api/ping.php` returns success
- [ ] Database initialized: `/init_railway_db.php` returns success
- [ ] Register new user account
- [ ] Login with credentials
- [ ] Create a writing plan
- [ ] View plan calendar
- [ ] Add daily progress
- [ ] Create checklist
- [ ] Mark checklist items as done
- [ ] View statistics dashboard

---

## 🔧 Configuration Files Already Set Up

✅ **Backend Configuration**
- `railway.json` - Railway deployment config
- `nixpacks.toml` - PHP 8.2 build config
- `config/database.php` - Auto-detects Railway MySQL
- `config/cors.php` - Production CORS handling
- `schema.sql` - Complete database schema
- `init_railway_db.php` - Database initialization script

✅ **Frontend Configuration**
- `frontend/package.json` - Node 18+ required
- `frontend/angular.json` - Angular 17 build config
- `frontend/src/environments/environment.prod.ts` - Production API URL

---

## 📊 Project Architecture

```
┌─────────────────────────────────────────┐
│           USERS (Browser)               │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│   FRONTEND (Railway/Vercel)             │
│   Angular 17 SPA                        │
│   - Static files                        │
│   - Client-side routing                 │
└──────────────┬──────────────────────────┘
               │ HTTPS API Calls
               ▼
┌─────────────────────────────────────────┐
│   BACKEND (Railway)                     │
│   PHP 8.2 REST API                      │
│   - /api/* endpoints                    │
│   - CORS enabled                        │
│   - JWT authentication                  │
└──────────────┬──────────────────────────┘
               │ MySQL Connection
               ▼
┌─────────────────────────────────────────┐
│   DATABASE (Railway MySQL)              │
│   - Users, Plans, Checklists            │
│   - Projects, Challenges                │
│   - Automatic backups                   │
└─────────────────────────────────────────┘
```

---

## 🆘 Troubleshooting

### Backend Issues

**❌ "Database connection error"**
- Check Railway MySQL service is running
- Verify environment variables are set in Railway dashboard
- Run `/init_railway_db.php` to create tables

**❌ "404 Not Found" on API endpoints**
- Check `railway.json` start command
- Verify `index.php` is routing correctly
- Check Railway deployment logs

### Frontend Issues

**❌ "Connection error" or "API not responding"**
- Verify `environment.prod.ts` has correct backend URL
- Check backend is deployed and accessible
- Test backend directly: `https://backend-url/api/ping.php`

**❌ CORS errors in browser console**
- Update `config/cors.php` with frontend URL
- Commit and push to trigger redeploy
- Clear browser cache

**❌ Build fails on Railway/Vercel**
- Check Node.js version is 18+
- Verify all dependencies in `package.json`
- Check build logs for specific errors

### Database Issues

**❌ Tables don't exist**
- Visit `/init_railway_db.php` to create tables
- Check Railway MySQL logs
- Verify database credentials in environment variables

---

## 💰 Cost Estimate

| Service | Plan | Cost |
|---------|------|------|
| Railway (Backend + MySQL) | Hobby | $5/month |
| Vercel (Frontend) | Hobby | FREE |
| **TOTAL** | | **$5/month** |

---

## 📝 Important URLs

- **GitHub Repository**: https://github.com/WebNovaCrew/Word-Tracker
- **Railway Dashboard**: https://railway.app/dashboard
- **Vercel Dashboard**: https://vercel.com/dashboard

---

## 🎉 Next Steps

1. ✅ Code pushed to GitHub
2. ⏳ Deploy backend to Railway (follow STEP 1)
3. ⏳ Initialize database (visit `/init_railway_db.php`)
4. ⏳ Update frontend config with backend URL (STEP 2)
5. ⏳ Deploy frontend to Railway/Vercel (STEP 3)
6. ⏳ Update CORS configuration
7. ⏳ Test all features

---

**Good luck with your deployment! 🚀**
