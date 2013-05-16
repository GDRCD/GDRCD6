<?php
/**
    * File di configurazione dell'application corrente
    * 
    * @package \GDRCD\Application\GDR
*/


$APPLICATION = array();


#> Informazioni generali
$APPLICATION['name']        = 'GDR';
$APPLICATION['description'] = 'Wow, il mio primo GDR!';


#> Dati di connesione al database
$APPLICATION['db'] = array();

$APPLICATION['db']['host']      = 'localhost';
$APPLICATION['db']['user']      = 'user';
$APPLICATION['db']['password']  = 'password';
$APPLICATION['db']['database']  = 'database';
