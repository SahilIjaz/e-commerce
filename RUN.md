# How to Run ShopHub Locally

## Backend (PHP Server - Already Running on port 8000)

Backend is already running on: **http://localhost:8000**

To stop and restart:
```bash
# Kill any running process on 8000
lsof -i :8000 | grep LISTEN | awk '{print $2}' | xargs kill -9

# Start backend
cd backend
php -S localhost:8000 -t public
```

## Frontend (Next.js - Start New Terminal)

Open a **NEW TERMINAL** and run:

```bash
cd frontend
npm run dev
```

Then open: **http://localhost:3000**

---

## What's Running

| Service | URL | Status |
|---------|-----|--------|
| Backend API | http://localhost:8000 | ✅ Running |
| Frontend App | http://localhost:3000 | 👉 Start this next |

---

##  Next Step

1. Open a new terminal window
2. Run: `cd /Users/sahilijaz/Desktop/oldToNew/e_comece && cd frontend && npm run dev`
3. Visit: http://localhost:3000

That's it! Your e-commerce app is ready to go! 🚀
