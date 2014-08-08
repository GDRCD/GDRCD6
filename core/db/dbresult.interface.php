<?php
/**
 * Interfaccia per tutti i risultati ottenuti dalle query
 * Gli oggetti ritornati dalle query e dagli statement implementano questi metodi
 * @package \GDRCD\core\db
 */
interface DbResult
{
    /**
     * Fetches the next row from the resultset of the query
     * @param $mode: Specifies the format of the returned result.
     * @return the row of data in the format specified by $mode: RESULT_ARRAY returns an array (int indexes),
     * RESULT_ASSOC returns an associative array, RESULT_OBJECT returns an object of StdClass
     */
    public function fetch($mode=DB::FETCH_ASSOC);

    /**
     * Fetches all the rows from the resultset of the query
     * @param $mode: Specifies the format of the singles rows of data in the returned result.
     * @return an array containing all the rows of data in the format specified by $mode:
     * RESULT_ARRAY returns an array (int indexes),
     * RESULT_ASSOC returns an associative array,
     * RESULT_OBJECT returns an object of StdClass
     */
    public function fetchAll($mode=DB::FETCH_ASSOC);

    /**
     * @return il numero di record ritornati dalla query o il numero di righe
     *         coinvolte nell'ultima operazione di INSERT/UPDATE/DELETE
     */
    public function numRows();

    /**
     * Frees the memory occupied by the resultset
     */
    public function free();
}