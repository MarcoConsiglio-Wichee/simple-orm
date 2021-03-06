<?php
namespace SimpleORM\Models;
/**
 * Rappresenta una condizione di una query.
 */
class Condition {
    /**
     * Il campo su cui applicare la condizione.
     *
     * @var string
     */
    protected $field = "";

    /**
     * L'operatore di comparazione della condizione.
     *
     * @var string
     */
    protected $operator = "=";

    /**
     * Il valore da utilizzare nella comparazione che compone
     * la condizione.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Trasforma la condizione in una stringa.
     *
     * @return string
     */
    public function __toString() {
        return "{$this->field} {$this->operator} ?";
    }

    /**
     * Costruisce una condizione.
     *
     * @param string $field Il campo da usare nella comparazione.
     * @param mixed $value Il valore da usare nella comparazione.
     * @param string $operator L'operatore della comparazione.
     */
    public function __construct($field, $value, $operator = "=") {
        // Se il valore della comparazione è verso il valore NULL
        // richiede un trattamento speciale.
        if($value === null) {
            switch ($operator) {
                case '=':
                    $this->operator = "IS";
                    break;
                case '!=':
                case '<>':
                    $this->operator = "IS NOT";
                    break;
            }
            $this->field = $field;
            $this->value = $value;
        } else {
            $this->field = $field;
            $this->value = $value;
            $this->operator = $operator;
        }
    }

    /**
     * Ottiene il valore da utilizzare nella comparazione della condizione.
     *
     * @return mixed il valore del parametro da usare nella comparazione.
     */
    public function getValue() {
        return $this->value;
    }
}