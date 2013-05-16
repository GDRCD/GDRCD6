<?php
/**
    * GDRCD Controller Class
    * Lo scopo di questa classe astratta è quello di formare i controller di modo che siano predisposti
    * a poter essere estesi dai metodi astratti. Si occupa quindi di tenere traccia anche delle istanze 
    * dei metodi e di controllarne la reale appartenenza allo specifico controller
    * 
    * @package \GDRCD\core
*/
abstract class Controller
{
    /**
        * Tiene traccia delle istanze dei metodi avviate sotto uno specifico controller
    */
    private $methodInstances = array();


    /**
        * Grazie a questo metodo i controller ottengono la capacità di essere estesi
        * ulteriormente da altre classi che verranno richiamate come fossero metodi del 
        * controller stesso.
        * Quando viene richiamato un metodo che non esiste nel controller questo viene
        * contattato ricevendo come parametri il nome del metodo ($method) che si tenta 
        * di chiamare e gli eventuali parametri ($params) ad esso assegnati.
    */
    public function __call($method, $params = null)
    {
        $controllerClass = get_class($this);
    
        if (
            isset($this->methodInstances[$controllerClass]) && 
            !empty($this->methodInstances[$controllerClass][$method])
            )
                return $this->methodInstances[$controllerClass][$method]->execute();


        if (
            !isset($this->methodInstances[$controllerClass]) || 
            empty($this->methodInstances[$controllerClass][$method])
            )
        {
            $this->loadMethod($controllerClass, $method);
            
            if (class_exists($method))
            {
                if (!isset($this->methodInstances[$controllerClass]))
                        $this->methodInstances[$controllerClass] = array();
                        
                $this->methodInstances[$controllerClass][$method] = new $method();
                
                return $this->methodInstances[$controllerClass][$method]->execute();
                
            }else
            {
                throw new Exception(
                    "[Application: " 
                    . GDRCD::$self->currentApplication()
                    . "] Method '$method' not exists in controller class '$class'");
            }
        }
    }


    /**
        * Include il metodo richiesto sotto il controller specificato
    */
    private function loadMethod($className, $methodName)
    {
        $methodName = 
            dirname(dirname(__FILE__))
            . GDRCD_DS
            . 'application'
            . GDRCD_DS
            . GDRCD::$self->currentApplication()
            . GDRCD_DS
            . 'controller'
            . GDRCD_DS
            . $className
            . GDRCD_DS
            . $methodName
            . '.php';
        
        if (!in_array($methodName, GDRCD::$includedFiles)) {
            
            if (is_readable($methodName)) {
                GDRCD::$includedFiles[] = $methodName;
                require $methodName;
                
            } else {
                throw new Exception(
                    "[Application: " 
                    . GDRCD::$self->currentApplication()
                    . "] Method file doesn't exists or it is unaccessible in '$methodName'");
            }
        }
    }
}