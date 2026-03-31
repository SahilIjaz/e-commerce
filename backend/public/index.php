<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Define MongoDB namespace mock classes BEFORE autoload
if (!class_exists('MongoDB\BSON\UTCDateTime')) {
    class MongoDB_BSON_UTCDateTime extends \DateTime {}
    class MongoDB_BSON_ObjectId {
        public $oid;
        public function __construct($id = null) {
            $this->oid = $id ?: uniqid();
        }
        public function __toString() {
            return $this->oid;
        }
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;

// Create global namespace aliases for MongoDB classes
if (!function_exists('MongoDB\\BSON\\ObjectId')) {
    if (!class_exists('MongoDB\\BSON\\UTCDateTime')) {
        class_alias('MongoDB_BSON_UTCDateTime', 'MongoDB\\BSON\\UTCDateTime');
    }
    if (!class_exists('MongoDB\\BSON\\ObjectId')) {
        class_alias('MongoDB_BSON_ObjectId', 'MongoDB\\BSON\\ObjectId');
    }
}

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize router
$router = new Router();

// Auth Routes
$router->post('/api/auth/register', 'AuthController@register');
$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->get('/api/auth/me', 'AuthController@getCurrentUser');

// Product Routes
$router->get('/api/products', 'ProductController@getAll');
$router->get('/api/products/:id', 'ProductController@getById');
$router->post('/api/products', 'ProductController@create');
$router->put('/api/products/:id', 'ProductController@update');
$router->delete('/api/products/:id', 'ProductController@delete');
$router->post('/api/products/:id/upload-image', 'ProductController@uploadImage');

// Cart Routes
$router->get('/api/cart', 'CartController@getCart');
$router->post('/api/cart/add', 'CartController@addItem');
$router->put('/api/cart/update/:itemId', 'CartController@updateItem');
$router->delete('/api/cart/remove/:itemId', 'CartController@removeItem');
$router->delete('/api/cart/clear', 'CartController@clearCart');

// Order Routes
$router->get('/api/orders', 'OrderController@getOrders');
$router->get('/api/orders/:id', 'OrderController@getOrderById');
$router->post('/api/orders', 'OrderController@createOrder');
$router->put('/api/orders/:id/status', 'OrderController@updateOrderStatus');

// User Routes
$router->get('/api/users', 'UserController@getAll');
$router->get('/api/users/:id', 'UserController@getById');
$router->put('/api/users/:id', 'UserController@update');
$router->delete('/api/users/:id', 'UserController@delete');

// Category Routes
$router->get('/api/categories', 'CategoryController@getAll');
$router->post('/api/categories', 'CategoryController@create');
$router->put('/api/categories/:id', 'CategoryController@update');
$router->delete('/api/categories/:id', 'CategoryController@delete');

// Admin Dashboard Routes
$router->get('/api/admin/dashboard', 'AdminController@getDashboard');
$router->get('/api/admin/stats', 'AdminController@getStats');

// Execute router
$router->dispatch();
