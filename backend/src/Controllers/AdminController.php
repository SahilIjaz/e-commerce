<?php

namespace App\Controllers;

use App\Database;
use App\Auth;

class AdminController
{
    public function __construct()
    {
    }

    public function getDashboard()
    {
        Auth::requireAuth('admin');

        try {
            $userDb = Database::getInstance()->getCollection('users');
            $productDb = Database::getInstance()->getCollection('products');
            $orderDb = Database::getInstance()->getCollection('orders');

            $totalUsers = $userDb->countDocuments();
            $totalProducts = $productDb->countDocuments();
            $totalOrders = $orderDb->countDocuments();

            // Calculate total revenue
            $orders = $orderDb->find()->toArray();
            $totalRevenue = array_reduce($orders, function($carry, $order) {
                return $carry + ($order['total'] ?? 0);
            }, 0);

            // Count by role
            $buyers = $userDb->countDocuments(['role' => 'buyer']);
            $clients = $userDb->countDocuments(['role' => 'client']);

            echo json_encode([
                'success' => true,
                'dashboard' => [
                    'totalUsers' => $totalUsers,
                    'totalProducts' => $totalProducts,
                    'totalOrders' => $totalOrders,
                    'totalRevenue' => $totalRevenue,
                    'usersByRole' => [
                        'buyers' => $buyers,
                        'clients' => $clients,
                        'admins' => $userDb->countDocuments(['role' => 'admin'])
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getStats()
    {
        Auth::requireAuth('admin');

        try {
            $orderDb = Database::getInstance()->getCollection('orders');
            $orders = $orderDb->find()->toArray();

            $statuses = [];
            foreach ($orders as $order) {
                $status = $order['status'] ?? 'unknown';
                $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            }

            echo json_encode([
                'success' => true,
                'stats' => [
                    'ordersByStatus' => $statuses,
                    'totalRevenue' => array_reduce($orders, function($carry, $order) {
                        return $carry + ($order['total'] ?? 0);
                    }, 0)
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
