<?php
/**
 * Interfaccia per tutti i Driver dei Database
 * Definisce i metodi per poter venire utilizzato dal layer di astrazione del DB
 * @package \GDRCD\core\driver
 */
interface DatabaseDriver
{
    /**
     * Crea la connessione con il DB
     * @param (string) $host: l'host su cui si trovare il server del DB
     * @param (string) $user: il nome utente da usare per identificarsi sul DB
     * @param (string) $pass: la password da usare per autenticarsi sul DB
     * @param (string) $database: il nome del database a cui connettersi
     * @param (array) $additional: configurazioni addizionali da passare al driver
     */
    public function __construct($host, $user, $pass, $database,$additional);

    /**
     * Esegue una query sul DB
     * @param (string) $sql: la query da eseguire
     * @param (const) $mode: Il parametro $mode permette di stabilire in che modo i dati vengono ritornati
     *                       dal metodo, mediante 4 costanti predefinite:
     *                       . GDRCD_FETCH_ASSOC: ritorna i dati in un array associativo
     *                       . GDRCD_FETCH_NUM: ritorna i dati ordinati numericamente
     *                       . GDRCD_FETCH_BOTH: li ritorna sia associativi, sia con indicizzazione numerica
     *                       . GDRCD_FETCH_OBJ: ritorna i dati come oggetto
     * @return (oboject|array|bool) se $one_shot==true viene ritornato direttamente il
     *            primo record del resultset nel formato specificato
     *            da $mode. Altrimenti viene ritornato un oggetto DBResult
     */
    public function query($sql, $one_shot=false, $mode=GDRCD_FETCH_ASSOC);

    /**
     * Esegue la query come un prepared statement
     * @param (string) $sql: la query da eseguire, adeguatamente parametrizzata
     * @param (array) $parameters: i parametri da sostituire nella query
     * @param (const) $mode: la modalità con cui eseguire la query @see self::query()
     */
    public function stmtQuery($sql, $parameters, $mode=GDRCD_FETCH_ASSOC);

    /**
     * Unknown
     */
    public function prepare($sql);
    public function bind($placeholder, $data, $filter);
    public function exec($mode);

    /**
     * Ritorna l'ID creato dall'ultima query INSERT
     */
    public function getLastID();

    /**
     * Transactional Statements
     */

    /**
     * Inizia una transazione
     */
    public function startTransaction();

    /**
     * Committa una Transazione con successo
     */
    public function commitTransaction();

    /**
     * Annulla tutte le azioni fatte durante la transazione attuale, terminandola
     */
    public function rollbackTransaction();

    /**
     * @return true se c'è una transazione attiva
     */
    public function isTransactionActive();

    public function close();
}