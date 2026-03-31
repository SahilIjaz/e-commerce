'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import useAuthStore from '@/store/authStore';
import api from '@/lib/api';
import toast from 'react-hot-toast';

export default function AdminDashboard() {
  const router = useRouter();
  const { user } = useAuthStore();

  const [dashboard, setDashboard] = useState(null);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (user && user.role !== 'admin') {
      toast.error('Only admins can access this page');
      router.push('/');
    }
  }, [user, router]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [dashRes, statsRes] = await Promise.all([
          api.get('/admin/dashboard'),
          api.get('/admin/stats'),
        ]);

        setDashboard(dashRes.data.dashboard);
        setStats(statsRes.data.stats);
      } catch (error) {
        console.error('Error fetching dashboard:', error);
        toast.error('Failed to load dashboard');
      } finally {
        setLoading(false);
      }
    };

    if (user?.id) {
      fetchData();
    }
  }, [user?.id]);

  if (!user) {
    return <div className="text-center py-12">Loading...</div>;
  }

  if (loading) {
    return <div className="text-center py-12">Loading dashboard...</div>;
  }

  return (
    <div>
      <h1 className="text-4xl font-bold mb-8">Admin Dashboard</h1>

      {/* Key Metrics */}
      {dashboard && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-blue-50 p-6 rounded-lg shadow-md">
            <p className="text-gray-600 text-sm font-semibold">Total Users</p>
            <p className="text-4xl font-bold text-blue-600">{dashboard.totalUsers}</p>
          </div>

          <div className="bg-green-50 p-6 rounded-lg shadow-md">
            <p className="text-gray-600 text-sm font-semibold">Total Products</p>
            <p className="text-4xl font-bold text-green-600">{dashboard.totalProducts}</p>
          </div>

          <div className="bg-purple-50 p-6 rounded-lg shadow-md">
            <p className="text-gray-600 text-sm font-semibold">Total Orders</p>
            <p className="text-4xl font-bold text-purple-600">{dashboard.totalOrders}</p>
          </div>

          <div className="bg-orange-50 p-6 rounded-lg shadow-md">
            <p className="text-gray-600 text-sm font-semibold">Total Revenue</p>
            <p className="text-4xl font-bold text-orange-600">
              ${dashboard.totalRevenue.toFixed(2)}
            </p>
          </div>
        </div>
      )}

      {/* Users by Role */}
      {dashboard && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-xl font-bold mb-4">Users by Role</h2>
            <div className="space-y-3">
              <div className="flex justify-between items-center pb-3 border-b">
                <span>Buyers</span>
                <span className="font-semibold">{dashboard.usersByRole.buyers}</span>
              </div>
              <div className="flex justify-between items-center pb-3 border-b">
                <span>Sellers</span>
                <span className="font-semibold">{dashboard.usersByRole.clients}</span>
              </div>
              <div className="flex justify-between items-center">
                <span>Admins</span>
                <span className="font-semibold">{dashboard.usersByRole.admins}</span>
              </div>
            </div>
          </div>

          {/* Order Status */}
          {stats && (
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h2 className="text-xl font-bold mb-4">Orders by Status</h2>
              <div className="space-y-3">
                {Object.entries(stats.ordersByStatus).map(([status, count]) => (
                  <div key={status} className="flex justify-between items-center pb-3 border-b last:border-b-0">
                    <span className="capitalize">{status}</span>
                    <span className="font-semibold">{count}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Quick Actions */}
      <div className="bg-white p-6 rounded-lg shadow-md mt-8">
        <h2 className="text-xl font-bold mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <a
            href="/admin/users"
            className="block p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition text-center"
          >
            <p className="font-semibold text-blue-600">Manage Users</p>
          </a>
          <a
            href="/admin/products"
            className="block p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition text-center"
          >
            <p className="font-semibold text-green-600">Manage Products</p>
          </a>
          <a
            href="/admin/orders"
            className="block p-4 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition text-center"
          >
            <p className="font-semibold text-purple-600">View Orders</p>
          </a>
        </div>
      </div>
    </div>
  );
}
