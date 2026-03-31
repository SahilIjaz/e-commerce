<?php

namespace App;

use MongoDB\Client;
use MongoDB\Database;

class Database
{
    private static $instance = null;
    private $client;
    private $db;

    private function __construct()
    {
        try {
            $uri = $_ENV['MONGODB_URI'];
            $this->client = new Client($uri);
            $this->db = $this->client->{$_ENV['DB_NAME']};
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDatabase(): Database
    {
        return $this->db;
    }

    public function getCollection($name)
    {
        return $this->db->selectCollection($name);
    }
}
