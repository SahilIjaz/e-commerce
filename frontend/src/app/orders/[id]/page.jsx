'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import useAuthStore from '@/store/authStore';
import api from '@/lib/api';
import toast from 'react-hot-toast';

export default function OrderDetailPage() {
  const params = useParams();
  const orderId = params.id;
  const { user } = useAuthStore();

  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchOrder = async () => {
      try {
        const response = await api.get(`/orders/${orderId}`);
        setOrder(response.data.order);
      } catch (error) {
        console.error('Error fetching order:', error);
        toast.error('Order not found');
      } finally {
        setLoading(false);
      }
    };

    if (orderId && user) {
      fetchOrder();
    }
  }, [orderId, user]);

  if (loading) {
    return <div className="text-center py-12">Loading order details...</div>;
  }

  if (!order) {
    return <div className="text-center py-12">Order not found</div>;
  }

  const getStatusColor = (status) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      processing: 'bg-blue-100 text-blue-800',
      shipped: 'bg-purple-100 text-purple-800',
      delivered: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  return (
    <div>
      <h1 className="text-4xl font-bold mb-8">Order Details</h1>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Order Details */}
        <div className="lg:col-span-2">
          <div className="bg-white p-6 rounded-lg shadow-md mb-6">
            <div className="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p className="text-gray-600 text-sm">Order ID</p>
                <p className="font-semibold text-lg">{order.id}</p>
              </div>
              <div>
                <p className="text-gray-600 text-sm">Order Date</p>
                <p className="font-semibold text-lg">
                  {new Date(order.createdAt).toLocaleDateString()}
                </p>
              </div>
              <div>
                <p className="text-gray-600 text-sm">Status</p>
                <span className={`inline-block px-3 py-1 rounded-full text-sm font-semibold ${getStatusColor(order.status)} capitalize`}>
                  {order.status}
                </span>
              </div>
              <div>
                <p className="text-gray-600 text-sm">Total Amount</p>
                <p className="font-semibold text-lg text-primary">
                  ${(order.total * 1.1).toFixed(2)}
                </p>
              </div>
            </div>

            {order.trackingNumber && (
              <div className="mb-6 p-4 bg-blue-50 rounded-lg">
                <p className="text-gray-600 text-sm">Tracking Number</p>
                <p className="font-semibold text-lg">{order.trackingNumber}</p>
              </div>
            )}

            {order.shippingAddress && (
              <div>
                <p className="text-gray-600 text-sm mb-2">Shipping Address</p>
                <p className="font-semibold">{order.shippingAddress}</p>
              </div>
            )}
          </div>

          {/* Order Items */}
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-2xl font-bold mb-4">Order Items</h2>
            <div className="space-y-4">
              {order.items && order.items.map((item, index) => (
                <div key={index} className="flex items-center justify-between p-4 border-b last:border-b-0">
                  <div className="flex-1">
                    <h3 className="font-semibold">{item.name}</h3>
                    <p className="text-gray-600">Qty: {item.quantity}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold">${item.price.toFixed(2)}</p>
                    <p className="text-gray-600 text-sm">
                      ${(item.price * item.quantity).toFixed(2)} total
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Order Summary */}
        <div className="lg:col-span-1">
          <div className="bg-white p-6 rounded-lg shadow-md sticky top-24">
            <h2 className="text-2xl font-bold mb-6">Summary</h2>

            <div className="space-y-4 mb-6">
              <div className="flex justify-between">
                <span>Subtotal:</span>
                <span className="font-semibold">${order.total.toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Tax (10%):</span>
                <span className="font-semibold">${(order.total * 0.1).toFixed(2)}</span>
              </div>
              <div className="flex justify-between">
                <span>Shipping:</span>
                <span className="font-semibold">Free</span>
              </div>
            </div>

            <div className="border-t pt-4">
              <div className="flex justify-between text-xl font-bold">
                <span>Total:</span>
                <span className="text-primary">${(order.total * 1.1).toFixed(2)}</span>
              </div>
            </div>

            {/* Timeline */}
            <div className="mt-8">
              <h3 className="font-semibold mb-4">Order Status Timeline</h3>
              <div className="space-y-3">
                <div className="flex items-start">
                  <div className="w-3 h-3 bg-green-500 rounded-full mt-1.5 mr-3"></div>
                  <div>
                    <p className="font-semibold">Order Placed</p>
                    <p className="text-sm text-gray-600">
                      {new Date(order.createdAt).toLocaleDateString()}
                    </p>
                  </div>
                </div>
                {['processing', 'shipped', 'delivered'].includes(order.status) && (
                  <div className="flex items-start">
                    <div className={`w-3 h-3 rounded-full mt-1.5 mr-3 ${
                      ['shipped', 'delivered'].includes(order.status) ? 'bg-green-500' : 'bg-gray-300'
                    }`}></div>
                    <div>
                      <p className="font-semibold">Processing</p>
                      <p className="text-sm text-gray-600">Preparing your order</p>
                    </div>
                  </div>
                )}
                {['shipped', 'delivered'].includes(order.status) && (
                  <div className="flex items-start">
                    <div className={`w-3 h-3 rounded-full mt-1.5 mr-3 ${
                      order.status === 'delivered' ? 'bg-green-500' : 'bg-gray-300'
                    }`}></div>
                    <div>
                      <p className="font-semibold">Shipped</p>
                      <p className="text-sm text-gray-600">On its way to you</p>
                    </div>
                  </div>
                )}
                {order.status === 'delivered' && (
                  <div className="flex items-start">
                    <div className="w-3 h-3 bg-green-500 rounded-full mt-1.5 mr-3"></div>
                    <div>
                      <p className="font-semibold">Delivered</p>
                      <p className="text-sm text-gray-600">Order completed</p>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
