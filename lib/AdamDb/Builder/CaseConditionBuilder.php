<?php

/**
 * Class CaseConditionBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class CaseConditionBuilder
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
    public function build()
    {
        return ' ( CASE '.implode(' ', $this->conditions);
    }
}
