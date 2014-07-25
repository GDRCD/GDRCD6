<?php
/**
 * Rappresenta l'interfaccia principale per accedere layer di astrazione di GDRCD
 * Si occupa di scegliere il driver corretto del database e di inviargli le query da eseguire.
 * Serve come punto principale di definizione delle constanti di tutto ciò che concerne il db
 * @package \GDRCD\core\db
 * @author Stefano "leoblacksoul" Campanella <programming@rel.to>
 */

GDRCD::load('db' . GDRCD_DS . 'dbdriver.interface.php');
GDRCD::load('db' . GDRCD_DS . 'dbresult.interface.php');
GDRCD::load('db' . GDRCD_DS . 'dbstatement.class.php');
GDRCD::load('exceptions' . GDRCD_DS . 'db.exception.php');

class DB
{
    /**
     * Costanti per indicare il formato dei dati da ritornare da una query
     */

    /**
     * Un array associativo, le cui chiavi sono i nomi dei campi ritornati dalla query
     */
    const FETCH_ASSOC=1;
    /**
     * Un array classico con indici numerici
     */
    const FETCH_NUM=2;
    /**
     * Un array che contiene sia i dati in forma associativa che in indici numerici
     */
    const FETCH_BOTH=3;
    /**
     * Un oggetto di classe StdClass, ogni dato è salvato nell'attributo pubblico
     * corrispondente al nome del campo ritornato dalla query
     */
    const FETCH_OBJ=4;

    /**
     * Costanti da usare per indicare la tipologia di dato dei parametri dei
     * Prepared Statements
     */

    /**
     * Il dato fornito è un intero
     */
    const TYPE_INT='i';
    /**
     * Il dato fornito è una stringa
     */
    const TYPE_STRING='s';
    /**
     * Il dato fornito è un numero decimale
     */
    const TYPE_FLOAT='d';
    /**
     * Il dato fornito contiene dati binari
     */
    const TYPE_BINARY='b';

    /**
     * The database driver object
     */
    private $dbObj;

    /**
     * Crea la connessione con il database
     * @param $driver: la classe driver da usare per la connessione al db, deve implementare
     * l'interfaccia DatabaseDriver
     * @see DatabaseDriver::__construct()
     * @throws DBException in caso di fallimento della connessione
     */
    public function __construct($driver, $host, $user, $passwd, $dbName,$additional=array())
    {
        $this->loadDriver($driver);
        $class=new ReflectionClass($driver);
        if ($class->implementsInterface('DatabaseDriver')){
            $this->dbObj=new $driver($host,$user,$passwd,$dbName,$additional);
        }
        else {
            throw new DBException("Impossibile usare il sistema di collegamento al database prescelto",
                                    0,
                                    "Il driver specificato '".$driver."' non sembra essere il driver di un database!");
        }
    }

    /**
     * Disconnette il database attualmente collegato
     */
    public function disconnect()
    {
        $this->dbObj->close();
    }

    /**
     * Metodo interno per PHP, usato per reindirizzare le chiamate al driver del DB
     */
    public function __call($method, $params = null)
    {
        if (!empty($this->dbObj) and method_exists($this->dbObj, $method)){
            return call_user_func_array(array($this->dbObj,$method), $params);
        }
        else {
            throw new DBException("Errore nell'invocazione del database",
                                    0,
                                    "Il metodo del database '".$method."' non esiste!");
        }
    }

    /**
     * Carica il file di un driver del database
     * @param (string) $driver: il nome della classe da caricare
     * @throws DBException se il file del driver non esiste
     */
    public function loadDriver($driver)
    {
        $driver_file=dirname(__FILE__) . GDRCD_DS . 'driver.' . strtolower($driver) . '.php';
        if (file_exists($driver_file)) {
            require_once($driver_file);
        }
        else {
            throw new DBException("Impossibile trovare un sistema di collegamento al database",
                                    0,
                                    "Il file del driver del database '".$driver."' non esiste");
        }
    }

    public function __destruct(){
        $this->disconnect();
    }
}