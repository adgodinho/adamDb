<?php

/**
 * Class OrConditionBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class OrConditionBuilder
{
    /**
     * List of SQL conditions
     *
     * @access protected
     * @var string[]
     */
    protected $conditions = array();

    /**
     * Add new condition
     *
     * @access public
     * @param  string $condition
     * @return $this
     */
    public function withCondition($condition)
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Build SQL
     *
     * @access public
     * @return string
     */
    public function build($and = false)
    {   
        if($and) {
            return ' AND ('.implode(' OR ', $this->conditions).')';
        } else {
            return ' ('.implode(' OR ', $this->conditions).')';
        }
    }
}
