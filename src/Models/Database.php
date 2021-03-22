<?php
namespace SimpleORM\Models;

use Exception;
use PDO;

class Database
{
    /**
     * Qui risiedono l'istanza di PDO e le
     * sue eventuali estensioni.
     *
     * @var array
     */
    private static $pdo_instances = [];

    /**
     * Non si può istanziare direttamente la classe Database.
     */
    protected function __construct() {}

    /**
     * Una istanza di Database non deve essere clonata.
     *
     * @return void
     */
    protected function __clone() {}

    /**
     * Una istanza di Database non deve essere deserializzata.
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    /**
     * Restituisce la connessione al database.
     * @return \PDO la connessione configurata con
     * le costanti DB_HOST, DB_USER, DB_PASSWORD, DB_PORT.
     */
    public static function getConnection()
    {
        // Default connection.
        $class = PDO::class;
        if(!isset(self::$pdo_instances[$class])) {
            switch (DB_DRIVER) {
                case 'mysql':
                    # code...
                    break;
                case 'sqlite':
                    self::$pdo_instances[$class] = new PDO('sqlite:'.DB_HOST);
            }
        }
        return self::$pdo_instances[$class];
    }
}