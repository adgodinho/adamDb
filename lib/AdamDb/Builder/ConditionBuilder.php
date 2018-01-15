<?php

require_once dirname(__FILE__).'/../Database.php';
require_once dirname(__FILE__).'/../Table.php';

/**
 * Handle SQL conditions
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class ConditionBuilder
{
    /**
     * Database instance
     *
     * @access private
     * @var    Database
     */
    private $db;

    /**
     * Condition values
     *
     * @access private
     * @var    array
     */
    private $values = array();

    /**
     * SQL AND conditions
     *
     * @access private
     * @var    string[]
     */
    private $conditions = array();

    /**
     * SQL CASE conditions
     *
     * @access private
     * @var    OrConditionBuilder[]
     */
    private $caseConditions = array();

    /**
     * SQL case condition offset
     *
     * @access private
     * @var int
     */
    private $caseConditionOffset = 0;

    /**
     * SQL case condition offset
     *
     * @access private
     * @var int
     */
    private $conditionOffset = 0;

    /**
     * Use CASE or not?
     *
     * @access private
     * @var    boolean
     */
    private $case;

    /**
     * Constructor
     *
     * @access public
     * @param  Database  $db
     */
    public function __construct(Database $db, $case = false)
    {
        $this->db = $db;
        $this->case = $case;
    }

    /**
     * Build the SQL condition
     *
     * @access public
     * @return string
     */
    public function build()
    {   
        if($this->case) {
            return empty($this->conditions) ? '' : ' '.implode('', $this->conditions);
        } else {
            return empty($this->conditions) ? '' : ' WHERE '.implode('', $this->conditions);
        }
    }

    /**
     * Get condition values
     *
     * @access public
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Add condition values
     *
     * @access public
     * @return array
     */
    public function addValues(array $newValues)
    {
        $this->values = array_merge($this->values, $newValues);
    }

    /**
     * Returns true if there is some conditions
     *
     * @access public
     * @return boolean
     */
    public function hasCondition()
    {
        return ! empty($this->conditions);
    }

    /**
     * Decides if connector should be added or not
     *
     * @access public
     * @return String
     */
    public function getConnector($connector)
    {
        if($this->conditionOffset > 0) {
            $this->conditionOffset--;
            return '';
        } else if(empty($this->conditions)){
            return '';
        } else {
            return $connector;
        }
    }

    /**
     * Add custom condition
     *
     * @access public
     * @param  string  $sql
     */
    public function addCondition($sql)
    {
        if ($this->caseConditionOffset > 0) {
            $this->caseConditions[$this->caseConditionOffset]->withCondition($sql);
        } else {
            $this->conditions[] = $sql;
        }
    }

    /**
     * Start OR condition
     *
     * @access public
     */
    public function beginOr()
    {
        $this->addCondition(' OR ( ');
        $this->conditionOffset++;
    }

    /**
     * Close OR condition
     *
     * @access public
     */
    public function closeOr()
    {
        $this->addCondition(' ) ');
    }

    /**
     * Start OR condition
     *
     * @access public
     */
    public function beginAnd()
    {
        $this->addCondition(' AND ( ');
        $this->conditionOffset++;
    }

    /**
     * Close OR condition
     *
     * @access public
     */
    public function closeAnd()
    {
        $this->addCondition(' ) ');
    }

    /**
     * Start CASE condition
     *
     * @access public
     */
    public function beginCase()
    {
        $this->caseConditionOffset++;
        $this->caseConditions[$this->caseConditionOffset] = new CaseConditionBuilder(!empty($this->conditions));
    }

    /**
     * Close CASE condition
     *
     * @access public
     */
    public function closeCase($alias)
    {
        if(is_null($alias)) {
            $this->addCondition('END )');
        } else {
            $this->addCondition('END ) AS '.$this->db->escapeIdentifier(strtolower(strtolower($alias))));
        }

        $condition = $this->caseConditions[$this->caseConditionOffset]->build();
        $this->caseConditionOffset--;

        if ($this->caseConditionOffset > 0) {
            $this->caseConditions[$this->caseConditionOffset]->withCondition($condition);
        } else {
            $this->conditions[] = $condition;
        }
    }

    /**
     * Start CASE WHEN condition
     *
     * @access public
     */
    public function caseWhen()
    {
        $this->addCondition(' WHEN ');
    }

    /**
     * Start CASE WHEN condition
     *
     * @access public
     */
    public function caseThen($value = NULL, $prepared = true)
    {
        if(is_null($value)) {
            $this->addCondition(' THEN ');
        } else {
            if($prepared) {
                $this->addCondition(' THEN ? ');
                $this->values[] = $value;
            } else {
                $this->addCondition(' THEN '.$this->db->escapeIdentifier($value).' ');
            }
        }
    }

    public function caseElse($value = '', $prepared = true)
    {
        if($prepared) {
            $this->addCondition(' ELSE ? ');
            $this->values[] = $value;
        } else {
            $this->addCondition(' ELSE '.$this->db->escapeIdentifier($value).' ');
        }
    }

    /**
     * AND Equal condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function where()
    {
        $prepared = true;
        $operator = '=';
        $numArgs = func_num_args();
        switch ($numArgs) {
            case 4:
                $prepared = func_get_arg(3);
            case 3:
                if(gettype(func_get_arg(2)) == 'boolean') {
                    $prepared = func_get_arg(2);
                } else {
                    $operator = func_get_arg(2);
                }
            case 2:
                $value = func_get_arg(1);
            case 1:
               $column = func_get_arg(0);
        }

        $condition = $this->getConnector(' AND ');

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' '.$operator.' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' '.$operator.' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= $this->db->escapeIdentifier($column).' '.$operator.' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR Equal condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function orWhere()
    {
        $prepared = true;
        $operator = '=';
        $numArgs = func_num_args();
        switch ($numArgs) {
            case 4:
                $prepared = func_get_arg(3);
            case 3:
                if(gettype(func_get_arg(2)) == 'boolean') {
                    $prepared = func_get_arg(2);
                } else {
                    $operator = func_get_arg(2);
                }
            case 2:
                $value = func_get_arg(1);
            case 1:
               $column = func_get_arg(0);
        }

         $condition = $this->getConnector(' OR ');

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' '.$operator.' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' '.$operator.' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= $this->db->escapeIdentifier($column).' '.$operator.' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * AND IN condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $values
     * @param  boolean  $prepared
     */
    public function whereIn($column, $values, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($values instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' IN ('.$values->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $values->getConditionBuilder()->getValues());
        } elseif(is_array($values)) {
            if ($prepared) {
                if (! empty($values)) {
                    $condition .= $this->db->escapeIdentifier($column).' IN ('.implode(', ', array_fill(0, count($values), '?')).')';
                    $this->addCondition($condition);
                    $this->values = array_merge($this->values, $values);
                }
            } else {
                $condition .= $this->db->escapeIdentifier($column).' IN ('.$this->db->escapeIdentifier(implode(', ', $values)).')';
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR IN condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $values
     * @param  boolean  $prepared
     */
    public function orWhereIn($column, $values, $prepared = true, $connector = ' OR ')
    {
        return $this->whereIn($column, $values, $prepared, $connector);
    }

    /**
     * AND NOT IN condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $values
     * @param  boolean  $prepared
     */
    public function whereNotIn($column, $values, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($values instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' NOT IN ('.$values->buildSelectQuery().')';
            $this->values = array_merge($this->values, $values->getConditionBuilder()->getValues());
            $this->addCondition($condition);
        } elseif(is_array($values)) {
            if($prepared) {
                if (! empty($values)) {
                    $condition .= $this->db->escapeIdentifier($column).' NOT IN ('.implode(', ', array_fill(0, count($values), '?')).')';
                    $this->addCondition($condition);
                    $this->values = array_merge($this->values, $values);
                }
            } else {
                $condition .= $this->db->escapeIdentifier($column).' NOT IN ('.$this->db->escapeIdentifier(implode(', ', $values)).')';
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR NOT IN condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $values
     * @param  boolean  $prepared
     */
    public function orWhereNotIn($column, $values, $prepared = true, $connector = ' OR ')
    {
        return $this->whereNotIn($column, $values, $prepared, $connector);
    }

    /**
     * Build a AND subquery from a Table query with an alias
     *
     * @access public
     * @param  Table  $subquery
     * @param  string  $alias
     * @return $this
     */
    public function addSubquery(Table $subquery, $alias = NULL, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);
           
        if(is_null($alias)) {
            $condition .= ' ('.$subquery->buildSelectQuery().')';
            $this->addCondition($condition);
        } else {
            $condition .= ' ('.$subquery->buildSelectQuery().') AS '.$this->db->escapeIdentifier(strtolower($alias));
            $this->addCondition($condition);
        }

        $this->values = array_merge($this->values, $subquery->getConditionBuilder()->getValues());
        return $this;
    }

    /**
     * Build a OR subquery from a Table query with an alias
     *
     * @access public
     * @param  Table  $subquery
     * @param  string  $alias
     * @return $this
     */
    public function orAddSubquery(Table $subquery, $alias = NULL, $connector = ' OR ')
    {
        return $this->addSubquery($subquery, $alias, $connector);
    }

    /**
     * AND LIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    private function like($column, $value, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('LIKE').' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('LIKE').' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= ' AND '.$this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('LIKE').' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR LIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    private function orLike($column, $value, $prepared = true, $connector = ' OR ')
    {
        return $this->like($column, $value, $prepared, $connector);
    }

    /**
     * AND NOT LIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    private function notLike($column, $value, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('LIKE').' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('LIKE').' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('LIKE').' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR NOT LIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    private function orNotLike($column, $value, $prepared = true, $connector = ' OR ')
    {
        return $this->notLike($column, $value, $prepared, $connector);
    }

    /**
     * AND ILIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    public function ilike($column, $value, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('ILIKE').' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('ILIKE').' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= $this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('ILIKE').' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR ILIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    public function orIlike($column, $value, $prepared = true, $connector = ' OR ')
    {
        return $this->ilike($column, $value, $prepared, $connector);
    }

    /**
     * AND NOT ILIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    public function notIlike($column, $value, $prepared = true, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('ILIKE').' ('.$value->buildSelectQuery().')';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            if($prepared) {
                $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('ILIKE').' ?';
                $this->addCondition($condition);
                $this->values[] = $value;
            } else {
                $condition .= $this->db->escapeIdentifier($column).' NOT '.$this->db->getDriver()->getOperator('ILIKE').' '.$this->db->escapeIdentifier($value);
                $this->addCondition($condition);
            }
        }
    }

    /**
     * OR NOT ILIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     * @param  boolean  $prepared
     */
    public function orNotIlike($column, $value, $prepared = true, $connector = ' OR ')
    {
        return $this->notIlike($column, $value, $prepared, $connector);
    }

    /**
     * AND IS NULL condition
     *
     * @access public
     * @param  mixed   $value
     */
    public function isNull($value, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= ' ('.$value->buildSelectQuery().') IS NULL';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            $condition .= ' '.$this->db->escapeIdentifier($value).' IS NULL';
            $this->addCondition($condition);
        }
    }

    /**
     * OR IS NULL condition
     *
     * @access public
     * @param  mixed   $value
     * @param  boolean  $prepared
     */
    public function orIsNull($value, $connector = ' OR ')
    {
        return $this->isNull($value, $connector);
    }

    /**
     * AND IS NOT NULL condition
     *
     * @access public
     * @param  mixed  $value
     * @param  boolean  $prepared
     */
    public function notNull($value, $connector = ' AND ')
    {
        $condition = $this->getConnector($connector);

        if($value instanceof Table) {
            $condition .= ' ('.$value->buildSelectQuery().') IS NOT NULL';
            $this->addCondition($condition);
            $this->values = array_merge($this->values, $value->getConditionBuilder()->getValues());
        } else {
            $condition .= ' '.$this->db->escapeIdentifier($value).' IS NOT NULL';
            $this->addCondition($condition);
        }
    }

    /**
     * OR IS NOT NULL condition
     *
     * @access public
     * @param  mixed  $value
     * @param  boolean  $prepared
     */
    public function orNotNull($value, $connector = ' OR ')
    {
        return $this->notNull($value, $connector);
    }
}
