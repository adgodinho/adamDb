<?php

require_once dirname(__FILE__).'/BaseBuilder.php';

/**
 * Class InsertBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class InsertBuilder extends BaseBuilder
{
    /**
     * Insert unescaped 
     *
     * @access private
     * @var    string[]
     */
    private $insertColumns = array();

    /**
     * Get object instance
     *
     * @static
     * @access public
     * @param  Database         $db
     * @param  ConditionBuilder $condition
     * @return static
     */
    public static function getInstance(Database $db, ConditionBuilder $condition)
    {
        $class = get_class();
        return new $class($db, $condition);
    }

    /**
     * Execute insert
     *
     * @access public
     * @return string
     */
    public function execute()
    {   
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->db->escapeIdentifier($this->table),
            implode(', ', array_merge(array_keys($this->columns), array_keys($this->insertColumns))),
            implode(', ', array_merge(array_map(array($this, 'escapeValues'), array_values($this->columns)), array_values($this->insertColumns)))
        );

        return $this->db->execute($sql, $this->conditionBuilder->getValues()) !== false;
    }

    private function escapeValues($value) 
    {
        return "'".$value."'";
    }

    public function column($column, $value, $escaped = true)
    {
        if($escaped) {
            $this->insertColumns[$column] = "'".$value."'";
        } else {
            $this->insertColumns[$column] = $value;
        }
        return $this;
    }
}
