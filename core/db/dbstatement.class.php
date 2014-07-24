<?php
/**
 * Rappresenta un prepared statement del DB
 * Non è inteso per essere usato dagli utenti, ma solo passato come parametro ai
 * metodi dei driver del Database
 * @package \GDRCD\core\db
 * @author Stefano "leoblacksoul" Campanella <programming@rel.to>
 */
abstract class DBStatement
{
    protected $statement;

    public function __construct($stmt)
    {
        if (!empty($stmt)) {
            $this->statement=$stmt;
        }
        else {
            throw new DBException("Errore nella preparazione delle istruzioni del database",0,'Passato un argomento vuoto al posto di uno statement a DBStatement');
        }
    }

    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Annulla di dati legati allo statement corrente per poter rieseguire la
     * query con altri dati
     */
    abstract public function resetStatement();
}
