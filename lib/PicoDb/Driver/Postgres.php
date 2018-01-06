<?php

require_once dirname(__FILE__).'/Base.php';

/**
 * Postgres Driver
 *
 * @package PicoDb\Driver
 * @author  Frederic Guillot
 */
class Postgres extends Base
{
    /**
     * List of required settings options
     *
     * @access protected
     * @var array
     */
    protected $requiredAttributes = array(
        'hostname',
        'username',
        'password',
        'database',
    );

    /**
     * Table to store the schema version
     *
     * @access private
     * @var array
     */
    private $schemaTable = 'schema_version';

    /**
     * Create a new PDO connection
     *
     * @access public
     * @param  array   $settings
     */
    public function createConnection(array $settings)
    {
        $dsn = 'pgsql:host='.$settings['hostname'].';dbname='.$settings['database'];

        if (! empty($settings['port'])) {
            $dsn .= ';port='.$settings['port'];
        }

        $this->pdo = new PDO($dsn, $settings['username'], $settings['password']);

        if (isset($settings['schema_table'])) {
            $this->schemaTable = $settings['schema_table'];
        }
    }

    /**
     * Enable foreign keys
     *
     * @access public
     */
    public function enableForeignKeys()
    {
    }

    /**
     * Disable foreign keys
     *
     * @access public
     */
    public function disableForeignKeys()
    {
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
        return $code == 23505 || $code == 23503;
    }

    /**
     * Escape identifier
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
                return 'CAST ('.$value.' AS INTEGER)';
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
                return "TO_CHAR(".$value.", 'YYYYMMDD')";
                break;
            case 'date_br':
                return "TO_CHAR(".$value.", 'DD/MM/YYYY')";
                break;
            case 'to_date_iso':
                return "TO_DATE(".$value.", 'YYYYMMDD')";
                break;
            case 'to_date_br':
                return "TO_DATE(".$value.", 'DD/MM/YYYY')";
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * Current date value
     *
     * @abstract
     * @access public
     * @return string
     */
    public function date() {
        return 'current_date';
    }
    
    /**
     * Current timestamp value
     *
     * @access public
     * @return string
     */
    public function timestamp() {
        return 'current_timestamp';
    }

    /**
     * Date difference
     *
     * @access public
     * @param  string  $diff
     * @param  string  $date2
     * @param  string  $date2
     * @return string
     */
    public function datediff($diff, $date1, $date2)
    {
        $value = strtolower($value);
        switch ($diff) {
        case 'year':
            return "DATE_PART('year', '".$date2."'::date) - DATE_PART('year', '".$date1."'::date)";
            break;
        case 'month':
            return "(DATE_PART('year', '".$date2."'::date) - DATE_PART('year', '".$date1."'::date)) * 12 + 
                    (DATE_PART('month', '".$date2."'::date) - DATE_PART('month', '".$date1."'::date))";
            break;
        case 'day':
            return "DATE_PART('day', '".substr_replace($date2, '00:00:00', 11, 8)."'::timestamp - '".substr_replace($date1, '00:00:00', 11, 8)."'::timestamp)";
            break;
        case 'week':
            return "TRUNC(DATE_PART('day', '".$date2."'::timestamp - '".$date1."'::timestamp)/7)";
            break;
        case 'hour':
            return "DATE_PART('day', '".$date2."'::timestamp - '".$date1."'::timestamp) * 24 + 
                    DATE_PART('hour', '".$date2."'::timestamp - '".$date1."'::timestamp)";
            break;
        case 'minute':
            return "(DATE_PART('day', '".$date2."'::timestamp - '".$date1."'::timestamp) * 24 + 
                    DATE_PART('hour', '".$date2."'::timestamp - '".$date1."'::timestamp)) * 60 +
                    DATE_PART('minute', '".$date2."'::timestamp - '".$date1."'::timestamp)";
            break;
        case 'second':
            return "((DATE_PART('day', '".$date2."'::timestamp - '".$date1."'::timestamp) * 24 + 
                    DATE_PART('hour', '".$date2."'::timestamp - '".$date1."'::timestamp)) * 60 +
                    DATE_PART('minute', '".$date2."'::timestamp - '".$date1."'::timestamp)) * 60 +
                    DATE_PART('second', '".$date2."'::timestamp - '".$date1."'::timestamp)";
            break;
        }
    }

    /**
     * Get non standard operator
     *
     * @param  string  $operator
     * @return string
     */
    public function getOperator($operator)
    {
        if ($operator === 'LIKE') {
            return 'LIKE';
        }
        else if ($operator === 'ILIKE') {
            return 'ILIKE';
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
        try {
            $rq = $this->pdo->prepare('SELECT LASTVAL()');
            $rq->execute();

            return $rq->fetchColumn();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get current schema version
     *
     * @access public
     * @return integer
     */
    public function getSchemaVersion()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS ".$this->schemaTable." (version INTEGER DEFAULT 0)");

        $rq = $this->pdo->prepare('SELECT "version" FROM "'.$this->schemaTable.'"');
        $rq->execute();
        $result = $rq->fetchColumn();

        if ($result !== false) {
            return (int) $result;
        }
        else {
            $this->pdo->exec('INSERT INTO '.$this->schemaTable.' VALUES(0)');
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
        $rq = $this->pdo->prepare('UPDATE '.$this->schemaTable.' SET version=?');
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
        return $this->getConnection()->query('EXPLAIN (FORMAT YAML) '.$this->getSqlFromPreparedStatement($sql, $values))->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get database version
     *
     * @access public
     * @return array
     */
    public function getDatabaseVersion()
    {
        return $this->getConnection()->query('SHOW server_version')->fetchColumn();
    }
}
