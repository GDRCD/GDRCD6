<?php
/**
    * GDRCD Core Class
    * Il cuore del framework del CMS, il suo compito è quello di rendere disponibili le classi del core e 
    * i controller, ha inoltre il compito di tener traccia dei files inclusi e delle istanze avviate, cosicchè
    * l'intera applicazione possa accedere in un qualsiasi punto alla medesima istanza di un oggetto avviato
    * in una qualsiasi altra zona. Ciò permette interessanti sviluppi e un risparmio non indifferente di risorse.
    * 
    * @package \GDRCD\core
*/
class GDRCD
{
    /**
        * Elenco dei files inclusi, la tracciabilità può risultare particolarmente utile per il debug
    */
    static public $includedFiles = array();
    
    /**
        * Elenco delle istanze degli oggetti del core, riproponendo un istanza già avviata si risparmiano risorse
        * e permette inoltre di adoperare le stesse istanze di un oggetto in più punti del software
    */
    public $coreInstances = array();
    
    /**
        * Elenco delle istanze del controller, riproponendo un istanza già avviata si risparmiano risorse
        * e permette inoltre di adoperare le stesse istanze di un oggetto in più punti del software
    */
    public $controllerInstances = array();
    
    /**
        * Contiene il nome dell'application con cui è stata avviata l'istanza del core
        * è privata perchè è di sola lettura, per leggere questo valore fare riferimento a
        * GDRCD::currentApplication()
    */
    private $application;
    
    /**
        * Questa proprietà conterrà l'istanza già avviata della classe GDRCD, come per i controller
        * questo permetterà di accedere alla classe in un istruzione singleton usando la medesima istanza
        * per tutti i richiami.
    */
    static public $self;


    /**
        * Il costruttore della classe si occupa di:
        *   . inizializzare i moduli del core [#to do]
        *   . inizializzare l'autoinclude delle classi
        *   . autoreferenziarsi nella proprietà statica $self
    */
    public function __construct($application = GDRCD_APPLICATION_DEFAULT)
    {
        if (!in_array(dirname(__FILE__) . GDRCD_DS . 'GDRCD.class.php', self::$includedFiles))
                self::$includedFiles[] = dirname(__FILE__) . GDRCD_DS . 'GDRCD.class.php';
        
        $this->loadCore('Controller');
        $this->autoloadRegister();
        $this->application = $application;
        self::$self =& $this;
    }
    
    
    /**
        * Fornisce l'istanza del $controller chiamato.
        * Se il controller non è mai stato richiamato prima, oppure se il parametro
        * $forceNewInstance viene impostato su true, ritornerà una nuova istanza 
        * dell'oggetto, altrimenti ritorna la prima istanza archiviata.
        *
        * @param (string) $controller <Il nome del controller richiesto>
        * @param (bool) $forceNewInstance <Forza a tornare una nuova istanza>
        *
        * @return object <L'istanza del controller richiesto>
    */
    public function getControllerInstance($controller, $forceNewInstance = false)
    {
        if (!isset($this->controllerInstances[$controller])) {
            
            $this->controllerInstances[$controller] = new $controller();
            return $this->controllerInstances[$controller];
            
        } else {
            
            return $forceNewInstance? new $controller() : $this->controllerInstances[$controller];
        }
    }
    
    
    /**
        * Fornisce l'istanza del $coreClass chiamato.
        * Se non è mai stato richiamato prima, oppure se il parametro
        * $forceNewInstance viene impostato su true, ritornerà una nuova istanza 
        * dell'oggetto, altrimenti ritorna la prima istanza archiviata.
        *
        * @param (string) $coreClass <Il nome della classe core richiesta>
        * @param (bool) $forceNewInstance <Forza a tornare una nuova istanza>
        *
        * @return object <L'istanza richiesta>
    */
    public function getCoreInstance($coreClass, $forceNewInstance = false)
    {
        $this->loadCore($coreClass);
        
        
        if (empty($this->coreInstances[$coreClass]) || $forceNewInstance) {

            $this->coreInstances[$coreClass] = new $coreClass();
                     
        }

        return $this->coreInstances[$coreClass];
    }
    
    
    /**
        * Fornisce il nome dell'applicazione con cui è stata avviata l'istanza del core.
        *
        * @return string <Il nome dell'application corrente>
    */
    public function currentApplication()
    {
        return $this->application;
    }
    
    
    /**
        * Effettua la connessione e fornisce l'istanza dell'oggetto database.
        * Una volta connessi è possibile richiedere l'istanza attiva senza dover spedire
        * nuovamente i parametri richiesti per la connessione, è però possibile richiedere
        * una nuova istanza adoperando il parametro $forceNewInstance
        *
        * @param (string) $host <L'host di connessione al database>
        * @param (string) $user <Lo user di connessione>
        * @param (string) $pass <La password di accesso>
        * @param (string) $database <Il nome del database a cui si vuol accedere>
        *
        * @throws Exeption
        * @return object <L'istanza dell'oggetto database>
    */
    public function DB($host = null, $user = null, $pass = null, $database = null, $forceNewInstance = false)
    {
        #> tutto quel che segue è perché, ahimè non si può fare empty(CONSTANT) in php
        #> se qualcuno conosce un metodo migliore di effettuare un check analogo
        #> si senta libero di migliorarlo
        if (
            !defined('GDRCD_DATABASE_DRIVER') || 
            trim(GDRCD_DATABASE_DRIVER) != '' || 
            !is_null(GDRCD_DATABASE_DRIVER)
            )
                throw new Exception('No database driver is defined, check your configuration.');
        
        
        $driverPath = 
            dirname(__FILE__)
            . GDRCD_DS
            . 'driver'
            . GDRCD_DS
            . 'driver.' . GDRCD_DATABASE_DRIVER . '.php';
				
		if (!in_array($driverPath, self::$includedFiles)) {
            
            if (is_readable($driverPath)) {
                self::$includedFiles[] = $driverPath;
                require $driverPath;
                
            } else {
                throw new Exception("Core file doesn't exists or it is unaccessible in '$driverPath'");
            }
		}
        
        
        if (empty($this->coreInstances['DB']) || $forceNewInstance) {
        
            if (
                empty($host) ||
                empty($user) || 
                empty($pass) || 
                empty($database)
                )
                    throw new Exception('Unable to contact target database. Check your connection parameters.');
  

            $this->coreInstances['DB'] = new DB($host, $user, $pass, $database);
        }
        
        return $this->coreInstances['DB'];
    }
    
    
    /**
        * Verifica se la classe di core esiste nel percorso richiesto, se è
        * accessibile e in caso lo include, aggiornando l'elenco dei file inclusi.
        * Se il file non esiste o non dispone dei permessi di lettura
        * il metodo ritornerà un eccezione.
        *
        * @param (string) $className <Il nome della classe di core richiesta>
        *
        * @throws Exception
    */
    private function loadCore($className)
	{
		$className = 
            dirname(__FILE__)
            . GDRCD_DS
            . $className
            . '.class.php';
				
		if (!in_array($className, self::$includedFiles)) {
            
            if (is_readable($className)) {
                self::$includedFiles[] = $className;
                require $className;
                
            } else {
                throw new Exception("Core file doesn't exists or it is unaccessible in '$className'");
            }
		}
	}
    
    
    /**
        * Verifica se il controller esiste nel percorso richiesto, se è
        * accessibile e in caso lo include, aggiornando l'elenco dei file inclusi.
        * Se il file del controller non esiste o non dispone dei permessi di lettura
        * il metodo ritornerà un eccezione.
        *
        * @param (string) $className <Il nome del controller richiesto>
        *
        * @throws Exception
    */
    private function loadController($className)
	{
		$className = 
            dirname(dirname(__FILE__))
            . GDRCD_DS
            . 'application'
            . GDRCD_DS
            . $this->currentApplication()
            . GDRCD_DS
            . 'controller'
            . GDRCD_DS
            . $className
            . '.class.php';
				
		if (!in_array($className, self::$includedFiles)) {
            
            if (is_readable($className)) {
                self::$includedFiles[] = $className;
                require $className;
                
            } else {
                throw new Exception(
                    "[Application: " 
                    . $this->currentApplication() 
                    . "] Controller file doesn't exists or it is unaccessible in '$className'");
            }
		}
	}


    /**
        * Registra il metodo che permette l'autoinclusione dei controller richiesti
    */
	private function autoloadRegister()
	{
		spl_autoload_register(array($this, 'loadController'));	
	} 


    /**
        * Annulla la registrazione del metodo che permette l'autoinclusione dei controller richiesti
    */
	private function autoloadUnregister()
	{
		spl_autoload_unregister(array($this, 'loadController'));	
	}
	

    /**
        * Deinizializza le risorse impiegate per l'avvio del core e distrugge l'istanza corrente
        * della classe
    */
	public function __destruct()
	{
        $this->autoloadUnregister();
	}
}