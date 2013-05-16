<?php
/**
    * GDRCD Front Controller (index.php)
    * Il file si occupa di configurare le impostazioni iniziali, di caricare i path necessari
    * e di inizializzare il framework engine in base agli input utente.
    * 
    * @package \GDRCD
*/

############################# Configurazione di GDRCD Framework Engine #############################

/**
    * Specifica il nome dell'application di default (definito dal nome della cartella in uso)
*/
define('GDRCD_APPLICATION_DEFAULT', 'GDR');

/**
    * Specifica quale componente caricare per permettere a GDRCD 
    * di stabilire una connessione al database
*/
define('GDRCD_DATABASE_DRIVER', 'pdomysql');

/**
    * Specifica il tipo di slash da usare per la composizione dei path del filesystem.
*/
define('GDRCD_DS', DIRECTORY_SEPARATOR);

/**
    * #TO DO
    * true: predispone l'engine all'uso dell URL REWRITE
    * false: disabilità la possibilità di utilizzo dell'URL REWRITE.
*/
define('GDRCD_URLREWRITE', false);


################# Da qui non toccare nulla se non sai ciò che fai, per cortesia :) #################


require 'core/GDRCD.class.php';


$GDRCD = new GDRCD();

$Controller = $GDRCD->getControllerInstance($_GET['controller']);
$Controller->{$_GET['method']}();

