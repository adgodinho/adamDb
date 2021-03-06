<?php

require_once dirname(__FILE__).'/Driver/Mssql.php';
require_once dirname(__FILE__).'/Driver/Sqlite.php';
require_once dirname(__FILE__).'/Driver/Mysql.php';
require_once dirname(__FILE__).'/Driver/Postgres.php';
require_once dirname(__FILE__).'/Builder/CaseConditionBuilder.php';
require_once dirname(__FILE__).'/DriverFactory.php';
require_once dirname(__FILE__).'/Table.php';
require_once dirname(__FILE__).'/Hashtable.php';
require_once dirname(__FILE__).'/LargeObject.php';
require_once dirname(__FILE__).'/Schema.php';
require_once dirname(__FILE__).'/SQLException.php';
require_once dirname(__FILE__).'/StatementHandler.php';
require_once dirname(__FILE__).'/UrlParser.php';

/**
 * Database
 *
 * @package PicoDb
 * @author  Frederic Guillot
 */
class Database
{
    /**
     * Database instances
     *
     * @static
     * @access private
     * @var array
     */
    private static $instances = array();

    /**
     * Statement object
     *
     * @access protected
     * @var StatementHandler
     */
    protected $statementHandler;

    /**
     * Queries logs
     *
     * @access private
     * @var array
     */
    private $logs = array();

    /**
     * Driver instance
     *
     * @access private
     */
    private $driver;

    /**
     * In Transaction Flag
     *
     * @access private
     */
    private $inTransaction;

    /**
     * Initialize the driver
     *
     * @access public
     * @param  array   $settings
     */
    public function __construct($db)
    {
        $this->driver = DriverFactory::getDriver($db);
        $this->statementHandler = new StatementHandler($this);
        $this->inTransaction = false;
    }

    /**
     * Destructor
     *
     * @access public
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Register a new database instance
     *
     * @static
     * @access public
     * @param  string    $name        Instance name
     * @param  Closure   $callback    Callback
     */
    public static function setInstance($name, Closure $callback)
    {
        self::$instances[$name] = $callback;
    }

    /**
     * Get a database instance
     *
     * @static
     * @access public
     * @param  string    $name   Instance name
     * @return Database
     */
    public static function getInstance($name)
    {
        if (! isset(self::$instances[$name])) {
            throw new LogicException('No database instance created with that name');
        }

        if (is_callable(self::$instances[$name])) {
            self::$instances[$name] = call_user_func(self::$instances[$name]);
        }

        return self::$instances[$name];
    }

    /**
     * Add a log message
     *
     * @access public
     * @param  mixed $message
     * @return Database
     */
    public function setLogMessage($message)
    {
        $this->logs[] = is_array($message) ? var_export($message, true) : $message;
        return $this;
    }

    /**
     * Add many log messages
     *
     * @access public
     * @param  array $messages
     * @return Database
     */
    public function setLogMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->setLogMessage($message);
        }

        return $this;
    }

    /**
     * Get all queries logs
     *
     * @access public
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logs;
    }

    /**
     * Get the PDO connection
     *
     * @access public
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->driver->getConnection();
    }

    /**
     * Get the Driver instance
     *
     * @access public
     * @return Mssql|Sqlite|Postgres|Mysql
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the last inserted id
     *
     * @access public
     * @return integer
     */
    public function getLastId()
    {
        return (int) $this->driver->getLastId();
    }

    /**
     * Get statement object
     *
     * @access public
     * @return StatementHandler
     */
    public function getStatementHandler()
    {
        return $this->statementHandler;
    }

    /**
     * Enable query debugging
     *
     * @access public
     * @return $this
     */
    public function debug($value = true)
    {
        $this->getConnection()->debug = $value;
    }

    /**
     * Release the PDO connection
     *
     * @access public
     */
    public function closeConnection()
    {
        $this->driver->closeConnection();
    }

    /**
     * Escape an identifier (column, table name...)
     *
     * @access public
     * @param  string    $value    Value
     * @param  string    $table    Table name
     * @return string
     */
    public function escapeIdentifier($value, $table = '')
    {
        // Do not escape custom query
        if (strpos($value, '.') !== false || strpos($value, ' ') !== false) {
            return $value;
        }

        if (! empty($table)) {
            return $this->driver->escape($table).'.'.$this->driver->escape($value);
        }

        return $this->driver->escape($value);
    }

    /**
     * Escape an identifier list
     *
     * @access public
     * @param  array     $identifiers  List of identifiers
     * @param  string    $table        Table name
     * @return string[]
     */
    public function escapeIdentifierList(array $identifiers, $table = '')
    {
        foreach ($identifiers as $key => $value) {
            $identifiers[$key] = $this->escapeIdentifier($value, $table);
        }

        return $identifiers;
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
        return $this->driver->cast($value, $type, $option);
    }

    /**
     * Current date value
     *
     * @access public
     * @return string
     */
    public function date()
    {
        return $this->driver->date();
    }

    /**
     * Current timestamp value
     *
     * @access public
     * @return string
     */
    public function timestamp()
    {
        return $this->driver->timestamp();
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
        return $this->driver->datediff($diff, $date1, $date2);
    }

    /**
     * Execute a prepared statement
     *
     * Note: returns false on duplicate keys instead of SQLException
     *
     * @access public
     * @param  string    $sql      SQL query
     * @param  array     $values   Values
     * @return \PDOStatement|false
     */
    public function execute($sql, array $values = array())
    {
        return $this->statementHandler
            ->withSql($sql)
            ->withPositionalParams($values)
            ->execute();
    }

    /**
     * Run a transaction
     *
     * @access public
     * @param  Closure    $callback     Callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        try {

            $this->startTransaction();
            $result = $callback($this);
            $this->closeTransaction();

            return $result === null ? true : $result;
        } catch (ADODB_Exception $e) {
            return $this->statementHandler->handleSqlError($e);
        }
    }

    /**
     * Begin a transaction
     *
     * @access public
     */
    public function startTransaction()
    {
        if (!$this->inTransaction) {
            $this->inTransaction = true;
            $this->getConnection()->beginTrans();
        }
    }

    /**
     * Commit a transaction
     *
     * @access public
     */
    public function closeTransaction()
    {
        if ($this->inTransaction) {
            $this->getConnection()->commitTrans();
            $this->inTransaction = false;
        }
    }

    /**
     * Rollback a transaction
     *
     * @access public
     */
    public function cancelTransaction()
    {
        if ($this->inTransaction) {
            $this->getConnection()->rollbackTrans();
            $this->inTransaction = false;
        }
    }

    /**
     * Get a table object
     *
     * @access public
     * @param  string $table
     * @return Table
     */
    public function table($table)
    {
        return new Table($this, $table);
    }

    /**
     * Get a hashtable object
     *
     * @access public
     * @param  string $table
     * @return Hashtable
     */
    public function hashtable($table)
    {
        return new Hashtable($this, $table);
    }

    /**
     * Get a LOB object
     *
     * @access public
     * @param  string $table
     * @return LargeObject
     */
    public function largeObject($table)
    {
        return new LargeObject($this, $table);
    }

    /**
     * Get a schema object
     *
     * @access public
     * @param  string $namespace
     * @return Schema
     */
    public function schema($namespace = null)
    {
        $schema = new Schema($this);

        if ($namespace !== null) {
            $schema->setNamespace($namespace);
        }

        return $schema;
    }
}
