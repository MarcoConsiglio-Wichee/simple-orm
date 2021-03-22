<?php
namespace SimpleORM\Models;

use SimpleORM\Models\Condition;
use DateTime;
use SimpleORM\Models\Model;
use SimpleORM\Models\Relation;
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
     * Ordinamento ascendente.
     */
    const ASC = 0;

    /**
     * Ordinamento discendente.
     */
    const DESC = 1;

    /**
     * Le colonne in lettura e scrittura della tabella.
     *
     * @var array
     */
    protected static $columns = [];

    /**
     * Le colonne in sola lettura della tabella.
     *
     * @var array
     */
    protected static $read_only_columns = [];

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


    /**
     *  Il formato della data e ora in italiano.
     */
    /*protected*/ const ITALIAN_DATETIME_FORMAT = "d/m/Y H:i:s";

    /**
     *  Il formato della data e ora in italiano.
     */
    /*protected*/ const ITALIAN_DATE_FORMAT = "d/m/Y";
    
    /**
     * Il formato della data e ora in un database MySQL.
     */
    /*protected*/ const MYSQL_DATETIME_FORMAT = "Y-m-d H:i:s";

    /**
     * Il formato della data in un database MySQL.
     */
    /*protected*/ const MYSQL_DATE_FORMAT = "Y-m-d";
    
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
    public static $record_per_page = 10;

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
        foreach (static::getAllColumns() as $property) {
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
     * specificata in $columns o $read_only_columns.
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
            return in_array($property, static::$read_only_columns);
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
            return in_array($property, static::$columns);
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
            return in_array($property, static::getAllColumns());
        else
            return false;
    }

    /**
     * Ottiene una istanza di modello padre
     * del modello corrente in uso.
     *
     * @param \SimpleORM\Models\Relation $relation La relazione che si vuol navigare.
     * @param array $conditions Delle condizioni opzionali per una query
     * più complessa.
     * @return \SimpleORM\Models\Model|null l'istanza di modello padre del modello corrente.
     */
    protected function findParent(Relation $relation, \Traversable $conditions = null) 
    {
        /**
         * @var \SimpleORM\Models\AbstractModel
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
            // Calcola le condizioni da aggiungere alla query (vedi classe \SimpleORM\Models\Condition).
            $conditions = new Collection(array_merge($constraint_condition, $conditions));
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
     * @param array[\SimpleORM\Condtion] $conditions
     * @param array $group_by Un array di colonne per la clausola GROUP BY.
     * @param array $aggregate_columns Un array associativo con indici le colonne e con valori
     * la funzione aggregata (ad es. ["colonna_1" => "max"])
     * @
     * @return \SimpleORM\Models\Model|\SimpleORM\Collection una o più istanze di modello 
     * figlie del modello corrente.
     * @throws PDOException
     */
    protected function findChildren(
        Relation $relation, 
        $page = null, 
        array $conditions = null, 
        $order = AbstractModel::DESC, 
        $order_column = null, 
        array $group_by = null, 
        array $aggregate_columns = null) 
    {
        $child_model = $relation->child_model;
        $child_table = $child_model::TABLE;

        // Calcola la clausola LIMIT della query.
        $limit = $page ? $this->getLimitClause($page) : "";
        // Calcola la clausola ORDER BY
        $order_by = is_string($order_column) ? $this->getOrderClause($order, $order_column) : $this->getOrderClause($order);
        // Calcola la clausola GROUP BY
        $group_by = is_array($group_by) ? $this->getGroupByClause($group_by) : "";
        // Calcola la clausola per le colonne aggregate.
        $aggregate_columns_string = $aggregate_columns ? $this->getAggregateColumns($aggregate_columns, $child_model) : "";

        // Prepara la query.
        $parameters = [];
        $conditions_string = "";
        if(isset($conditions) && $this->checkConditions($conditions)){
            // Calcola le condizioni da aggiungere alla query (vedi classe \SimpleORM\Models\Condition).
            $conditions_string = $conditions ? "AND ".$this->getConditions($conditions) : "";
            // Prepara i parametri da passare alla query.
            $parameters = $conditions ? $this->getParametersValue($conditions) : [];
        }
        if(!$aggregate_columns_string)
            $sql = "SELECT * FROM `{$child_table}` WHERE `{$relation->foreign_key_column}` = ? {$conditions_string} {$group_by} {$order_by} {$limit}";
        else
            $sql = "SELECT {$aggregate_columns_string} FROM `{$child_table}` WHERE `{$relation->foreign_key_column}` = ? {$conditions_string} {$group_by} {$order_by} {$limit}";

        /**
         * @var \PDOStatement
         */
        $query = $this::$connection->prepare($sql);
        // Esegue la query.
        if($query->execute(array_merge([$relation->foreign_key_value], $parameters))) {
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
                } else return new Collection; // Collezione vuota
            // Se non è specificato un limite utilizza fetch() per ragioni di performance.
            } else {
                if($rows_count > 1) {
                    $collection = new Collection;
                    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                        $collection[] = new $child_model($row);
                    }
                    return $collection;
                } else if($rows_count == 1)
                    return new $child_model($query->fetch(PDO::FETCH_ASSOC));
                else return new Collection; // Collezione vuota
            }
        }
    }

    /**
     * Ottiene la stringa SQL delle condizioni della query.
     *
     * @param array[\SimpleORM\Models\Condition] $conditions Le condizioni da trasformare in SQL.
     * @return string la stringa SQL.
     */
    protected static function getConditions(\Traversable $conditions = null) 
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
     * @param array[\SimpleORM\Models\Condition] $conditions Le condizioni
     * @return array[mixed] la lista dei valori dei parametri oppure un array vuoto.
     */
    protected static function getParametersValue(\Traversable $conditions) 
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
     * classe SimpleORM\Models\Condition.
     *
     * @param array[\SimpleORM\Models\Condition] $conditions Le condizioni da controllare
     * @return bool true se sono tutte classi Condition, false se almeno una è 
     * qualcos'altro.
     */
    protected static function checkConditions(\Traversable $conditions) 
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
        $model_class = static::class;
        $table = $model_class::TABLE;
        /**
         * @var \PDO
         */
        self::$connection = $GLOBALS["db"];
        return (int)self::$connection->query("SELECT count(1) FROM {$table}")->fetchColumn();
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
            if(!$datetime) {
                $date = DateTime::createFromFormat(AbstractModel::MYSQL_DATE_FORMAT, $datetime_string);
            }
            // ... e la restituisce nel formato italiano.
            $datetime_string = $datetime ? 
                $datetime->format(AbstractModel::ITALIAN_DATETIME_FORMAT) : 
                $date->format(AbstractModel::ITALIAN_DATE_FORMAT);
            if(!$datetime_string) {
                $date_string = $datetime->format(AbstractModel::ITALIAN_DATE_FORMAT);
            }
            return $datetime_string ? $datetime_string : $date_string;
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
        $read_only_columns = static::$read_only_columns;
        $columns = static::$columns;

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
        $columns = array_merge($read_only_columns, static::$columns);
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
            $this->id = $result ? (int)$this::$connection->lastInsertId() : null;
            return $result;
        }
    }

    /**
     * Salva un modello se non esiste già in database.
     * @param bool $strict Se vero, include nelle condizioni di ricerca
     * anche i valori null del modello.
     * @return \SimpleORM\Models\Model l'istanza salvata oppure quella che c'è già in database.
     */
    public function saveIfNotExists($strict = false) 
    {
        $conditions = new Collection;
        foreach (static::getAllColumns() as $column) {
            if(!$strict && $this->$column != null) {
                $condition = new Condition($column, $this->$column);
                $conditions[] = $condition;
            }
        }
        $record_found = $this->findWhere($conditions);
        if(!$record_found){
            $this->save();
            return $this;
        } else return $record_found;
    }

    /**
     * Cerca un modello tramite il suo ID.
     *
     * @param int $id
     * @return \SimpleORM\Models\Model|null il modello che corrisponde all'ID,
     * altrimenti null.
     */
    public static function find($id)
    {
        if(is_integer($id)) {
            $model = static::class;
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
     * @param array[\SimpleORM\Models\Models\Condition] $conditions Le condizioni della query.
     * @param int $page Il numero della pagina da selezionare. Se minore di zero, diventa la prima
     * pagina, se superiore al massimo delle pagine, diventa l'ultima pagina.
     * @return \SimpleORM\Models\Model|\SimpleORM\Collection|null uno o più modelli
     * che rispettano le condizioni, altrimenti null.
     */
    public static function findWhere(\Traversable $conditions, $page = null) {
        /**
         * @var PDO
         */
        self::$connection = $GLOBALS["db"];
        $model_class = static::class;
        $table = $model_class::TABLE;
        // Calcola la clausola WHERE delle query.
        $conditions_string = self::getConditions($conditions);
        // Calcola la clausola LIMIT della query.
        $limit = $page ? self::getLimitClause($page) : "";
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

    /**
     * Cerca dei modelli con una o più condizioni coniugate
     * con l'operatore logico AND, ma restituisce solo il primo.
     *
     * @param \Traversable $conditions Le condizioni della query.
     * @return \SimpleORM\Models\Model|null
     */
    public static function findFirstWhere(\Traversable $conditions)
    {
        $result = self::findWhere($conditions, 1);
        if(!$result)
            return null;
        if($result instanceof Model)
            return $result;
        if($result instanceof Collection)
            return $result->first();
    }

    /**
     * Restituisce una pagina di tutte le istanze del modello.
     *
     * @param integer $page La pagina da selezionare.
     * @param integer $order AbstractModel::ASC per un ordinamento ascendente,
     * AbstractModel::DESC per un ordinamento discendente.
     * @param string $order_columns La colonna per cui ordinare i risultati.
     * @return \SimpleORM\Models\Collection|null Una collezione di modelli.
     */
    public static function all($page = null, $order = AbstractModel::DESC, $order_column = null)
    {
        $model_class = static::class;
        $table = $model_class::TABLE;
        // Calcola la clausola LIMIT della query.
        $limit = $page ? self::getLimitClause($page, self::$record_per_page) : "";
        // Calcola la clausola ORDER BY
        $order_by = is_string($order_column) ? self::getOrderClause($order, $order_column) : self::getOrderClause($order);
        // Prepara la query.
        $sql = "SELECT * FROM {$table} {$order_by} {$limit}";
        self::$connection = $GLOBALS["db"];
        $query = self::$connection->prepare($sql);
        // Esegue la query.
        if($query->execute()) {
            if($rows = $query->fetchAll(PDO::FETCH_ASSOC)) {
                $models = [];
                foreach ($rows as $row) {
                    $models[] = new $model_class($row);
                }
                return new Collection($models);
            } else return null;
        } else return null;
    }

    /**
     * Produce una clausola LIMIT per una query SQL.
     * @param int $page La pagina da selezionare
     * @param int $record_per_page  Il numero di righe per pagina
     * @return string la clausola LIMIT.
     */
    private static function getLimitClause($page)
    {
        $page = is_integer($page) ? $page : 1;
        $record_per_page = static::$record_per_page;
        $total_pages = static::getTotalPages();
        $page = $page <= $total_pages ? $page : $total_pages;
        $offset = ($page - 1) * $record_per_page;
        $limit = "LIMIT {$offset}, {$record_per_page}";
        return $limit;
    }

    /**
     * Produce una clausola ORDER BY per una query SQL. 
     *
     * @param int $order AbstractModel::DESC per un ordinamento discendente,
     * AbstractModel::ASC per un ordinamento ascendente.
     * @param string $order_column La colonna secondo cui ordinare i risultati.
     * @return string la clausola ORDER BY.
     */
    private static function getOrderClause($order = AbstractModel::DESC, $order_column = "id")
    {
        $column = $order_column;
        switch ($order) {
            case AbstractModel::ASC:
                $order_by = "ORDER BY {$column} ASC";
                break;
            case AbstractModel::DESC:
                $order_by = "ORDER BY {$column} DESC";
                break;
        }
        return $order_by;
    }

    /**
     * Produce una clausola GROUP BY per una query SQL.
     *
     * @param array $group_by La lista delle colonne da raggruppare.
     * @return string|null La clausola GROUP BY.
     */
    private static function getGroupByClause(array $group_by) 
    {
        if(!empty($group_by))
            return "GROUP BY ".implode(", ", $group_by);
        else
            return null;
    }

    /**
     * Produce una stringa di colonne dove alcune possono essere
     * i parametri di una funzione aggregata.
     *
     * @param array $aggregate_columns Un array che ha come indice il nome della colonna
     * e come valore il nome della funzione aggregata (ad es. ["colonna_1" => "max"]).
     * @param static $model_class La classe di cui ottenere la lista delle colonne.
     * @return string Una stringa da utilizzare in una query con le colonne agreggate.
     */
    private static function getAggregateColumns(array $aggregate_columns, $model_class)
    {
        $all_columns = [];
        foreach (array_merge($model_class::$read_only_columns, $model_class::$columns) as $column) {
            if(isset($aggregate_columns[$column]))
                $all_columns[] = $aggregate_columns[$column]."(".$column.") as $column";
            else
                $all_columns[] = $column;
        }
        return implode(", ", $all_columns);
    }

    /**
     * Conta il numero delle pagine selezionabili dal database
     * considerando $record_per_page di una selezione della tabella
     * senza condizioni.
     * @return int Il totale delle pagine.
     */
    public static function getTotalPages()
    {
        return (int)ceil(static::count() / static::$record_per_page);
    }

    /**
     * Trasforma gli attributi del modello in un array.
     *
     * @param array $properties Le proprietà da trasformare. Se mancanti, 
     * utilizza quelle già dichiarate nella classe che implementa 
     * l'interfaccia \SimpleORM\Model;
     * @return array Gli attributi del modello.
     */
    public function attributesToArray($properties = null)
    {
        $properties = !$properties ? $this->getAllColumns() : $properties;
        $attributes = [];
        foreach($properties as $property) {
            $attributes[$property] = $this->$property;
        }
        return $attributes;
    }

    /**
     * Ricava tutte le colonne della tabella a cui
     * è associato il modello.
     *
     * @return array I nomi degli attributi del modello.
     */
    public static function getAllColumns()
    {
        return array_merge(static::$read_only_columns, static::$columns);
    }
}