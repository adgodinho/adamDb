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
     * Build SQL
     *
     * @access public
     * @return string
     */
    public function build()
    {
        $columns = array();
        $placeholders = array();

        foreach ($this->columns as $column) {
            $columns[] = $this->db->escapeIdentifier($column);
            $placeholders[] = ':'.$column;
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->db->escapeIdentifier($this->table),
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }
}
