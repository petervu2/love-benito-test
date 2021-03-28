<?php
namespace App\Jobs;
use Slim\Factory\AppFactory;
use Psr\Container\ContainerInterface;

class BaseJob {
    private $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer(){
        return $this->container;
    }
}
