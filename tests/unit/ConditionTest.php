<?php
namespace SimpleORM\Tests\Unit;
use SimpleORM\Tests\TestCase;
use SimpleORM\Models\Condition;
use SimpleORM\Models\Database;

class ConditionTest extends TestCase
{
    /**
     * @test
     * @testdox "La connessione al database è un oggetto unico."
     */
    public function special_condition()
    {
        $this->markTestIncomplete();
        $condition = new Condition(null, null);
    }
}