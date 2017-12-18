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
