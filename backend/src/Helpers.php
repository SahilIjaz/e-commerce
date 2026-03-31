<?php

namespace App;

class ObjectId
{
    public $oid;

    public function __construct($id = null)
    {
        $this->oid = $id ?: uniqid();
    }

    public function __toString()
    {
        return $this->oid;
    }
}

// Create MongoDB namespace mock
if (!class_exists('MongoDB\BSON\UTCDateTime')) {
    class UTCDateTime extends \DateTime {}
}

// Create MongoDB BSON ObjectId mock
if (!class_exists('MongoDB\BSON\ObjectId')) {
    class ObjectIdMock
    {
        public $oid;

        public function __construct($id = null)
        {
            $this->oid = $id ?: uniqid();
        }

        public function __toString()
        {
            return $this->oid;
        }
    }
}
