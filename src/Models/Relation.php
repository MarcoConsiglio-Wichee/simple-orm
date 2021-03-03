<?php
namespace SimpleORM\Models;
/**
 * Rappresenta una relazione tra due tabelle o modelli.
 * 
 * @property-read string $foreign_key_column La colonna che funge da chiave esterna per il
 * lato debole della relazione.
 * @property-read string $primary_key_column La colonna che funge da chiave primaria per il
 * lato forte della relazione.
 * @property-read string $child_model La classe del modello del lato debole della relazione.
 * @property-read string $parent_model La classe del modello del lato forte della relazione.
 */
class Relation
{
    /**
     * La colonna che funge da chiave esterna per il
     * lato debole della relazione.
     *
     * @var string
     */
    protected $foreign_key_column = "";

    /**
     * La colonna che funge da chiave primaria per il
     * lato forte della relazione.
     *
     * @var string
     */
    protected $primary_key_column = "";

    /**
     * La tabella del lato debole della relazione.
     *
     * @var string
     */
    protected $child_model = "";

    /**
     * La tabella del lato forte della relazione.
     *
     * @var string
     */
    protected $parent_model = "";

    /**
     * Il valore della chiave esterna.
     *
     * @var mixed
     */
    public $foreign_key_value = null;

    /**
     * Il valore della chiave primaria.
     *
     * @var mixed
     */
    public $primary_key_value = null;

    public function __construct(
        $child_model,
        $foreign_key_column,
        $parent_model,
        $primary_key_column = "id"
    ) {
        $this->child_model = class_exists($child_model) ? $child_model : null;
        $this->foreign_key_column = is_string($foreign_key_column) ? $foreign_key_column : null;
        $this->parent_model = class_exists($parent_model) ? $parent_model : null;
        $this->primary_key_column = is_string($primary_key_column) ? $primary_key_column : null;
    }

    public function __get($property) {
        $properties = [
            "child_model",
            "foreign_key_column",
            "parent_model",
            "primary_key_column"
        ];
        if(is_string($property) && in_array($property, $properties))
            return $this->$property;
    }
}
