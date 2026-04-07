'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import Image from 'next/image';
import useCartStore from '@/store/cartStore';
import useAuthStore from '@/store/authStore';
import api from '@/lib/api';
import toast from 'react-hot-toast';
import { FiTrash2, FiArrowLeft } from 'react-icons/fi';

export default function CartPage() {
  const router = useRouter();
  const { items, total, removeItem, updateQuantity, clearCart, loadCart } = useCartStore();
  const { user } = useAuthStore();

  useEffect(() => {
    if (user && user.role !== 'buyer') {
      toast.error('Only buyers can access cart');
      router.push('/');
    }
  }, [user, router]);

  useEffect(() => {
    if (user) {
      // Load cart from backend
      api.get('/cart')
        .then((response) => {
          if (response.data.success && response.data.cart) {
            const cartItems = response.data.cart.items || [];
            const cartTotal = cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
            loadCart(cartItems, cartTotal);
          }
        })
        .catch((err) => {
          console.error('Failed to load cart:', err);
        });
    }
  }, [user, loadCart]);

  const handleCheckout = async () => {
    if (items.length === 0) {
      toast.error('Cart is empty');
      return;
    }

    try {
      const response = await api.post('/orders', {
        shippingAddress: 'User Address',
        paymentMethod: 'card',
      });

      clearCart();
      toast.success('Order placed successfully!');
      router.push(`/orders/${response.data.orderId}`);
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to create order');
    }
  };

  if (!user) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-600 mb-4">Please login to view your cart</p>
        <Link href="/login" className="text-primary hover:underline">
          Login
        </Link>
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-600 text-lg mb-6">Your cart is empty</p>
        <Link
          href="/products"
          className="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition"
        >
          <FiArrowLeft />
          Continue Shopping
        </Link>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-4xl font-bold mb-8">Shopping Cart</h1>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Cart Items */}
        <div className="lg:col-span-2">
          <div className="bg-white rounded-lg shadow-md overflow-hidden">
            {items.map((item) => (
              <div
                key={item.productId}
                className="flex items-center justify-between p-6 border-b last:border-b-0 hover:bg-gray-50 transition"
              >
                {/* Image */}
                <div className="relative w-24 h-24">
                  {item.image ? (
                    <Image
                      src={item.image}
                      alt={item.name}
                      fill
                      className="object-cover rounded-lg"
                    />
                  ) : (
                    <div className="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                      No Image
                    </div>
                  )}
                </div>

                {/* Details */}
                <div className="flex-1 ml-6">
                  <h3 className="font-semibold text-lg">{item.name}</h3>
                  <p className="text-gray-600">${item.price.toFixed(2)}</p>
                </div>

                {/* Quantity */}
                <div className="flex items-center border border-gray-300 rounded-lg mx-6">
                  <button
                    onClick={() => updateQuantity(item.productId, Math.max(1, item.quantity - 1))}
                    className="px-3 py-1 text-gray-600 hover:bg-gray-100"
                  >
                    −
                  </button>
                  <input
                    type="number"
                    min="1"
                    value={item.quantity}
                    onChange={(e) =>
                      updateQuantity(item.productId, Math.max(1, parseInt(e.target.value)))
                    }
                    className="w-12 text-center border-l border-r border-gray-300"
                  />
                  <button
                    onClick={() => updateQuantity(item.productId, item.quantity + 1)}
                    className="px-3 py-1 text-gray-600 hover:bg-gray-100"
                  >
                    +
                  </button>
                </div>

                {/* Subtotal */}
                <div className="text-right mr-6 min-w-max">
                  <p className="text-lg font-semibold">${(item.price * item.quantity).toFixed(2)}</p>
                </div>

                {/* Remove Button */}
                <button
                  onClick={() => removeItem(item.productId)}
                  className="text-red-600 hover:text-red-800 transition p-2"
                >
                  <FiTrash2 size={20} />
                </button>
              </div>
            ))}
          </div>

          {/* Continue Shopping Link */}
          <Link
            href="/products"
            className="inline-flex items-center gap-2 text-primary hover:underline mt-6"
          >
            <FiArrowLeft />
            Continue Shopping
          </Link>
        </div>

        {/* Order Summary */}
        <div className="lg:col-span-1">
          <div className="bg-white rounded-lg shadow-md p-6 sticky top-24">
            <h2 className="text-2xl font-bold mb-6">Order Summary</h2>

            <div className="space-y-4 mb-6">
              <div className="flex justify-between">
                <span>Subtotal:</span>
                <span>${total.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Shipping:</span>
                <span>Free</span>
              </div>
              <div className="flex justify-between">
                <span>Tax:</span>
                <span>${(total * 0.1).toFixed(2)}</span>
              </div>
            </div>

            <div className="border-t pt-4 mb-6">
              <div className="flex justify-between text-xl font-bold">
                <span>Total:</span>
                <span className="text-primary">${(total * 1.1).toFixed(2)}</span>
              </div>
            </div>

            <button
              onClick={handleCheckout}
              className="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-blue-600 transition mb-3"
            >
              Proceed to Checkout
            </button>

            <button
              onClick={() => clearCart()}
              className="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-semibold hover:bg-gray-300 transition"
            >
              Clear Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
