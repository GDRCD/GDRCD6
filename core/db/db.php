<?php
/**
 * Rappresenta l'interfaccia principale per accedere layer di astrazione di GDRCD
 * Si occupa di scegliere il driver corretto del database e di inviargli le query da eseguire.
 * Questa è una classe completamente statica, utile semplicemente per la selezione del driver
 * e per poter accedere al database con un nome unico, non legato al nome del driver.
 * @package \GDRCD\core\db
 * @author Stefano "leoblacksoul" Campanella <programming@rel.to>
 */

GDRCD::load('db' . GDRCD_DS . 'dbdriver.interface.php');
GDRCD::load('db' . GDRCD_DS . 'dbresult.interface.php');
GDRCD::load('db' . GDRCD_DS . 'dbstatement.class.php');
GDRCD::load('exceptions' . GDRCD_DS . 'db.exception.php');

/**
 * Tipi di formati per i risultati ricevibili dal database
 */
define('GDRCD_FETCH_ASSOC',1);
define('GDRCD_FETCH_NUM',2);
define('GDRCD_FETCH_BOTH',3);
define('GDRCD_FETCH_OBJ',4);

/**
 * Tipo di formati per i parametri dei prepared statements
 */
define('GDRCD_FILTER_INT','i');
define('GDRCD_FILTER_STRING','s');
define('GDRCD_FILTER_FLOAT','d');
define('GDRCD_FILTER_BINARY','b');

class DB
{
    /**
     * The database driver object
     */
    static private $dbObj;

    /**
     * Classe statica, non è pensata per venire istanziata
     */
    private function __construct(){}

    /**
     * Crea la connessione con il database
     * @param $driver: la classe driver da usare per la connessione al db, deve implementare
     * l'interfaccia DatabaseDriver
     * @see DatabaseDriver::__construct()
     * @throws DBException in caso di fallimento della connessione
     *
     * TODO considerare se è il caso di non usare $driver e leggere direttamente da GDRCD_DATABASE_DRIVER
     */
    public static function connect($driver, $host, $user, $passwd, $dbName,$additional=array())
    {
        self::loadDriver($driver);
        $class=new ReflectionClass($driver);
        if($class->implementsInterface('DatabaseDriver')){
            self::$dbObj=new $driver($host,$user,$passwd,$dbName,$additional);
        }else{
            throw new DBException("Il driver specificato non sembra essere il driver di un database!");
        }
    }

    public static function disconnect(){
        self::$dbObj->close();
    }

    /**
     * Metodo interno per PHP, usato per reindirizzare le chiamate al driver del DB
     */
    public static function __callStatic($method, $params = null)
    {
        if(!empty(self::$dbObj) and method_exists(self::$dbObj, $method)){
            return call_user_func_array(array(self::$dbObj,$method), $params);
        }else{
            throw new DBException("Il metodo ".$method." non esiste!");
        }
    }

    public static function loadDriver($driver){
        $driver_file=dirname(__FILE__) . GDRCD_DS . 'driver.' . strtolower($driver) . '.php';
        if (file_exists($driver_file)) {
            require_once($driver_file);
        }
        else {
            throw new DBException("Il driver speficato non esiste");
        }
    }
}