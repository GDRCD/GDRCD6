<?php
require_once(dirname(__FILE__) . GDRCD_DS . 'dbdriver.interface.php');
require_once(dirname(dirname(__FILE__)) . GDRCD_DS . 'exceptions' . GDRCD_DS . 'db.exception.php');

/**
 * Tipi di formati per i risultati ricevibili dal database
 */
define('GDRCD_FETCH_ASSOC',1);
define('GDRCD_FETCH_NUM',2);
define('GDRCD_FETCH_BOTH',3);
define('GDRCD_FETCH_OBJ',4);

/**
 * Rappresenta l'interfaccia principale per accedere layer di astrazione di GDRCD
 * Si occupa di scegliere il driver corretto del database e di inviargli le query da eseguire.
 * Questa è una classe completamente statica, utile semplicemente per la selezione del driver
 * e per poter accedere al database con un nome unico, non legato al nome del driver.
 *
 */
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
        $driver_file=dirname(__FILE__) . GDRCD_DS . 'driver.' . strtolower($driver) . '.php';
        if(file_exists($driver_file)) {
            require_once($driver_file);
            $class=new ReflectionClass($driver);
            if($class->implementsInterface('DatabaseDriver')){
                self::$dbObj=new $driver($host,$user,$passwd,$dbName,$additional);
            }else{
                throw new DBException("Il driver speficato non sembra essere il driver di un database!");
            }
        }else{
            throw new DBException("Il driver speficato non esiste");
        }
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
}