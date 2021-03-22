<?php
namespace SimpleORM\Models;

use ArrayIterator;
use Iterator, ArrayAccess, Countable;

/**
 * Rappresenta una collezione di modelli.
 * @see \SplObjectStorage per implementare una struttura dati che come
 * indice ha degli oggetti, da sostituire alla definizione delle interfacce
 * implementate nella classe e lasciando i metodi implementati qui, così come sono.
 */
class Collection extends ArrayIterator {
    /**
     * Costruisce una collezione.
     *
     * @param array $collection L'array da utilizzare per costruire una collezione.
     */
    public function __construct(array $collection = [])
    {
        parent::__construct($collection, ArrayIterator::STD_PROP_LIST);
    }

    /**
     * Trasforma gli attributi di ogni oggetto Model della in un array.
     *
     * @return array Gli attributi e i valori della collezione.
     */
    public function attributesToArray()
    {
        $elements = [];
        $collection_attributes = [];
        if($this->count() > 0) {
            $this->seek(0);
            $first_element = $this->current();
            /**
             * @var \SimpleORM\Models\AbstractModel $model
             */
            $model = get_class($first_element);
            $columns = $model::getAllColumns();
            $collection = $this->getArrayCopy();
            foreach ($collection as $element) {
                /**
                 * @var \SimpleORM\Models\AbstractModel $element
                 */
                $collection_attributes[] = $element->attributesToArray($columns);
            }
            return $collection_attributes;
        } else return [];
    }

    /**
     * Estrae una pagina di elementi dalla collezione.
     *
     * @param int $number Il numero della pagina da selezionare, in base 1.
     * @param int $lenght Il numero di elementi che deve contenere ciascuna pagina.
     * @return \SimpleORM\Models\Collection contenente gli elementi della pagina selezionata.
     */
    public function getPage($number, $lenght)
    {
        $selected_page = [];
        $total_pages = ceil($this->count() / $lenght);
        if($number < 0) $number = 1;
        if($number > $total_pages) $number = $total_pages;
        $offset = ($number - 1) * $lenght;
        $this->seek($offset);
        while($this->valid()) {
            if($this->key() <= $offset + $lenght - 1) {
                $selected_page[] = $this->current();
                $this->next();
                continue;
            } else break;
        }
        return new Collection($selected_page, ArrayIterator::STD_PROP_LIST);
    }

    /**
     * Restituisce il primo elemento della collezione
     *
     * @return mixed
     */             
    public function first()
    {
       $this->seek(0);
       return $this->current();
    }

    /**
     * Restituisce l'ultimo elemento della collezione
     *
     * @return void
     */
    public function last()
    {
        $this->seek($this->count() - 1);
        return $this->current();
    }
}