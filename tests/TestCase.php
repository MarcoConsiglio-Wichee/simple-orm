<?php
namespace SimpleORM\Tests;

use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Generatore di valori casuali
     *
     * @var \Faker\Generator
     */
    protected $faker;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->faker = FakerFactory::create();
    }
}