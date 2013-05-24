<?php
/**
 * Interfaccia per tutti i Driver dei Database
 * Definisce i metodi per poter venire utilizzato dal layer di astrazione del DB
 * @package \GDRCD\core\driver
 */
interface DatabaseDriver
{
    const FETCH_ASSOC   = 1;
    const FETCH_NUM     = 2;
    const FETCH_BOTH    = 3;
    const FETCH_OBJ     = 4;

    /**
     * Crea la connessione con il DB 
     * @param $host: l'host su cui si trovare il server del DB
     * @param $user: il nome utente da usare per identificarsi sul DB
     * @param $pass: la password da usare per autenticarsi sul DB
     * @param $database: il nome del database a cui connettersi
     */
    public function __construct($host, $user, $pass, $database);
    
    /**
     * Esegue una query sul DB
     * @param $sql: la query da eseguire
     * @param $mode: specifica la modalità con cui eseguire la query
     */
    public function query($sql, $mode);
    
    /**
     * Esegue la query come un prepared statement
     * @param $sql: la query da eseguire, adeguatamente parametrizzata
     * @param $parameters: i parametri da sostituire nella query
     * @param $mode: la modalità con cui eseguire la query @see metodo query
     */
    public function stmtQuery($sql, $parameters, $mode);
    
    public function prepare($sql);
    public function bind($placeholder, $data, $filter);
    public function exec($mode);

    /**
     * Ritorna la descrizione dell'ultimo errore generato
     */
    public function lastError();
    
    public function __destruct();
}