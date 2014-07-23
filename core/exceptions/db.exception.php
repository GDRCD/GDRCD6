<?php

/**
 * Definisce l'eccezione da lanciare per le operazioni del DB
 */
class DBException extends GDRCDException
{
    private $query;

    public function __construct($mess,$code,$internal,$query){
        parent::__construct($mess, $code,$internal);
        $this->query=$query;
    }

    /**
     * @return la query a cui questa eccezione si riferisce
     */
    public function getQuery(){
        return $this->query;
    }

    /**
     * override
     */
    public function getInternalMessage(){
        return parent::getInternalMessage()." | ".$this->getQuery();
    }
}
