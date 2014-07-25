<?php
/**
 * Interfaccia per tutti i Driver dei Database
 * Definisce i metodi per poter usare la classe dal layer di astrazione del DB
 * @package \GDRCD\core\db
 * @author Stefano "leoblacksoul" Campanella <programming@rel.to>
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
    public function query($sql, $one_shot=false, $mode=DB::FETCH_ASSOC);

    /**
     * Esegue l'escape di un generico parametro da inserire in una query SQL
     */
    public function escape($param);

    /**
     * Ritorna l'ID creato dall'ultima query INSERT
     */
    public function getLastID();

    /**
     * Chiude la connessione al database
     */
    public function close();

    /**
     * Supporto ai Prepared Statement
     */

    /**
     * Il metodo permette di eseguire una query mediante un prepared statement.
     * è uno short cut di:
     * self::prepare();
     * self::bind();
     * self::exec();
     *
     * @param (string) $sql: La query SQL richiesta
     * @param (array) $parameters: Elenco di valori da sostituire ai placeholder nella query
     *                             Ogni elemento dell'array è a sua volta un array la cui chiave
     *                             è il placeholder corrispondente e contiene le chiavi:
     *                             'data' e 'type'
     *                             per i dettagli sulle loro nature @see self::bind()
     * @param (bool) $one_shot: controlla cosa deve venire ritornato @see self::query()
     * @param (int) $mode: La modalità di ritorno dei dati. @see self::query()
     *
     * @throws DBException se il prepared statement fallisce
     * @return @see self::query()
     */
    public function stmtQuery($sql, $parameters, $one_shot=false, $mode=DB::FETCH_ASSOC);

    /**
     * Esegue il primo passo di preparazione di un Prepared Statement
     * @param (string) $sql: la query da eseguire con gli adeguati placeholder
     *                       Sono supportati placeholder indicati con ? o
     *                       placeholder nominati secondo la sintassi ':nome'.
     *                       NON mischiare placeholder nominati e non nella stessa
     *                       query
     * @return un oggetto DbStatement
     */
    public function prepare($sql);

    /**
     * Imposta un dato per eseguire un prepared statement
     * @param (DBStatement) $stmt: un oggetto creato con self::prepare()
     * @param (string)$placeholder: il placeholder per questo dato usato nella query
     *                              Per placeholder nominati deve corrispondere al
     *                              nome del placeholder preceduto da ':'
     *                              Per i placeholder con ? deve essere l'indice
     *                              numerico corrispondente alla posizione del
     *                              parametro nella query, iniziando il conteggio
     *                              da 1.
     * @param (mixed)$data: il valore effettivo del dato
     * @param (string)$type Indica la natura del dato sul database:
     *                          GDRCD_FILTER_INT se è un numero intero
     *                          GDRCD_FILTER_FLOAT se è un numero decimale
     *                          GDRCD_FILTER_STRING se è testo o una data
     *                          GDRCD_FILTER_BINARY se sono dati binari
     */
    public function bind($stmt, $placeholder, $data, $type=DB::TYPE_STRING);

    /**
     * Esegue un Prepared Statement
     * @param (DBStatement)$stmt: lo statement preparato da eseguire
     * @param (bool)$one_shot: @see self::query()
     * @param (int)$mode: @see self::query()
     */
    public function exec($stmt, $one_shot=false, $mode=DB::FETCH_ASSOC);

    /**
     * Supporto alle Transazioni
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
}