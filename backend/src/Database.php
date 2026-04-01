<?php

namespace App;






class Database
{
    private static $instance = null;
    private $collections = [];
    private $dataFile;

    private function __construct()
    {
        $this->dataFile = __DIR__ . '/../data.json';
        $this->loadCollections();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadCollections()
    {
        if (file_exists($this->dataFile)) {
            $data = json_decode(file_get_contents($this->dataFile), true);
            foreach ($data as $name => $docs) {
                $this->collections[$name] = new Collection($name, $docs);
            }
        } else {
            // Initialize new collections
            $this->collections['users'] = new Collection('users', []);
            $this->collections['products'] = new Collection('products', []);
            $this->collections['orders'] = new Collection('orders', []);
            $this->collections['carts'] = new Collection('carts', []);
            $this->collections['categories'] = new Collection('categories', [
                [
                    '_id' => 'cat_1',
                    'name' => 'Electronics',
                    'description' => 'Electronic products',
                    'icon' => '📱'
                ]
            ]);
            $this->saveCollections();
        }
    }

    public function getCollection($name)
    {
        if (!isset($this->collections[$name])) {
            $this->collections[$name] = new Collection($name, []);
        }
        return $this->collections[$name];
    }

    public function saveCollections()
    {
        $data = [];
        foreach ($this->collections as $name => $collection) {
            $data[$name] = $collection->getAllData();
        }
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function __destruct()
    {
        $this->saveCollections();
    }
}

class Collection
{
    private $name;
    private $data = [];
    private $idCounter = 0;

    public function __construct($name, $initialData = [])
    {
        $this->name = $name;
        $this->data = $initialData;
        foreach ($this->data as $doc) {
            if (isset($doc['_id'])) {
                $parts = explode('_', $doc['_id']);
                if (count($parts) >= 2 && is_numeric($parts[1])) {
                    $this->idCounter = max($this->idCounter, (int)$parts[1]);
                }
            }
        }
    }

    public function findOne($filter)
    {
        foreach ($this->data as $doc) {
            if ($this->matches($doc, $filter)) {
                return $doc;
            }
        }
        return null;
    }

    public function find($filter = [])
    {
        $results = [];
        foreach ($this->data as $doc) {
            if (empty($filter) || $this->matches($doc, $filter)) {
                $results[] = $doc;
            }
        }
        return new ArrayWrapper($results);
    }

    public function insertOne($data)
    {
        if (!isset($data['_id'])) {
            $this->idCounter++;
            $data['_id'] = 'id_' . $this->idCounter . '_' . time();
        }
        $this->data[] = $data;
        Database::getInstance()->saveCollections();
        return new InsertResult($data['_id']);
    }

    public function updateOne($filter, $update)
    {
        $found = false;
        foreach ($this->data as &$doc) {
            if ($this->matches($doc, $filter)) {
                if (isset($update['$set'])) {
                    foreach ($update['$set'] as $key => $value) {
                        $doc[$key] = $value;
                    }
                }
                $found = true;
                break;
            }
        }
        if ($found) {
            Database::getInstance()->saveCollections();
        }
        return new UpdateResult($found ? 1 : 0);
    }

    public function deleteOne($filter)
    {
        $count = 0;
        foreach ($this->data as $key => $doc) {
            if ($this->matches($doc, $filter)) {
                unset($this->data[$key]);
                $count++;
                break;
            }
        }
        if ($count > 0) {
            Database::getInstance()->saveCollections();
        }
        return new DeleteResult($count);
    }

    public function countDocuments($filter = [])
    {
        $count = 0;
        foreach ($this->data as $doc) {
            if (empty($filter) || $this->matches($doc, $filter)) {
                $count++;
            }
        }
        return $count;
    }

    public function getAllData()
    {
        return $this->data;
    }

    private function matches($doc, $filter)
    {
        foreach ($filter as $key => $value) {
            if (!isset($doc[$key]) || $doc[$key] !== $value) {
                return false;
            }
        }
        return true;
    }
}

class ArrayWrapper
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function toArray()
    {
        return $this->data;
    }
}

class InsertResult
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getInsertedId()
    {
        return $this->id;
    }
}

class UpdateResult
{
    private $matched;

    public function __construct($matched)
    {
        $this->matched = $matched;
    }

    public function getMatchedCount()
    {
        return $this->matched;
    }

    public function getModifiedCount()
    {
        return $this->matched;
    }
}

class DeleteResult
{
    private $deleted;

    public function __construct($deleted)
    {
        $this->deleted = $deleted;
    }

    public function getDeletedCount()
    {
        return $this->deleted;
    }
}
