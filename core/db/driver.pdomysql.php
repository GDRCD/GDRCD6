<?php
/**
 * PDO Mysql Driver
 * La classe in questione adopera PDO per dialogare con un database mysql.
 * Lo scopo non è implementare PDO e quindi rendere usabili i metodi dello stesso mediante un extends
 * ma piuttosto fornire un interfaccia per l'utilizzo di PDO (non è un controsenso se si pensa che ha
 * i suoi limiti e qualcuno potrebbe dover necessitare di altro), così che solo i metodi di questa classe
 * saranno utilizzati e gli stessi standardizzeranno il modo con cui vengono eseguite le query nel CMS.
 * Tutto questo, permetterà di adoperare i più svariati driver (odbc, mysqli, sqlserver) senza dover
 * riscrivere altro in tutto l'engine (per maggiori info, googlate "abstraction layer").
 * IMPORTANTE: per il presente driver è richiesto che nel php.ini sia abilitata l'estensione php_pdo_mysql.dll
 *
 * @package \GDRCD\core\driver
 */
class PdoMysql implements DatabaseDriver
{
    private $DBObj;
    private $activeTransaction=false;

    /**
     * Il metodo inizializza la connessione al database
     *
     * @param (string) $host: L'host di connessione al database
     * @param (string) $user: Lo user di connessione
     * @param (string) $pass: La password di accesso
     * @param (string) $database: Il nome del database a cui si vuol accedere
     * @param (array)  $additional: un array di parametri addizionali da passare
     *                              direttamente al driver PDO
     *
     * @throws DBException se la connessione fallisce
     */
    public function __construct($host, $user, $pass, $database,$additional=array())
    {
        try {

            $additional=array_merge($additional,array(
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true//Don't know if it works here. If not we should move it to statement attributes
            ));

            $this->DBObj = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8",
                $user,
                $pass,
                $additional
            );

        }
        catch (PDOException $e){
            throw new DBException($e->getMessage());
        }
    }


    /**
     * Permette di spedire una qualsiasi query al database e ne ritorna i dati.
     *
     * @param (string) $sql: La query SQL richiesta
     * @param (bool) $one_shot: controlla cosa deve venire ritornato
     * @param (int) $mode: Valido solo se $one_shot=true
     *                     Il parametro $mode permette di stabilire in che modo i dati vengono ritornati
     *                     dal metodo, mediante 4 costanti predefinite:
     *                     . GDRCD_FETCH_ASSOC: ritorna i dati in un array associativo
     *                     . GDRCD_FETCH_NUM: ritorna i dati ordinati numericamente
     *                     . GDRCD_FETCH_BOTH: li ritorna sia associativi, sia con indicizzazione numerica
     *                     . GDRCD_FETCH_OBJ: ritorna i dati come oggetto
     *
     * @throws DBException se la query fallisce
     * @return (object|array) se $one_shot==true viene ritornato direttamente il
     *                      primo record del resultset nel formato specificato
     *                      da $mode. Altrimenti viene ritornato un oggetto DBResult
     *                      Se la query non è di select, viene ritornato il numero
     *                      di record coinvolti nella query eseguita.
	 */
    public function query($sql, $one_shot=false, $mode = GDRCD_FETCH_ASSOC)
    {
        try {
            switch (strtolower(substr($sql, 0, strpos($sql,' ')))) {
                case 'select':
                case 'describe':
                case 'explain':
                case 'show':
                    $stmt = $this->DBObj->query($sql);
                    $res=new PDOResult($stmt);

                    if ($one_shot) {
                        return $res->fetch($mode);
                    }
                    else {
                        return $res;
                    }
                    break;

                default://Simply execute and return affected rows number
                    return $this->DBObj->exec($sql);
                    break;
            }

        }
        catch (PDOException $e) {
            throw new DBException("Errore nell'interrogazione al database.",0,$e->getMessage(),$sql);
        }
    }

    public function escape($param){
        return $this->DBObj->quote($param);
    }

    /**
     * Il metodo permette di eseguire una query mediante un prepared statement.
     *
     * @param (string) $sql: La query SQL richiesta
     * @param (array) $parameters: Elenco di valori da sostituire ai placeholder nella query,
     *                              placeholder nelle chiavi, valori nei valori
     * @param (bool) $one_shot: controlla cosa deve venire ritornato
     * @param (int) $mode: La modalità di ritorno dei dati. @see self::query
     *
     * @throws DBException se il prepared statement fallisce
     * @return @see self::query()
     */
    public function stmtQuery($sql, $parameters = array(), $one_shot=false, $mode = GDRCD_FETCH_ASSOC)
    {
        $stmt = $this->prepare($sql);
        foreach($parameters as $v){
            $this->bind($stmt, $v['placeholder'], $v['data'], '');
        }
        $this->exec($stmt);
    }

    public function prepare($sql){
        try{
            return new DBPDOStatement($this->DBObj->prepare($sql));
        }
        catch(PDOException $e){
            throw new DBException("Errore preparazione dati del database",0,"Errore Prepare: ".$e->getMessage(),$sql);
        }
    }

    /**
     * PDO è più potente e non ha bisogno di indicazioni per filtrare i dati
     */
    public function bind($stmt, $placeholder, $data, $filter){
        $real_stmt=$stmt->getStatement();
        try{
            $real_stmt->bindParam($placeholder,$data);
        }
        catch(PDOException $e){
            throw new DBException("Errore impostazione parametri per il database",0,"Errore bind argomento Prepared Statement: ".$e->getMessage(),$real_stmt->queryString);
        }
    }

    public function exec($stmt, $one_shot=false, $mode=GDRCD_FETCH_ASSOC){
        $real_stmt=$stmt->getStatement();
        $sql=$real_stmt->queryString;
        try{
            $real_stmt->execute();

            switch (trim(strtolower(substr($sql, 0, strpos($sql, ' '))))) {
                case 'select':
                case 'describe':
                case 'explain':
                case 'show':
                    $res=new PDOResult($real_stmt);
                    if($one_shot){
                        return $res->fetch($mode);
                    }
                    else{
                        return $res;
                    }
                    break;
                default:
                    return $stmt->rowCount();
                    break;
            }
        }
        catch(PDOException $e){
            throw new DBException("Errore nell'interrogazione al database",0,"Errore Execute: ".$e->getMessage(),$sql);
        }
    }

    /**
     * @return l'ultimo ID inserito nel database da una query di INSERT
     */
    public function getLastID(){
        return $this->DBObj->lastInsertId();
    }

    public function startTransaction(){
        $this->DBObj->beginTransaction();
        $this->activeTransaction=true;
    }

    /**
     * Committa una Transazione con successo
     */
    public function commitTransaction(){
        $this->DBObj->commit();
        $this->activeTransaction=false;
    }

    /**
     * Annulla tutte le azioni fatte durante la transazione attuale, terminandola
     */
    public function rollbackTransaction(){
        $this->DBObj->rollBack();
        $this->activeTransaction=false;
    }

    /**
     * @return true se c'è una transazione attiva
     */
    public function isTransactionActive(){
        if(method_exists($this->DBObj, 'inTransaction')){
            return $this->DBObj->inTransaction();
        }
        else{
            return $this->activeTransaction;
        }
    }

    /**
     * Il distrutture della classe elimina l'istanza dell'oggetto di database
     * ed effettua la disconnessione.
     * Nel caso di PDO la cancellazione dell'instanza chiude automaticamente
     * la connessione al database in uso.
     */
    public function close()
    {
        unset($this->DBObj);
    }

    /**
     * Let's close the connection if the object gets destroyed
     */
    public function __destruct(){
        $this->close();
    }
}

/**
 * Rappresenza un risultato proveniente dal DB.
 * Può essere usato sia per risultati che contengono dati, sia per semplici
 * query che hanno solo il numero di record coinvolti
 */
class PDOResult implements DbResult{
    private $PDOstmt;

    public function __construct(PDOStatement $stmt){
        if(!empty($stmt) and $stmt instanceof PDOStatement){
            $this->PDOstmt=$stmt;
        }
        else{
            throw new DBException("Errore di costruzione dei risultati del database",0,"Il parametro passato a PDOResult non è un PDOStatement");
        }
    }

    public function fetch($mode=GDRCD_FETCH_ASSOC){
        $val=$this->PDOstmt->fetch($this->evaluateConstants($mode));

        if($val!==false){
            return $val;
        }
        else{
            throw new DBException("Non ci sono dati da riornare", 0, "Chiamata a metodo fetch su un risultato senza resultset",$this->PDOstmt->queryString);
        }
    }

    public function fetchAll($mode=GDRCD_FETCH_ASSOC){
        $val=$this->PDOstmt->fetchAll($this->evaluateConstants($mode));

        if($val!==false){
            return $val;
        }
        else{
            throw new DBException("Non ci sono dati da riornare", 0, "Chiamata a metodo fetchAll su un risultato senza resultset",$this->PDOstmt->queryString);
        }
    }

    public function numRows(){
        $chunks=explode(' ', $this->PDOstmt->queryString);
        if($chunks[0]=='select'){
            /**
             * Sfortunatamente PDO non ha un metodo che ritorni effettivamente
             * il numero di righe nel recordset. Ce lo dobbiamo calcolare.
             */

            /**
             * Costruisco una nuova query di conteggio dalla vecchia query.
             * Sperando di non incappare in casi particolari
             */
            $new_query="SELECT count(*) AS N ".substr($this->PDOstmt->queryString,
                        strpos($this->PDOstmt->queryString, 'FROM'));
            $count=DB::query($new_query,true);
            return (int)$count['N'];
        }
        else{
            return $this->PDOstmt->rowCount();
        }
    }

    public function free(){
        $this->PDOstmt->closeCursor();
    }

    /**
     * Accetta in ingresso una costante definita nell'interfaccia DatabaseDriver e ritorna
     * la costante associata equivalente per PDO.
     *
     * @param (int) $mode: La modalità di ritorno dei dati richiesta
     * @return (int) La modalità di ritorno dei dati compatibile
     */
    private function evaluateConstants($mode)
    {
        switch ($mode)
        {
            case self::FETCH_ASSOC:
                return PDO::FETCH_ASSOC;
                break;

            case self::FETCH_NUM:
                return PDO::FETCH_NUM;
                break;

            case self::FETCH_BOTH:
                return PDO::FETCH_BOTH;
                break;

            case self::FETCH_OBJ:
                return PDO::FETCH_OBJ;
                break;
        }
    }
}

/**
 * Rappresenta uno Statement PDO
 */
class DBPDOStatement extends DBStatement{
    public function resetStatement(){
        $this->statement->closeCursor();
    }
}

/*
# Premessa: ogni istruzione può essere gestita col paradigma try/catch
# richiamando la classe DBException, come nell'esempio che segue:

try {

    $db->query("Wrong SQL Syntax!");

} catch (DBException $e)
{
    $errorMessage = $e->getMessage();

    // altre eventuali gestioni dell'errore
}



#> Esempio di connessione
$DB = new DB('host', 'user', 'password', 'database');


#> Esempio di una query standard
$someResult = $DB->query("SELECT * FROM log WHERE nome_interessato LIKE 'Sup%'");


#> Stessa query di prima, ma con i prepared statement (il responso sarà identico)
$someResult = $DB->stmtQuery(
    "SELECT * FROM log WHERE nome_interessato LIKE ?",
    array('Sup%')
);


#> Query di insert con statement contenente più parametri
$insertResult = $DB->stmtQuery(
    "INSERT INTO log (nome_interessato, autore, data_evento, codice_evento, descrizione_evento)
    VALUES (?, ?, NOW(), ?, ?)",
    array('Super', '::1', 2, '::1')
);

#> $insertResult tornerà così:
stdClass Object
(
    [data] =>
    [num_rows] =>
    [insert_id] => 2
    [affected_rows] => 1
)


#> Query di update, questa volta per lo statement adopero dei placeholder
$insertResult = $DB->stmtQuery(
    "UPDATE log SET descrizione_evento = :log_desc WHERE id = :log_id",
    array(':log_id' => 2, ':log_desc' => '::2')
);

#> In $insertResult ci sarà, come al solito
stdClass Object
(
    [data] =>
    [num_rows] =>
    [insert_id] =>
    [affected_rows] => 1
)

*/