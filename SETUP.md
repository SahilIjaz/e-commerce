# Quick Start Guide

Get ShopHub running in 5 minutes!

## Step 1: Backend Setup (Terminal 1)

```bash
# Navigate to backend
cd backend

# Install PHP dependencies
composer install

# Start PHP server
php -S localhost:8000 -t public
```

✅ Backend running at: `http://localhost:8000/api`

## Step 2: Frontend Setup (Terminal 2)

```bash
# Navigate to frontend
cd frontend

# Install Node dependencies
npm install

# Start development server
npm run dev
```

✅ Frontend running at: `http://localhost:3000`

## Step 3: Test the Application

### Create Test Accounts

1. **Go to Register** (`http://localhost:3000/register`)
2. **Create Admin (Optional):**
   - Create account manually, then modify database to set role as 'admin'

3. **Create Seller Account:**
   - Name: John Smith
   - Email: seller@test.com
   - Password: seller123
   - Role: Sell products (Client)

4. **Create Buyer Account:**
   - Name: Jane Doe
   - Email: buyer@test.com
   - Password: buyer123
   - Role: Buy products (Buyer)

### Test Buyer Flow

1. Login as buyer
2. Go to **Products** page
3. Browse and search products
4. Click on product to see details
5. Click "Add to Cart"
6. Go to **Cart** (icon in navbar)
7. Click "Proceed to Checkout"
8. Complete order

### Test Seller Flow

1. Login as seller
2. Go to **My Products** (navbar menu)
3. Click "Add Product" button
4. Fill in product details:
   - Name: Test Product
   - Description: This is a test product
   - Price: 29.99
   - Stock: 100
   - Category: Electronics
5. Click "Create Product"
6. View product in Products page

### Test Admin Features (Manual Setup Required)

After creating admin account:
1. Go to **Dashboard** (navbar)
2. View key metrics:
   - Total users
   - Total products
   - Total orders
   - Total revenue
3. View user distribution by role
4. View order statistics

## Environment Files Already Configured

### Backend (.env)
```
MONGODB_URI=mongodb+srv://hssahil2913_db_user:7cq4IJUZIRULc0hm@cluster0.zvhdurt.mongodb.net/?appName=Cluster0
CLOUDINARY_CLOUD_NAME=dbwuumsdy
CLOUDINARY_API_KEY=215729642962335
CLOUDINARY_API_SECRET=fAdgo-rDMnjyJmbFwF3_Qvajs2o
JWT_SECRET=your_super_secret_jwt_key_change_this_in_production
```

### Frontend (.env.local)
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Common Issues & Solutions

### Backend Won't Start
```bash
# Check if port 8000 is in use
lsof -i :8000
# Kill process if needed
kill -9 <PID>
```

### Database Connection Error
- Check MongoDB connection string
- Verify IP is whitelisted in MongoDB Atlas
- Test connection: `php -r "require 'vendor/autoload.php'; new App\Database();"`

### Frontend Won't Connect to API
```bash
# Clear Next.js cache
rm -rf .next

# Restart dev server
npm run dev
```

### Image Upload Not Working
- Check Cloudinary credentials
- Verify file permissions
- Check browser console for upload errors

## Project Structure Summary

```
e_comece/
├── backend/          # PHP REST API
│   ├── public/       # Entry point
│   ├── src/          # Controllers & utilities
│   └── composer.json # Dependencies
├── frontend/         # Next.js React app
│   ├── src/
│   │   ├── app/      # Pages
│   │   ├── components/
│   │   ├── store/    # Zustand stores
│   │   └── lib/      # Utilities
│   └── package.json
└── README.md         # Full documentation
```

## Key URLs

| Page | URL | Access |
|------|-----|--------|
| Home | `http://localhost:3000` | Everyone |
| Products | `http://localhost:3000/products` | Everyone |
| Product Detail | `http://localhost:3000/products/[id]` | Everyone |
| Login | `http://localhost:3000/login` | Anonymous |
| Register | `http://localhost:3000/register` | Anonymous |
| Cart | `http://localhost:3000/cart` | Buyers |
| Seller Products | `http://localhost:3000/seller/products` | Sellers |
| Add Product | `http://localhost:3000/seller/products/new` | Sellers |
| Admin Dashboard | `http://localhost:3000/admin/dashboard` | Admins |

## API Testing with cURL

### Test Authentication
```bash
# Register
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"test123","role":"buyer"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'
```

### Get Products
```bash
curl http://localhost:8000/api/products
```

### Get Categories
```bash
curl http://localhost:8000/api/categories
```

## Next Steps

1. **Customize branding** - Update colors in tailwind.config.js
2. **Add more categories** - Create categories via API or admin dashboard
3. **Setup payment gateway** - Integrate Stripe/PayPal for payments
4. **Deploy application** - Use Vercel for frontend, Heroku/AWS for backend
5. **Enable email notifications** - Setup SendGrid or similar service
6. **Add more features** - Reviews, ratings, wishlists, etc.

## Support

Need help? Check:
- README.md for full documentation
- API endpoints documentation in README.md
- Backend controller files for implementation details
- Frontend component files for UI logic

---

**Happy coding! 🚀**
