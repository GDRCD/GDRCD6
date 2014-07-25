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
     * Contiene tutte le configurazioni impostate per l'applicazione corrente
     */
    private $appSettings;

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

        //Carica l'eccezione generica
        self::load('exceptions' . GDRCD_DS . "GDRCD.exception.php");

        $this->loadCore('Controller');
        $this->autoloadRegister();
        $this->application = $application;
        self::$self =& $this;
        $this->loadApplicationSettings();

        $this->DBBootstrap();
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
            $controller=(string)$controller;
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
     * Fornisce i dati di configurazione dell'applicazione corrente
     * @param (string)$name: il nome della configurazione o del gruppo di configurazioni
     *                          da caricare
     * @return la configurazione o il gruppo di configurazioni da caricare.
     *          Null se la configurazione richiesta non esiste
     */
    public function getApplicationSetting($name)
    {
        if(isset($this->appSettings[$name])){
            return $this->appSettings[$name];
        }
        return null;
    }

    /**
     * Carica le configurazioni dell'applicazione corrente
     */
    private function loadApplicationSettings()
    {
        $settings=dirname(dirname(__FILE__)) . GDRCD_DS .
                'application' . GDRCD_DS .
                $this->currentApplication() . GDRCD_DS .
                'application.inc.php';
        if(file_exists($settings) and is_readable($settings)){
            require_once($settings);
            if(isset($APPLICATION)){
                $this->appSettings=$APPLICATION;
            }
        }
        else{
            throw new GDRCDException("Impossibile Avviare l'applicazione",
                                    0,
                                    "File di configurazione dell'applicazione ".
                                        $this->currentApplication()." non trovato: ".$settings,
                                    GDRCD_FATAL);
        }
    }

    /**
     * Inizializza il database con i parametri forniti
     */
    private function DBBootstrap()
    {
        $this->loadCore("db","db");

        $set=$this->getApplicationSetting('db');
        if(!empty($set['driver']) && !empty($set['host']) && !empty($set['user']) && !empty($set['database'])){
            $this->coreInstances['DB']=new DB($set['driver'],
                       $set['host'],
                       $set['user'],
                       !empty($set['password'])?$set['password']:null,
                       $set['database'],
                       !empty($set['additional'])?$set['additional']:array());

        }
    }

    /**
     * Generico caricatore di file e classi nel sistema.
     * Metodo alternativo a require_once e include_once
     * @param (string) $path: il percorso del file da includere
     * @param (string) $err: Un eventuale stringa di errore da inviare
     *                       in caso che il caricamento fallisca
     * @throws GDRCDEXception in caso di fallimento
     */
    public static function load($path,$err='')
    {
        $className =
            dirname(__FILE__)
            . GDRCD_DS
            . $path;

        if(empty($err)){
            $err="Il file '".$className."' non esiste o non è accessibile.";
        }

        if (!in_array($className, self::$includedFiles)) {
            if (is_file($className) and is_readable($className)) {
                self::$includedFiles[] = $className;
                require $className;
            }
            else {
                throw new GDRCDException("Errore nell'inclusione di un file",0,$err,GDRCD_FATAL);
            }
        }
    }

    /**
        * Verifica se la classe di core esiste nel percorso richiesto, se è
        * accessibile e in caso lo include, aggiornando l'elenco dei file inclusi.
        * Se il file non esiste o non dispone dei permessi di lettura
        * il metodo ritornerà un eccezione.
        *
        * @param (string) $className: <Il nome della classe di core richiesta>
        * @param (string) $path: il percorso il cui centrare la classe da caricare
        *                        Deve essere relativo alla cartella dove si trovano
        *                        le cartelle application, core, etc
        *
        * @throws Exception
    */
    private function loadCore($className,$path='')
    {
        if (!empty($path) and $path[strlen($path)-1]!=GDRCD_DS) {
            $path.=GDRCD_DS;
        }

        self::load($path . $className . '.class.php',
                   'Il Core file ' . $className . " non esiste o non è accessibile");
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
        self::load('application'
            . GDRCD_DS
            . $this->currentApplication()
            . GDRCD_DS
            . 'controller'
            . GDRCD_DS
            . $className
            . '.class.php',"[Applicazione: "
                    . $this->currentApplication()
                    . "] Il file Controller '$className' non esiste o non è accessibile");
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