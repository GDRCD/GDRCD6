<?php
/**
 * Astrazione del database per il driver mysqli
 * @package \GDRCD\core\db
 * @author Stefano "leoblacksoul" Campanella <programming@rel.to>
 */

class MysqlI implements DatabaseDriver
{
    private $DBObj;
    private $activeTransaction=false;

    public function __construct($host, $user, $pass, $database,$additional)
    {
        $port=null;
        if (!empty($additional['port'])) {
            $port=(int)$additional['port'];
        }

        if (!empty($additional['socket'])) {
            $socket=$additional['socket'];
        }

        $this->DBObj=new mysqli($host,$user,$pass,$database,$port,$socket);

        if ($this->DBObj->connect_error) {
          throw new DBException("Errore di connessione al server del database",0,$this->connection->connect_error);
        }

        if (!$this->DBObj->set_charset("utf8")) {
          throw new DBException("Errore di configurazione della connessione al database",
                                0,
                                "Impossibile impostare il set di caratteri UTF-8 per il database");
        }
    }

    public function query($sql, $one_shot=false, $mode=GDRCD_FETCH_ASSOC)
    {
        $res=$this->DBObj->query($sql);

        if ($res === true) {//Non era una query SELECT
            return $this->DBObj->affected_rows;
        }
        elseif ($res instanceof mysqli_result) {
            $result=new MysqlIResult($res);
            if ($one_shot) {
                return $result->fetch($mode);
            }
            else {
                return $result;
            }
        }
        elseif ($res===false) {
            throw new DBException("Errore nell'interrogazione al database.",0,$this->DBObj->error);
        }
    }

    public function escape($param)
    {
        return $this->DBObj->real_escape_string($param);
    }

    public function getLastID()
    {
        return $this->DBObj->insert_id;
    }

    public function close()
    {
        $this->DBObj->close();
    }

    public function stmtQuery($sql, $parameters, $one_shot=false, $mode=GDRCD_FETCH_ASSOC)
    {
        $s=$this->prepare($sql);
        foreach ($parameters as $pl=>$data){
            $this->bind($s, $pl, $data['data'], $data['type']);
        }
        return $this->exec($s);
    }

    public function prepare($sql)
    {
        return new MysqlIStatement($this->DBObj,$sql);
    }

    public function bind($stmt, $placeholder, $data, $type)
    {
        $stmt->addParam($placeholder,$data,$type);
    }

    public function exec($stmt, $one_shot=false, $mode=GDRCD_FETCH_ASSOC)
    {
        $stmt->doRealBind();
        $ex=$stmt->getStatement()->execute();
        if ($ex) {
            $meta=$stmt->getStatement()->result_metadata();
            /**
             * Does the query have a result set? Metadata can be retrieved only for
             * queries with a resultset
             */
            if ($meta!==false) {
                /**
                 * We can't use $stmt->get_result() because it is available only
                 * with mysqlnd and it is not always available in default php
                 * installations
                 */
                $result=new MysqlIResult($meta,$stmt);
                if ($one_shot) {
                    return $result->fetch($mode);
                }
                else {
                    return $result;
                }
            }
            else {//This is a query with no results
                return $stmt->getStatement()->affected_rows;
            }
        }
        else {
            throw DBException("Errore esecuzione comandi del database",0,"Errore execute del prepared statement: ".$this->DBObj->error,$stmt->getSql());
        }
    }

    public function startTransaction()
    {
        $this->DBObj->autocommit(false);
        $this->activeTransaction=true;
    }

    public function commitTransaction()
    {
        $this->DBObj->commitTransaction();
        $this->DBObj->autocommit(true);
        $this->activeTransaction=false;
    }

    public function rollbackTransaction()
    {
        $this->DBObj->rollbackTransaction();
        $this->DBObj->autocommit(true);
        $this->activeTransaction=false;
    }

    public function isTransactionActive()
    {
        return $this->activeTransaction;
    }

    public function __destruct()
    {
        $this->close();
    }
}

/**
 * Represents a result from the database
 */
class MysqlIResult implements DbResult
{
    private $result;
    private $stmt;
    private $bind_results;
    private $fields;

    /**
     * The second parameter is used if the query was made with a prepared statement
     */
    public function __construct(mysqli_result $r, MysqlIStatement $s=null)
    {
        if (!empty($r) and $r instanceof mysqli_result) {
            $this->result=$r;
        }
        else {
            throw new DBException("Errore di costruzione dei risultati del database",0,"Il parametro passato a MysqlIResult non è un mysqli_result");
        }

        if (!empty($s) and $s instanceof MysqlIStatement) {
            $this->stmt=$s;
            //Buffer all the data or resultsets with big datatypes may consume all memory
            $this->stmt->getStatement()->store_result();

            $this->fields=$this->result->fetch_fields();

            $n_fields=$this->result->field_count;
            $this->bind_results=array();
            $results=array();
            for($i=0;$i<$n_fields;$i++){//Build the arguments array
                $results[]=&$this->bind_results[$i];//Allocate memory for the results
            }
            call_user_func_array(array($this->stmt->getStatement(),'bind_result'), $results);
        }
        else {
            $this->stmt=null;
        }
    }

    public function fetch($mode=GDRCD_FETCH_ASSOC)
    {
        if (empty($this->stmt)) {//Query mode
            return $this->fetchQuery($mode);
        }
        else {//Prepared Statement mode
            return $this->fetchStmt($mode);
        }
    }

    public function fetchAll($mode=GDRCD_FETCH_ASSOC)
    {
      $result=array();
      while ($row=$this->fetch($mode)) {
        $result[]=$row;
      }
      return $result;
    }

    public function numRows()
    {
        if (empty($this->stmt)) {
            return $this->result->num_rows;
        }
        else {
            return $this->stmt->getStatement()->affected_rows;
        }
    }

    public function free()
    {
        $this->result->free();
        if (!empty($this->stmt)) {
            $this->stmt->getStatement()->free_result();
            $this->stmt->getStatement()->close();
        }
    }

    public function __destruct()
    {
        $this->free();
    }

    private function fetchQuery($mode)
    {
        switch ($mode) {
            case GDRCD_FETCH_ASSOC:
                return $this->result->fetch_assoc();
                break;

            case GDRCD_FETCH_NUM:
                return $this->result->fetch_array(MYSQLI_NUM);
                break;

            case GDRCD_FETCH_BOTH:
                return $this->result->fetch_array(MYSQLI_BOTH);
                break;

            case GDRCD_FETCH_OBJ:
                return $this->result->fetch_object();
                break;
        }
    }

    private function fetchStmt($mode)
    {
        $this->stmt->getStatement()->fetch();
        $output=array();
        switch ($mode) {
            case GDRCD_FETCH_BOTH:
                $output=$this->bind_results;
                //No break!
            case GDRCD_FETCH_ASSOC:
                $i=0;
                foreach($this->fields as $fObj){
                    $output[$fObj->name]=$this->bind_results[$i];
                    $i++;
                }
                return $output;
                break;

            case GDRCD_FETCH_NUM:
                return $this->bind_results;
                break;

            case GDRCD_FETCH_OBJ:
                $output=new stdClass();
                $i=0;
                foreach($this->fields as $fObj){
                    $output->{$fObj->name}=$this->bind_results[$i];
                    $i++;
                }
                return $output;
                break;
        }
    }
}

class MysqlIStatement extends DBStatement
{
    private $temp_params=array();
    private $sql;
    private $question_ph;
    private $named_ph;
    const NAMED_REGEX="/(:.+)\b/siU";

    public function __construct(mysqli $db,$sql)
    {
        $this->sql=$sql;
        $this->question_ph=substr_count($sql, '?');
        preg_match_all(self::NAMED_REGEX,$sql, $matches);
        $this->named_ph=$matches[1];

        if (count($this->named_ph)>0) {
            $this->sql=preg_replace(self::NAMED_REGEX, '?', $this->sql);
        }
        parent::__construct($db->prepare($sql));
    }

    public function resetStatement()
    {
        $this->getStatement()->free_result();
        $this->getStatement()->reset();
    }

    /**
     * Working with the assumption that the two types of placeholders are not
     * mixed in the same query. If not mysqli will just throw an exception at bindParam time
     */
    public function addParam($placeholder,$value, $type)
    {
        if (is_numeric($placeholder)) {
            $this->temp_params[(int)$placeholder]=array($value,$type);
        }
        elseif (in_array($placeholder, $this->named_ph)) {
            $this->temp_params[$placeholder]=array($value,$type);
        }
        else {
            throw new DBException("Errore inserimento dati per il database",0,"Parametro errato come placeholder: ".$placeholder." | ".$value." | ".$type,$this->sql);
        }
    }

    public function doRealBind()
    {
        //mysqli does not support named placeholders, let's convert them to ?
        if (count($this->named_ph)>0) {
            $new_temp=array();
            foreach($this->named_ph as $i=>$pl){
                if(!empty($this->temp_params[$pl])){
                    $new_temp[$i]=$this->temp_params[$pl];
                }
            }
            $this->temp_params=$new_temp;
        }

        ksort($this->temp_params);
        $args=array();
        $i=1;
        foreach ($this->temp_params as $k=>$data) {
            //Add the type
            $args[0].=$data[1];
            //Add the value. Beware mysqli wants values passed by reference
            $args[$i]=$this->temp_params[$k][0];
            $i++;
        }

        //If it fails it returns false and also throws a PHP Warning
        $result=call_user_func_array(array($this->getStatement(),'bind_param'), $args);
        if (!$result) {
            ob_start();
            var_dump($args);
            $debug=ob_get_contents();
            ob_end_clean();
            throw new DBException("Errore trasferimento dati al database",0,"bind_param fallita con parametri: ".$debug,$this->sql);
        }
    }

    public function getSql()
    {
        return $this->sql;
    }
}
