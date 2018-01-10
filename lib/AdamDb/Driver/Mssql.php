<?php

require_once dirname(__FILE__).'/Base.php';

/**
 * Microsoft SQL Server Driver
 *
 * @package PicoDb\Driver
 * @author  Algy Taylor <thomas.taylor@cmft.nhs.uk>
 */
class Mssql extends Base
{
    /**
     * Table to store the schema version
     *
     * @access private
     * @var array
     */
    private $schemaTable = 'schema_version';

    /**
     * Create a new ADOdb connection
     *
     * @access public
     * @param  array   $settings
     */
    public function createConnection($db)
    {
        $this->adodb = $db;
    }

    /**
     * Enable foreign keys
     *
     * @access public
     */
    public function enableForeignKeys()
    {
        $this->adodb->execute('EXEC sp_MSforeachtable @command1="ALTER TABLE ? CHECK CONSTRAINT ALL"; GO;');
    }

    /**
     * Disable foreign keys
     *
     * @access public
     */
    public function disableForeignKeys()
    {
        $this->adodb->execute('EXEC sp_MSforeachtable @command1="ALTER TABLE ? NOCHECK CONSTRAINT ALL"; GO;');
    }

    /**
     * Return true if the error code is a duplicate key
     *
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    public function isDuplicateKeyError($code)
    {
        return $code == 2601;
    }

    /**
     * Escape identifier
     *
     * https://msdn.microsoft.com/en-us/library/ms175874.aspx
     *
     * @access public
     * @param  string  $identifier
     * @return string
     */
    public function escape($identifier)
    {
        return $identifier;
    }


    /**
     * Cast value
     *
     * @access public
     * @param  string  $value
     * @param  string  $type
     * @return string
     */
    public function cast($value, $type, $option = NULL)
    {
        $value = strtolower($value);
        switch ($type) {
            case 'int':
                return 'CAST ('.$value.' AS INT)';
                break;
             case 'double':
                return 'CAST ('.$value.' AS DOUBLE)';
                break;
            case 'varchar':
                if(is_null($option)) {
                    return 'CAST ('.$value.' AS VARCHAR)';
                } else {
                    return 'CAST ('.$value.' AS VARCHAR('.$option.'))';
                }
                break;
            case 'date_iso':
                return 'CONVERT (VARCHAR, '.$value.', 112)';
                break;
            case 'date_br':
                return 'CONVERT (VARCHAR, '.$value.', 103)';
                break;
            case 'to_date_iso':
                return 'CONVERT (DATETIME, '.$value.', 112)';
                break;
            case 'to_date_br':
                return 'CONVERT (DATETIME, '.$value.', 103)';
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * Date difference
     *
     * @access public
     * @param  string  $diff
     * @param  string  $date1
     * @param  string  $date2
     * @return string
     */
    public function datediff($diff, $date1, $date2)
    {
        $value = strtolower($value);
        switch ($diff) {
        case 'year':
            return "DATEDIFF(year, '".$date1."', '".$date2."')";
            break;
        case 'month':
            return "DATEDIFF(month, '".$date1."', '".$date2."')";
            break;
        case 'day':
            return "DATEDIFF(day, '".substr_replace($date1, '00:00:00', 11, 8)."', '".substr_replace($date2, '00:00:00', 11, 8)."')";
            break;
        case 'week':
            return "DATEDIFF(week, '".$date1."', '".$date2."')";
            break;
        case 'hour':
            return "DATEDIFF(hour, '".$date1."', '".$date2."')";
            break;
        case 'minute':
            return "DATEDIFF(minute, '".$date1."', '".$date2."')";
            break;
        case 'second':
            return "DATEDIFF(second, '".$date1."', '".$date2."')";
            break;
        }
    }

    /**
     * Current date value
     *
     * @access public
     * @return string
     */
    public function date()
    {
    	return 'CONVERT (date, GETDATE())';
    }
	
	/**
     * Current timestamp value
     *
     * @access public
     * @return string
     */
    public function timestamp()
    {
    	return 'GETDATE()';
    }

    /**
     * Get non standard operator
     *
     * @access public
     * @param  string  $operator
     * @return string
     */
    public function getOperator($operator)
    {
        if ($operator === 'LIKE' || $operator === 'ILIKE') {
            return 'LIKE';
        }

        return '';
    }

    /**
     * Get last inserted id
     *
     * @access public
     * @return integer
     */
    public function getLastId()
    {
        return $this->adodb->lastInsertId();
    }

    /**
     * Get current schema version
     *
     * @access public
     * @return integer
     */
    public function getSchemaVersion()
    {
        $this->adodb->execute("CREATE TABLE IF NOT EXISTS [".$this->schemaTable."] ([version] INT DEFAULT '0')");

        $rq = $this->adodb->prepare('SELECT [version] FROM ['.$this->schemaTable.']');
        $rq->execute();
        $result = $rq->fetchColumn();

        if ($result !== false) {
            return (int) $result;
        }
        else {
            $this->adodb->execute('INSERT INTO ['.$this->schemaTable.'] VALUES(0)');
        }

        return 0;
    }

    /**
     * Set current schema version
     *
     * @access public
     * @param  integer  $version
     */
    public function setSchemaVersion($version)
    {
        $rq = $this->adodb->prepare('UPDATE ['.$this->schemaTable.'] SET [version]=?');
        $rq->execute(array($version));
    }

    /**
     * Run EXPLAIN command
     *
     * @param  string $sql
     * @param  array  $values
     * @return array
     */
    public function explain($sql, array $values)
    {
        $this->getConnection()->execute('SET SHOWPLAN_ALL ON');
        return $this->getConnection()->query($this->getSqlFromPreparedStatement($sql, $values))->fetchAll(ADOdb::FETCH_ASSOC);
    }
}