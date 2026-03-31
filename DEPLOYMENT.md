# ShopHub Deployment Guide

Complete guide for deploying ShopHub to production environments.

## Deployment Architecture

```
┌─────────────────────┐
│  Vercel Frontend    │
│   (Next.js App)     │
└──────────┬──────────┘
           │
           │ HTTPS API Calls
           │
┌──────────▼──────────────────┐
│   Backend Server            │
│   (Heroku/AWS/Render)       │
│   PHP REST API              │
└──────────┬──────────────────┘
           │
           │
┌──────────┴──────────┬────────────────┐
│                     │                │
▼                     ▼                ▼
MongoDB          Cloudinary        Third-party
Atlas            (Images)          Services
```

## Part 1: Backend Deployment (Heroku)

### Prerequisites
- Heroku account (free or paid)
- Heroku CLI installed
- Git repository initialized

### Step-by-Step Deployment

1. **Initialize Git Repository**
   ```bash
   cd backend
   git init
   git add .
   git commit -m "Initial backend commit"
   ```

2. **Create Heroku App**
   ```bash
   heroku login
   heroku create your-ecommerce-api
   ```

3. **Configure Environment Variables**
   ```bash
   heroku config:set MONGODB_URI="your_mongodb_uri"
   heroku config:set CLOUDINARY_CLOUD_NAME="dbwuumsdy"
   heroku config:set CLOUDINARY_API_KEY="215729642962335"
   heroku config:set CLOUDINARY_API_SECRET="fAdgo-rDMnjyJmbFwF3_Qvajs2o"
   heroku config:set JWT_SECRET="your_production_secret_key"
   heroku config:set APP_ENV="production"
   ```

4. **Create Procfile**
   ```bash
   echo "web: php -S 0.0.0.0:\$PORT -t public" > Procfile
   ```

5. **Deploy**
   ```bash
   git push heroku main
   ```

6. **Verify Deployment**
   ```bash
   heroku logs --tail
   heroku open
   ```

### Alternative: AWS Deployment

1. **Create EC2 Instance**
   - OS: Ubuntu 20.04 LTS
   - Instance Type: t3.micro (free tier)
   - Security Group: Allow ports 80, 443, 8000

2. **SSH into Instance**
   ```bash
   ssh -i your-key.pem ubuntu@your-instance-ip
   ```

3. **Setup PHP Environment**
   ```bash
   sudo apt update
   sudo apt install php7.4 php7.4-cli php7.4-curl git

   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

4. **Clone Repository**
   ```bash
   git clone your-repo-url
   cd backend
   composer install
   ```

5. **Setup PM2 (Process Manager)**
   ```bash
   sudo apt install nodejs npm
   sudo npm install -g pm2
   pm2 start "php -S 0.0.0.0:8000 -t public" --name "ecommerce-api"
   pm2 startup
   pm2 save
   ```

6. **Setup Nginx Reverse Proxy**
   ```bash
   sudo apt install nginx
   sudo nano /etc/nginx/sites-available/default
   ```

   Add configuration:
   ```nginx
   server {
       listen 80 default_server;
       server_name _;

       location / {
           proxy_pass http://localhost:8000;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
       }
   }
   ```

   ```bash
   sudo systemctl restart nginx
   ```

### Alternative: Render Deployment

1. **Connect Repository to Render**
   - Go to render.com
   - Click "New +" → "Web Service"
   - Connect GitHub repository

2. **Configure Service**
   - Build Command: `composer install`
   - Start Command: `php -S 0.0.0.0:$PORT -t public`

3. **Add Environment Variables**
   - Same as Heroku setup above

4. **Deploy**
   - Render will automatically deploy on git push

---

## Part 2: Frontend Deployment (Vercel)

### Prerequisites
- Vercel account (free)
- GitHub repository

### Step-by-Step Deployment

1. **Connect Repository to Vercel**
   - Go to vercel.com
   - Click "New Project"
   - Import your GitHub repository

2. **Configure Build Settings**
   - Framework: Next.js
   - Build Command: `npm run build`
   - Output Directory: `.next`

3. **Add Environment Variables**
   ```
   NEXT_PUBLIC_API_URL=https://your-backend-domain.com/api
   ```

4. **Deploy**
   - Click "Deploy"
   - Vercel will automatically deploy on git push

### Alternative: Netlify Deployment

1. **Build Project Locally**
   ```bash
   cd frontend
   npm run build
   npm run export
   ```

2. **Deploy to Netlify**
   - Drag and drop `.next` folder to Netlify
   - Or connect GitHub for automatic deployments

3. **Configure Redirects**
   Create `_redirects` file:
   ```
   /* /index.html 200
   ```

### Alternative: Self-Hosted Frontend

1. **Build**
   ```bash
   npm run build
   ```

2. **Deploy to Server**
   ```bash
   scp -r .next ubuntu@your-server:/var/www/app/
   ```

3. **Setup Node.js**
   ```bash
   npm install -g pm2
   pm2 start "npm start" --name "ecommerce-frontend"
   ```

---

## Part 3: Database Setup

### MongoDB Atlas (Recommended)

1. **Create Cluster**
   - Go to mongodb.com/cloud
   - Create M0 cluster (free)

2. **Whitelist IP**
   - Network Access → Add IP Address
   - Add your server's IP

3. **Create Database User**
   - Database Access → Add Database User
   - Username: dbuser
   - Password: (strong password)

4. **Get Connection String**
   - Cluster → Connect → Connection String
   - Copy and use in backend .env

### Self-Hosted MongoDB

1. **Install on Server**
   ```bash
   sudo apt install mongodb
   sudo systemctl start mongodb
   ```

2. **Create Database**
   ```bash
   mongosh
   use ecommerce
   db.createCollection("users")
   ```

---

## Part 4: SSL/HTTPS Setup

### Using Let's Encrypt (Recommended)

1. **Install Certbot**
   ```bash
   sudo apt install certbot python3-certbot-nginx
   ```

2. **Get Certificate**
   ```bash
   sudo certbot certonly --standalone -d yourdomain.com
   ```

3. **Configure Nginx**
   ```nginx
   server {
       listen 443 ssl;
       server_name yourdomain.com;

       ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
       ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

       location / {
           proxy_pass http://localhost:8000;
       }
   }

   server {
       listen 80;
       server_name yourdomain.com;
       return 301 https://$server_name$request_uri;
   }
   ```

4. **Auto-Renewal**
   ```bash
   sudo systemctl enable certbot.timer
   ```

---

## Part 5: Monitoring & Maintenance

### Setup Monitoring

1. **Application Monitoring**
   ```bash
   # Install PM2 Plus monitoring
   pm2 monitor
   ```

2. **Error Tracking (Sentry)**
   - Sign up at sentry.io
   - Add API key to frontend

3. **Uptime Monitoring**
   - Use UptimeRobot.com
   - Monitor your API endpoint

### Backup Strategy

1. **Database Backups**
   ```bash
   # MongoDB Atlas automatic backups (enabled)
   # Or manual backup
   mongodump --uri="your-connection-string" --out=./backup
   ```

2. **Code Backups**
   - Use GitHub with automatic deployments
   - Maintain backup branch

### Security Checklist

- ✅ Change all default passwords
- ✅ Enable two-factor authentication
- ✅ Use strong JWT secret
- ✅ Update all dependencies regularly
- ✅ Enable HTTPS/SSL
- ✅ Configure CORS properly
- ✅ Set up firewall rules
- ✅ Enable database authentication
- ✅ Regular security audits
- ✅ Implement rate limiting

---

## Part 6: Performance Optimization

### Frontend Optimization

1. **Image Optimization**
   - Already using Cloudinary CDN
   - Cloudinary handles optimization

2. **Build Optimization**
   ```bash
   npm run build
   npm run analyze  # Analyze bundle size
   ```

3. **Caching Strategy**
   - Use Vercel's automatic caching
   - Configure revalidation in next.config.js

### Backend Optimization

1. **Database Indexing**
   ```javascript
   // Already configured in controllers
   db.products.createIndex({ name: "text" })
   db.users.createIndex({ email: 1 })
   ```

2. **API Response Caching**
   - Implement Redis (optional)
   - Use HTTP caching headers

---

## Part 7: Post-Deployment Checklist

- [ ] Test all authentication flows
- [ ] Verify product uploads work
- [ ] Test shopping cart functionality
- [ ] Verify order creation
- [ ] Check admin dashboard
- [ ] Test responsive design on mobile
- [ ] Verify HTTPS/SSL working
- [ ] Check performance metrics
- [ ] Test payment flow (if integrated)
- [ ] Setup automated backups
- [ ] Configure error monitoring
- [ ] Setup uptime monitoring
- [ ] Configure email notifications
- [ ] Test with real data
- [ ] Performance testing
- [ ] Security testing
- [ ] Load testing

---

## Part 8: Domain Setup

### Domain Registration

1. **Register Domain**
   - GoDaddy, Namecheap, Route53, etc.

2. **Configure DNS**
   For Vercel:
   ```
   Type: CNAME
   Name: @
   Value: cname.vercel-dns.com
   ```

   For Backend (Custom Server):
   ```
   Type: A
   Name: api
   Value: your-server-ip
   ```

3. **Update Frontend .env**
   ```
   NEXT_PUBLIC_API_URL=https://api.yourdomain.com
   ```

---

## Part 9: Continuous Deployment

### GitHub Actions (Automatic)

1. **Create `.github/workflows/deploy.yml`**
   ```yaml
   name: Deploy

   on:
     push:
       branches: [ main ]

   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v2
         - name: Deploy to Vercel
           uses: vercel/action@master
           with:
             vercel-token: ${{ secrets.VERCEL_TOKEN }}
   ```

2. **Add Secrets**
   - VERCEL_TOKEN
   - MONGODB_URI
   - Other API keys

---

## Troubleshooting Deployment Issues

### Backend Won't Connect
```bash
# Check logs
heroku logs --tail

# Verify environment variables
heroku config

# Test connection
heroku run "php -r \"require 'vendor/autoload.php'; new App\Database();\""
```

### Frontend Shows API Errors
```bash
# Check environment variables are set
echo $NEXT_PUBLIC_API_URL

# Check CORS headers from backend
curl -H "Origin: https://yourdomain.com" your-api/products
```

### Database Connection Issues
```bash
# Test MongoDB connection
mongosh "your-connection-string"

# Check IP whitelist
# MongoDB Atlas → Network Access → Review IPs
```

### Image Upload Not Working
```bash
# Verify Cloudinary credentials
# Check API key and secret
# Test with curl:
curl -X POST https://api.cloudinary.com/v1_1/YOUR_CLOUD_NAME/image/upload \
  -F "file=@image.jpg" \
  -F "api_key=YOUR_API_KEY"
```

---

## Cost Estimation (Monthly)

| Service | Free Tier | Paid Tier |
|---------|-----------|-----------|
| MongoDB Atlas | 512MB | $9+ |
| Vercel Frontend | Yes | Pay as you go |
| Heroku Backend | No (deprecated free) | $7+ |
| Cloudinary Images | 25GB/month | $99+ |
| Domain Name | - | $10-15 |
| **Total** | Cloudinary overage | ~$30-50+ |

---

## Summary

ShopHub can be deployed in multiple ways. For best performance and cost:

**Recommended Stack:**
- Frontend: Vercel (free)
- Backend: Heroku or AWS (paid)
- Database: MongoDB Atlas free tier
- Images: Cloudinary free tier
- **Total Cost: ~$10-20/month**

For maximum control and scalability:
- Use AWS EC2 for backend
- AWS RDS for PostgreSQL
- CloudFront for CDN
- **Total Cost: $50-100+/month**

---

**Deployment Status: ✅ Ready for Production**

All services are configured and ready to deploy.
