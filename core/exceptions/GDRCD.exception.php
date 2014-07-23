<?php

/**
 * Exception severity error levels
 */
define('GDRCD_FATAL',0);
define('GDRCD_WARNING',1);
define('GDRCD_INFO',2);
define('GDRCD_DEBUG',3);

/**
 * Una classe di base su cui basare tutte le classi del sistema
 * Implementa un doppio sistema di errore:
 * l'errore da mostrare all'utente
 * e l'errore da inserire nei log interni
 */
class GDRCDException extends Exception
{
    private $internalError;
    private $errorLevel;

    public function __construct($mess, $code, $internal='', $level=GDRCD_WARNING){
        parent::__construct($mess,$code);
        if(empty($internal)){
          $this->internalError=$mess;
        }
        else{
            $this->internalError=$internal;
        }
        $this->errorLevel=$level;
    }

    public function getInternalMessage(){
        return $this->internalError;
    }

    /**
     * Logga l'errore interno nel DB
     */
    public function logToDb($prefix){
        //Code to log $prefix+$this->internalMessage to a db table
    }

    /**
     * Logga l'errore interno in un file
     */
    public function logToFile($filename){
        if($fd=fopen($filename, 'a')){
            fwrite($fd, $this->getInternalMessage()."\n");
            fclose($fd);
        }
        //Fail silently? Log to somewhere else?
    }

    /**
     * Logga l'errore interno nel syslog di php
     */
    public function logToPhpLog(){
        $priority=null;
        switch($this->errorLevel){
            case GDRCD_FATAL:
                $priority=LOG_EMERG;
                break;
            case GDRCD_WARNING:
            default:
                $priority=LOG_ERR;
                break;
            case GDRCD_INFO:
                $priority=LOG_NOTICE;
                break;
            case GDRCD_DEBUG:
                $priority=LOG_DEBUG;
                break;
        }

        syslog($priority, $this->getInternalMessage());
    }

    /**
     * Stampa l'errore a schermo
     * @param (bool) $html: indica se l'errore deve essere preparato per essere
     *                      stampato come html o meno. Default true
     */
    public function printError($html=true){
        //TODO Theming?
        if($html){
            echo '<div class="gdrcd_error">'.htmlentities($this->getMessage(),ENT_QUOTES,'utf-8')."</div>";
        }
        else{
            echo $this->getMessage();
        }
    }
}
