'use client';

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import useAuthStore from '@/store/authStore';
import useCartStore from '@/store/cartStore';
import { FiShoppingCart, FiMenu, FiX, FiLogOut } from 'react-icons/fi';
import { useState } from 'react';

export default function Navbar() {
  const router = useRouter();
  const { user, logout } = useAuthStore();
  const { items } = useCartStore();
  const [isOpen, setIsOpen] = useState(false);

  const handleLogout = () => {
    logout();
    router.push('/');
  };

  return (
    <nav className="bg-white shadow-lg sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link href="/" className="flex items-center space-x-2">
            <div className="text-2xl font-bold text-primary">ShopHub</div>
          </Link>

          {/* Desktop Menu */}
          <div className="hidden md:flex items-center space-x-8">
            <Link href="/" className="text-gray-700 hover:text-primary transition">
              Home
            </Link>
            <Link href="/products" className="text-gray-700 hover:text-primary transition">
              Products
            </Link>

            {user?.role === 'client' && (
              <Link href="/seller/products" className="text-gray-700 hover:text-primary transition">
                My Products
              </Link>
            )}

            {user?.role === 'admin' && (
              <Link href="/admin/dashboard" className="text-gray-700 hover:text-primary transition">
                Dashboard
              </Link>
            )}
          </div>

          {/* Right Side Icons */}
          <div className="hidden md:flex items-center space-x-6">
            {user ? (
              <>
                {user?.role === 'buyer' && (
                  <Link href="/cart" className="relative hover:text-primary transition">
                    <FiShoppingCart className="text-2xl" />
                    {items.length > 0 && (
                      <span className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                        {items.length}
                      </span>
                    )}
                  </Link>
                )}

                <div className="flex items-center space-x-4">
                  <span className="text-sm text-gray-700">{user.name}</span>
                  <button
                    onClick={handleLogout}
                    className="flex items-center space-x-2 px-3 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition"
                  >
                    <FiLogOut />
                    <span className="hidden sm:inline">Logout</span>
                  </button>
                </div>
              </>
            ) : (
              <>
                <Link href="/login" className="text-gray-700 hover:text-primary transition">
                  Login
                </Link>
                <Link
                  href="/register"
                  className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition"
                >
                  Register
                </Link>
              </>
            )}
          </div>

          {/* Mobile Menu Button */}
          <div className="md:hidden flex items-center space-x-4">
            {user?.role === 'buyer' && (
              <Link href="/cart" className="relative">
                <FiShoppingCart className="text-2xl" />
                {items.length > 0 && (
                  <span className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                    {items.length}
                  </span>
                )}
              </Link>
            )}

            <button
              onClick={() => setIsOpen(!isOpen)}
              className="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-primary focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary"
            >
              {isOpen ? <FiX className="text-2xl" /> : <FiMenu className="text-2xl" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Menu */}
      {isOpen && (
        <div className="md:hidden bg-gray-50 border-t">
          <div className="px-2 pt-2 pb-3 space-y-1">
            <Link href="/" className="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200">
              Home
            </Link>
            <Link
              href="/products"
              className="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200"
            >
              Products
            </Link>

            {user?.role === 'client' && (
              <Link
                href="/seller/products"
                className="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200"
              >
                My Products
              </Link>
            )}

            {user?.role === 'admin' && (
              <Link
                href="/admin/dashboard"
                className="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200"
              >
                Dashboard
              </Link>
            )}

            {user ? (
              <button
                onClick={handleLogout}
                className="w-full text-left px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600"
              >
                Logout
              </button>
            ) : (
              <div className="space-y-2">
                <Link
                  href="/login"
                  className="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-200"
                >
                  Login
                </Link>
                <Link
                  href="/register"
                  className="block px-3 py-2 bg-primary text-white rounded-md hover:bg-blue-600"
                >
                  Register
                </Link>
              </div>
            )}
          </div>
        </div>
      )}
    </nav>
  );
}
