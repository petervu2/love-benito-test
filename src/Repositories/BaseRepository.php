<?php
namespace App\Repositories;

class BaseRepository {
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getContainer() {
        return $this->container;
    }
}
