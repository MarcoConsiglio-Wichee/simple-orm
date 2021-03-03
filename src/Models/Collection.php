<?php
namespace SimpleORM\Models;
use Iterator, ArrayAccess, Countable;

/**
 * Rappresenta una collezione di modelli.
 * @see \SplObjectStorage per implementare una struttura dati che come
 * indice ha degli oggetti, da sostituire alla definizione delle interfacce
 * implementate nella classe e lasciando i metodi implementati qui, così come sono.
 */
class Collection implements Iterator, ArrayAccess, Countable {
    /**
     * La collezione
     *
     * @var array
     */
    protected $collection = [];

    /**
     * La posizione dell'indice nella collezione.
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * Costruisce una collezione.
     *
     * @param array $collection L'array da utilizzare per costruire una collezione.
     */
    public function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    /**
     * Restituisce l'elemento identificato dalla posizione corrente
     * dell'indice.
     *
     * @return mixed L'elemento corrente.
     */
    public function current() 
    {
        return $this->collection[$this->position];
    }

    /**
     * Riporta l'indice all'elemento iniziale della
     * collezione.
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Restituisce la chiave della posizione corrente.
     *
     * @return mixed la chiave della posizione corrente.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Sposta l'indice sull'elemento successivo.
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Indica se la posizione corrente esiste ed ha
     * un elemento.
     *
     * @return void
     */
    public function valid()
    {
        return isset($this->collection[$this->position]);
    }

    /**
     * Indica se la posizione specificata esiste ed ha un
     * elemento.
     *
     * @param mixed $offset L'indice della posizione da controllare.
     * @return bool true se la posizione indica un elemento esistente, false altrimenti.
     */
    public function offsetExists($offset)
    {
        return isset($this->collection[$offset]);
    }

    /**
     * Imposta il valore di un elemento in una posizione
     * specificata.
     *
     * @param mixed $offset La posizione
     * @param mixed $value  Il valore dell'elemento.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if(is_null($offset))
            $this->collection[] = $value;
        else
            $this->collection[$offset] = $value;
    }

    /**
     * Restituisce l'elemento alla posizione speficata.
     *
     * @param mixed $offset La posizione dell'elemento da ottenere.
     * @return mixed L'elemento indicato dalla posizione $offset.
     */
    public function offsetGet($offset) 
    {
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }

    /**
     * Elimina un elemento da una posizione.
     *
     * @param mixed $offset La posizione dell'elemento che si vuole eliminare.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    } 

    /**
     * Conta gli elementi della collezione
     *  
     * @return int il numero di elementi presenti nella collezione
     */
    public function count()
    {
        return count($this->collection);
    }
}