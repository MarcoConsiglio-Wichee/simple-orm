<?php
namespace SimpleORM\Models;

use Models\Condition;
use DateTime;
use Models\Model;
use Models\Relation;
use PDO;
/**
 * Rappresenta il funzionamento che dovrebbe avere
 * una istanza di un Modello.
 */
abstract class AbstractModel implements Model
{
    /**
     * I dati del modello. Il formato dell'array avrà come indice
     * il nome delle colonne.
     *
     * @var array
     */
    protected $data = [];
    
    /**
     * La tabella associata al modello.
     */
    const TABLE = "";

    /**
     * Le colonne in lettura e scrittura della tabella.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Le colonne in sola lettura della tabella.
     *
     * @var array
     */
    protected $read_only_columns = [];

    /**
     * Le colonne di tipo DATETIME.
     * 
     * @var array
     */
    protected $datetime_columns = [];

    /**
     * Le colonne che hanno un valore di default
     * impostato al livello di database.
     * 
     * @var array
     */
    protected $default_value_columns = [];

    // /**
    //  * Le relazioni del modello.
    //  *
    //  * @var array[\Relation]
    //  */
    // protected $relations = [];

    /**
     *  Il formato delle date in italiano.
     */
    /*protected*/ const ITALIAN_DATETIME_FORMAT = "d/m/Y H:i:s";

    /**
     * Il formato delle date in un database MySQL.
     */
    /*protected*/ const MYSQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    /**
     * La connessione al database.
     *
     * @var PDO
     */
    protected static $connection;

    /**
     * Il numero di record per pagina da mostrare per le query
     * ad un modello.
     *
     * @var integer
     */
    public $record_per_page = 10;

    /**
     * Costruisce un qualsiasi modello.
     *
     * @param array $attributes Gli attributi che costituiscono lo 
     * stato iniziale del modello.
     */
    public function __construct(array $attributes = null)
    {
        $this::$connection = $GLOBALS["db"];
        
        // Inizializza tutte le proprietà.
        foreach (array_merge($this->columns, $this->read_only_columns) as $property) {
            $this->data[$property] = null;
        }
        // Se sono specificati degli attributi, verrano usati
        // per inizializzare il modello.
        if($attributes)
            foreach($attributes as $property => $value) {
                $this->data[$property] = $value;
            }
    }

    /**
     * Ottiene il valore di una proprietà del modello
     * specificata in $propertycolumns o $read_only_columns.
     *
     * @param string $property La proprietà di cui ottenere il valore
     * @return mixed il valore della proprietà specificata.
     */
    public function __get($property) 
    {
        if($this->exists($property)) {
            // Per le date, ci vuole un trattamento riservato.
            if($this->isDatetime($property)) {
                return $this->formatDateToItalian($this->data[$property]);
            } else 
                return $this->data[$property];
        }
    }

    /**
     * Imposta il valore di una proprietà del modello in sola 
     * lettura solo la prima volta, oppure le proprietà in
     * lettura e scrittura se sono dichiarate.
     *
     * @param string $property La proprietà da valorizzare.
     * @param mixed $value Il valore con cui valorizzare la proprietà.
     */
    public function __set($property, $value) 
    {
        if($this->exists($property)){
            $value = is_string($value) ? utf8_decode($value) : $value;
            // Scrive le proprietà in sola lettura solo la prima volta.
            if($this->isReadonly($property) && is_null($this->data[$property])) {
                if($this->isDatetime($property))
                    // Mi aspetto che il formato in arrivo sia AbstractModel::ITALIAN_DATETIME_FORMAT.
                    $this->data[$property] = $this->formatDateToMysql($value);
                else
                    $this->data[$property] = $value;
            } elseif($this->isWritable($property)) {
                if($this->isDatetime($property))
                    // Mi aspetto che il formato in arrivo sia AbstractModel::ITALIAN_DATETIME_FORMAT.
                    $this->data[$property] = $this->formatDateToMysql($value);
                else
                    $this->data[$property] = $value;
            }
        }
    }

    /**
     * Controlla se la proprietà è di tipo data e ora.
     *
     * @param string $property
     * @return boolean
     */
    private function isDatetime($property)
    {
        if(is_string($property))
            return in_array($property, $this->datetime_columns);
        else   
            return false;
    }

    /**
     * Controlla se la proprietà è in sola lettura.
     * 
     * @param string $property
     * @return boolean
     */
    private function isReadonly($property)
    {
        if(is_string($property))
            return in_array($property, $this->read_only_columns);
        else   
            return false;
    }

    /**
     * Controlla se la proprietà è in scrittura.
     *
     * @param string $property
     * @return boolean
     */
    private function isWritable($property)
    {
        if(is_string($property))
            return in_array($property, $this->columns);
        else
            return false;
    }

    /**
     * Controlla se la proprietà esiste.
     *
     * @param string $property
     * @return boolean
     */
    private function exists($property)
    {
        if(is_string($property))
            return in_array($property, array_merge($this->columns, $this->read_only_columns));
        else
            return false;
    }

    /**
     * Ottiene una istanza di modello padre
     * del modello corrente in uso.
     *
     * @param \Models\Relation $relation La relazione che si vuol navigare.
     * @param array $conditions Delle condizioni opzionali per una query
     * più complessa.
     * @return \Models\Model|null l'istanza di modello padre del modello corrente.
     */
    protected function findParent(Relation $relation, array $conditions = null) 
    {
        /**
         * @var \Models\AbstractModel
         */        
        $parent_model = $relation->parent_model;

        // Se ci sono ulteriori condizioni, le fonde insieme a quella
        // del vincolo di chiave esterna.
        if(isset($conditions) && $this->checkConditions($conditions)){
            // Prepara i parametri da passare alla query.
            $parameters = $conditions ? $this->getParametersValue($conditions) : [];
            // Prepara le condizioni della query.
            $constraint_condition = new Condition(
                $relation->primary_key_column,
                $relation->foreign_key_value
            );
            // Calcola le condizioni da aggiungere alla query (vedi classe \Models\Condition).
            $conditions = array_merge($constraint_condition, $conditions);
            $conditions_string = $conditions ? $this->getConditions($conditions) : "";
            return $parent_model::findWhere($conditions);
        } else {
            return $parent_model::find($relation->foreign_key_value);
        }
        
    }

    /**
     * Ottiene uno o più istante di modello figlie dell'istanza
     * corrente.
     *
     * @param Relation $relation La relazione che si vuol navigare.
     * @param int $page La pagina da restituire nel caso siano tanti record.
     * Se null, restituisce tutti i record.
     * @param array[Condtion] $conditions
     * @return \Models\Model|\Collection|null una o più istanze di modello 
     * figlie del modello corrente.
     * @throws PDOException
     */
    protected function findChildren(Relation $relation, $page = null, array $conditions = null) 
    {
        $child_model = $relation->child_model;
        $child_table = $child_model::TABLE;

        // Calcola la clausola LIMIT della query.
        $limit = "";
        if($page) {
            $r = $this->record_per_page;
            $i = ($page - 1) * $r;
            $limit = "LIMIT {$i}, {$r}";
        }
        
        if(isset($conditions) && $this->checkConditions($conditions)){
            // Calcola le condizioni da aggiungere alla query (vedi classe \Models\Models\Condition).
            $conditions_string = $conditions ? $this->getConditions($conditions) : "";
            // Prepara i parametri da passare alla query.
            $parameters = $conditions ? $this->getParametersValue($conditions) : [];
        }

        $sql = "SELECT * FROM `{$child_table}` WHERE `{$relation->foreign_key_column}` = ? {$conditions_string} {$limit}";
        /**
         * @var \PDOStatement
         */
        $query = $this::$connection->prepare($sql);

        // Esegue la query.
        if($query->execute(array_merge([$relation->primary_key_value], $parameters))) {
            $rows_count = $query->rowCount();
            // Se è specificato un limite posso usare fetchAll()
            if($limit) {
                if($rows = $query->fetchAll(PDO::FETCH_ASSOC)) {
                    if($rows_count > 1) {
                        $childs = [];
                        foreach ($rows as $row) {
                            $childs[] = new $child_model($row);
                        }
                        return new Collection($childs);
                    } else if ($rows_count = 1)
                        return new $child_model($rows[0]);
                } else return null;
            // Se non è specificato un limite utilizza fetch() per ragioni di performance.
            } else {
                ;
                if($rows_count > 1) {
                    $collection = new Collection;
                    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                        $collection[] = new $child_model($row);
                    }
                } else if($rows_count == 1)
                    return new $child_model($query->fetch(PDO::FETCH_ASSOC));
                else return null;
            }
        }
    }

    /**
     * Ottiene la stringa SQL delle condizioni della query.
     *
     * @param array[\Models\Models\Condition] $conditions Le condizioni da trasformare in SQL.
     * @return string la stringa SQL.
     */
    protected static function getConditions(array $conditions = null) 
    {
        if(self::checkConditions($conditions)) {
            $conditions_string = "";
            // Genera le condizioni in SQL.
            foreach ($conditions as $condition) {
                $conditions_string .= (string)$condition." AND ";
            }
            return rtrim($conditions_string, " AND ");
        } else return "";
    }

    /**
     * Ottiene i valori da passare ai parametri in una query preparata.
     *
     * @param array[\Models\Models\Condition] $conditions Le condizioni
     * @return array[mixed] la lista dei valori dei parametri oppure un array vuoto.
     */
    protected static function getParametersValue(array $conditions) 
    {
        // Prepara i parametri da passare alla query.
        $parameters = [];
        foreach ($conditions as $condition) {
            $parameters[] = $condition->getValue();
        }
        return $parameters;
    }

    /**
     * Controlla che le condizioni siano del tipo specificato dalla
     * classe Models\Condition.
     *
     * @param array[\Models\Models\Condition] $conditions Le condizioni da controllare
     * @return bool true se sono tutte classi Condition, false se almeno una è 
     * qualcos'altro.
     */
    protected static function checkConditions(array $conditions) 
    {
        $not_a_condition = false;
        foreach($conditions as $condition) {
            if(get_class($condition) != Condition::class) {
                $not_a_condition = true;
                break;
            }
        }

        return !$not_a_condition;
    }

    /**
     * Conta tutte i record della tabella associata al 
     * modello in uso.
     * @return int Il numero di record di tutta la tabella.
     */
    public static function count() 
    {
        $table = self::TABLE;
        self::$connection = $GLOBALS["db"];
        return self::$connection->query("SELECT count(1) FROM {$table}")->fetchColumn();
    }

    /**
     * Trasforma le date del database in formato italiano.
     *
     * @param string $datetime_string La data nel formato AbstractModel::MYSQL_DATETIME_FORMAT.
     * @return string La data e l'ora nel formato AbstractModel::ITALIAN_DATETIME_FORMAT.
     */
    protected function formatDateToItalian($datetime_string) 
    {
        if(isset($datetime_string) && is_string($datetime_string)) {
            // Prende la data in arrivo dal database...
            $datetime = DateTime::createFromFormat(AbstractModel::MYSQL_DATETIME_FORMAT, $datetime_string);
            // ... e la restituisce nel formato italiano.
            return $datetime->format(AbstractModel::ITALIAN_DATETIME_FORMAT);
        } else return null;
    }

    /**
     * Trasforma le date dal formato italiano a quello di MySQL.
     * @param string $datetime_string La data nel formato AbstractModel::ITALIAN_DATETIME_FORMAT.
     * @return string La data e l'ora nel formato AbstractModel::MYSQL_DATETIME_FORMAT.
     */
    protected function formatDateToMysql($datetime_string)
    {
        if(isset($datetime_string) && is_string($datetime_string)) {
            // Prende la data in arrivo dal database...
            $datetime = DateTime::createFromFormat(AbstractModel::ITALIAN_DATETIME_FORMAT, $datetime_string);
            // ... e la restituisce nel formato italiano.
            return $datetime->format(AbstractModel::MYSQL_DATETIME_FORMAT);
        } else return null;
    }

    /**
     * Salva il modello nel database.
     *
     * @return bool true se salvato con successo, false altrimenti.
     */
    function save()
    {
        $table = $this::TABLE;
        $read_only_columns = $this->read_only_columns;
        $columns = $this->columns;

        // Esclude la colonna ID.
        if (($key = array_search("id", $read_only_columns)) !== false) {
            unset($read_only_columns[$key]);
        }
        
        // Esclude le colonne che hanno null come valore e che ricevono un 
        // valore di default dal database.
        foreach($this->default_value_columns as $property) {
            if($this->$property === null) {
                if (($key = array_search($property, $read_only_columns)) !== false) {
                    unset($read_only_columns[$key]);
                } 
                if (($key = array_search($property, $columns)) !== false) {
                    unset($columns[$key]);
                }
            }
        } 
        // Prepara le colonne e i segnaposti e i parametri della query.
        $columns = array_merge($read_only_columns, $this->columns);
        $columns_and_placeholders = implode(" = ?, ", $columns)." = ?";
        $values = [];
        foreach($columns as $property) {
            if($this->isDatetime($property)) 
                $values[] = $this->formatDateToMysql($this->$property);
            else
                $values[] = $this->$property;
        }
        $placeholders = rtrim(str_repeat('?, ', count($values)), ', ') ;

        // Se ha un ID, esegue un UPDATE, altrimenti un INSERT.
        if($this->id) {
            // Prepara la query.
            $sql = "UPDATE {$table} SET {$columns_and_placeholders} WHERE id = ?";
            $query = $this::$connection->prepare($sql);
            echo $values[0];
            $values[] = $this->id;
            return $query->execute($values);
        } else {
            // Prepara la query.
            $columns_string = implode(", ", $columns);
            $sql = "INSERT INTO {$table} ({$columns_string}) VALUES ({$placeholders})";
            $query = $this::$connection->prepare($sql);
            $result = $query->execute($values);
            $this->id = (int)$this::$connection->lastInsertId();
            return $result;
        }
    }

    /**
     * Cerca un modello tramite il suo ID.
     *
     * @param int $id
     * @return \Models\Model|null il modello che corrisponde all'ID,
     * altrimenti null.
     */
    public static function find($id)
    {
        if(is_integer($id)) {
            $model = get_called_class();
            $table = $model::TABLE;
            $sql = "SELECT * FROM {$table} WHERE id = ?";
            self::$connection = $GLOBALS["db"];
            $query = self::$connection->prepare($sql);
            if($query->execute([$id]) && $query->rowCount() > 0) {
                return new $model($query->fetch(PDO::FETCH_ASSOC));
            } else return null;
        } else return null;
    }

    /**
     * Cerca dei modelli con una o più condizioni coniugate
     * con l'operatore logico AND.
     *
     * @param array[\Models\Models\Condition] $conditions
     * @return \Models\Model|\Collection|null uno o più modelli
     * che rispettano le condizioni, altrimenti null.
     */
    public static function findWhere(array $conditions, $page = null) {
        /**
         * @var PDO
         */
        self::$connection = $GLOBALS["db"];
        $model_class = get_called_class();
        $table = $model_class::TABLE;
        // Calcola la clausola WHERE delle query.
        $conditions_string = self::getConditions($conditions);
        // Calcola la clausola LIMIT della query.
        $limit = "";
        if($page) {
            $r = self::$record_per_page;
            $i = ($page - 1) * $r;
            $limit = "LIMIT {$i}, {$r}";
        }
        // Prepara la query.
        $sql = "SELECT * FROM {$table} WHERE {$conditions_string} {$limit}";
        $query = self::$connection->prepare($sql);
        $parameters = self::getParametersValue($conditions);
        // Esegue la query.
        if($query->execute($parameters)) {
            $rows_count = $query->rowCount();
            // Se è specificato un limite posso usare fetchAll()
            if($limit) {
                if($rows = $query->fetchAll(PDO::FETCH_ASSOC)) {
                    if($rows_count > 1) {
                        $models = [];
                        foreach ($rows as $row) {
                            $models[] = new $model_class($row);
                        }
                        return new Collection($models);
                    } else if ($rows_count == 1)
                        return new $model_class($rows[0]);
                } else return null;
            // Se non è specificato un limite utilizza fetch() per ragioni di performance.
            } else {
                if($rows_count > 1) {
                    $collection = new Collection;
                    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                        $collection[] = new $model_class($row);
                    }
                } else if($rows_count == 1)
                    return new $model_class($query->fetch(PDO::FETCH_ASSOC));
                else return null;
            }
        } else return false;
    }
}