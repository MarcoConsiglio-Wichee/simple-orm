<?php
namespace SimpleORM\Tests\Unit;
use SimpleORM\Tests\TestCase;
use SimpleORM\Models\Database;

class DatabaseTest extends TestCase
{
    /**
     * @test
     * @testdox "La connessione al database è un oggetto unico."
     */
    public function database_is_singleton()
    {
        require_once "config/database.php";
        $this->assertSame(Database::getConnection(), Database::getConnection());
    }
}