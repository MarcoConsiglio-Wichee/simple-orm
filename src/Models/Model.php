<?php
namespace SimpleORM\Models;

interface Model {
    /**
     * Cerca un modello tramite il suo ID.
     *
     * @param int $id
     * @return \SimpleORM\Model|null il modello che corrisponde all'ID,
     * altrimenti null.
     */
    static function find($id);

    /**
     * Cerca dei modelli con una o più condizioni coniugate
     * con l'operatore logico AND.
     *
     * @param array[\SimpleORM\Condition] $conditions
     * @return \SimpleORM\Model|\SimpleORM\Collection|null uno o più modelli
     * che rispettano le condizioni, altrimenti null.
     */
    static function findWhere(\Traversable $conditions);

    /**
     * Cerca dei modelli con un insieme di ID.
     *
     * @param array $ids Gli ID dei modelli da cercare
     * @return \SimpleORM\Model|\SimpleORM\Collection|null i modelli che corrispondono all'insieme
     * di ID.
     */
    // static function findMany(array $ids);

    /**
     * Conta tutte i record della tabella associata al 
     * modello in uso.
     * @return int Il numero di record di tutta la tabella.
     */
    static function count();

    /**
     * Salva il modello nel database.
     *
     * @return bool true se salvato con successo, false altrimenti.
     */
    function save();

    /**
     * Salva una collezione di modelli nel database.
     *
     * @return bool true se tutti sono stati salvati, altrimenti false.
     */
    // static function saveMany(Collection $collection);

    /**
     * Restituisce una pagina di tutte le istanze del modello.
     *
     * @param integer $page La pagina da selezionare.
     * @return \Priveasy\Models\Collection|null
     */
    static function all($page = 1);

    /**
     * Trasforma gli attributi del modello in un array.
     *
     * @param array $properties Le proprietà da trasformare. Se mancanti, 
     * utilizza quelle già dichiarate nella classe che implementa 
     * l'interfaccia \Priveasy\Model;
     * @return array Gli attributi del modello.
     */
    function attributesToArray($properties = null);

    /**
     * Ricava tutte le colonne della tabella a cui
     * è associato il modello.
     *
     * @return array I nomi degli attributi del modello.
     */
    static function getAllColumns();
}