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
    
    
    /**
     * Il metodo inizializza la connessione al database
     *
     * @param (string) $host L'host di connessione al database
     * @param (string) $user Lo user di connessione
     * @param (string) $pass La password di accesso
     * @param (string) $database Il nome del database a cui si vuol accedere
     *
     * @throws DBException se la connessione fallisce
     */
    public function __construct($host, $user, $pass, $database)
    {
        try {
            
            $this->DBObj = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8", 
                $user, 
                $pass,
                array(
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                )
            );
        
        } catch (PDOException $e) {
        
            throw new DBException($e->getMessage());
            
        }
    }
    
    
    /**
     * Permette di spedire una qualsiasi query al database e ne ritorna i dati.
     * Il parametro $mode permette di stabilire in che modo i dati vengono ritornati
     * dal metodo, mediante 4 costanti predefinite dalla classe:
     *
     *   . DB::FETCH_ASSOC <ritorna i dati in un array associativo>
     *   . DB::FETCH_NUM <ritorna i dati ordinati numericamente>
     *   . DB::FETCH_BOTH <li ritorna sia associativi, sia con indicizzazione numerica>
     *   . DB::FETCH_OBJ <ritorna i dati come oggetto>
     *
     * L'oggetto $result dispone inoltre delle seguenti proprietà:
     *
     *   . data <ritorna i dati recuperati dalla richiesta>
     *   . num_rows <ritorna il numero di righe rintracciate dalla richiesta>
     *   . insert_id <ritorna l'id di riga generato per l'ultima query di tipo insert>
     *   . affected_rows <ritorna il numero di righe affette da un intervento di update/insert/delete>
     *
     * @param (string) $sql La query SQL richiesta
     * @param (int) $mode La modalità di ritorno dei dati
     *
     * @throws DBException se la query fallisce
     * @return object Un oggetto che contiene i dati richiesti ed altre proprietà
	 */
    public function query($sql, $mode = self::FETCH_ASSOC)
    {
        $mode = $this->evaluateConstants($mode);
    
        $result = new stdClass;
        $result->data = null;
        $result->num_rows = null;
        $result->insert_id = null;
        $result->affected_rows = null;
        
        try {
            
            switch (trim(strtolower(substr($sql, 0, 6))))
            {
                case 'select':
                    $stmt = $this->DBObj->query($sql);
                    $data = $stmt->fetchAll($mode);
                    
                    $result->data = $data;
                    #$result->data = !isset($data[1])? array_shift($data) : $data;
                    $result->num_rows = $stmt->rowCount();
                    break;
                
                case 'insert':
                    $result->affected_rows = $this->DBObj->exec($sql);
                    $result->insert_id = $this->DBObj->lastInsertId();
                    break;
                
                default:
                    $result->affected_rows = $this->DBObj->exec($sql);
                    break;
            }
            
        } catch (PDOException $e) {
   
            throw new DBException($e->getMessage());
                
        }
        
        return $result;
    }
    
    
    /**
     * Il metodo permette di eseguire una query mediante un prepared statemnt.
     * Il parametro $parameters contiene l'elenco dei dati da sostituire ai placeholder.
     * Il parametro $mode permette di stabilire in che modo i dati vengono ritornati
     * dal metodo, mediante 4 costanti predefinite dalla classe:
     *
     *   . DB::FETCH_ASSOC <ritorna i dati in un array associativo>
     *   . DB::FETCH_NUM <ritorna i dati ordinati numericamente>
     *   . DB::FETCH_BOTH <li ritorna sia associativi, sia con indicizzazione numerica>
     *   . DB::FETCH_OBJ <ritorna i dati come oggetto>
     *
     * L'oggetto $result dispone inoltre delle seguenti proprietà:
     *
     *   . data: ritorna i dati recuperati dalla richiesta
     *   . num_rows: ritorna il numero di righe rintracciate dalla richiesta
     *   . insert_id: ritorna l'id di riga generato per l'ultima query di tipo insert
     *   . affected_rows: ritorna il numero di righe affette da un intervento di update/insert/delete
     *
     * @param (string) $sql: La query SQL richiesta
     * @param (array) $parameters: Elenco di valori da sostituire ai placeholder nella query
     * @param (int) $mode: La modalità di ritorno dei dati
     *
     * @throws DBException se il prepared statement fallisce
     * @return (object) Un oggetto che contiene i dati richiesti ed altre proprietà
     */
    public function stmtQuery($sql, $parameters = array(), $mode = self::FETCH_ASSOC)
    {
        $mode = $this->evaluateConstants($mode);
        
        $result = new stdClass;
        $result->data = null;
        $result->num_rows = null;
        $result->insert_id = null;
        $result->affected_rows = null;
        
        try {
        
            $stmt = $this->DBObj->prepare($sql);
            $stmt->execute($parameters);
            
            switch (trim(strtolower(substr($sql, 0, 6))))
            {
                case 'select':
                    $data = $stmt->fetchAll($mode);
                    
                    $result->data = $data;
                    #$result->data = !isset($data[1])? array_shift($data) : $data;
                    $result->num_rows = $stmt->rowCount();
                    break;
                
                case 'insert':
                    $result->affected_rows = $stmt->rowCount();
                    $result->insert_id = $this->DBObj->lastInsertId();
                    break;
                
                default:
                    $result->affected_rows = $stmt->rowCount();
                    break;
            }
            
        } catch (PDOException $e) {
   
            throw new DBException($e->getMessage());
            
        }
        
        return $result;
    }
    
    
    public function prepare($sql){}
    
    
    public function bind($placeholder, $data, $filter){}
    
    
    public function exec($mode){}
    
    
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
    
    
    /**
     * Il distrutture della classe elimina l'istanza dell'oggetto di database 
     * ed effettua la disconnessione.
     * Nel caso di PDO la cancellazione dell'instanza chiude automaticamente 
     * la connessione al database in uso.
     */
    public function __destruct()
    {
        unset($this->DBObj);
    }
}


/*
# Premessa: ogni istruzione può essere gestita col paradigma try/catch 
# richiamando la classe Exception, come nell'esempio che segue:

try {

    $db->query("Wrong SQL Syntax!");

} catch (Exception $e)
{
    $errorMessage = $e->getMessage();
    
    // altre eventuali gestioni dell'errore
}



#> Esempio di connessione
$DB = new DB('host', 'user', 'password', 'database');


#> Esempio di una query standard
$someResult = $DB->query("SELECT * FROM log WHERE nome_interessato LIKE 'Sup%'");

#> Ciò che viene immagazzinato in $someResult
#> Da notare che in caso di più record verrà riempito l'array primario in data
stdClass Object
(
    [data] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [nome_interessato] => Super
                    [autore] => ::1
                    [data_evento] => 2013-05-09 02:18:05
                    [codice_evento] => 2
                    [descrizione_evento] => ::1
                )
         )
         
    [num_rows] => 1
    [insert_id] => 
    [affected_rows] => 
)


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