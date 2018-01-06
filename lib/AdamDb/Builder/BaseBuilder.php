<?php

require_once dirname(__FILE__).'/../Database.php';

/**
 * Class InsertBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
abstract class BaseBuilder
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * @var ConditionBuilder
     */
    protected $conditionBuilder;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * @var string[]
     */
    protected $columns = array();

    /**
     * InsertBuilder constructor
     *
     * @param Database         $db
     * @param ConditionBuilder $condition
     */
    public function __construct(Database $db, ConditionBuilder $condition)
    {
        $this->db = $db;
        $this->conditionBuilder = $condition;
    }

    /**
     * Set table name
     *
     * @access public
     * @param  string $table
     * @return $this
     */
    public function withTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set columns name
     *
     * @access public
     * @param  string[] $columns
     * @return $this
     */
    public function withColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }
}
