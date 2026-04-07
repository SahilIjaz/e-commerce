#!/bin/bash

# Start both backend and frontend servers

echo "🚀 Starting ShopHub E-Commerce Platform..."
echo ""

# Start Backend in background
echo "📦 Starting Backend (PHP) on http://localhost:8000..."
cd /Users/sahilijaz/Desktop/oldToNew/e_comece/backend
php -S localhost:8000 -t public > /tmp/backend.log 2>&1 &
BACKEND_PID=$!
echo "✅ Backend started (PID: $BACKEND_PID)"
echo ""

# Start Frontend in background
echo "🎨 Starting Frontend (Next.js) on http://localhost:3000..."
cd /Users/sahilijaz/Desktop/oldToNew/e_comece/frontend
npm run dev > /tmp/frontend.log 2>&1 &
FRONTEND_PID=$!
echo "✅ Frontend started (PID: $FRONTEND_PID)"
echo ""

echo "═════════════════════════════════════════════════"
echo "✨ ShopHub is running!"
echo "═════════════════════════════════════════════════"
echo ""
echo "📱 Frontend: http://localhost:3000"
echo "🔌 Backend:  http://localhost:8000/api"
echo ""
echo "Press Ctrl+C to stop both servers"
echo ""

# Wait for Ctrl+C
wait

# Kill both processes
kill $BACKEND_PID 2>/dev/null
kill $FRONTEND_PID 2>/dev/null
echo ""
echo "✋ Servers stopped"
