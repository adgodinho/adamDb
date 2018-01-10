<?php

require_once dirname(__FILE__).'/Driver/Mssql.php';
require_once dirname(__FILE__).'/Driver/Sqlite.php';
require_once dirname(__FILE__).'/Driver/Mysql.php';
require_once dirname(__FILE__).'/Driver/Postgres.php';

/**
 * Class DriverFactory
 *
 * @package PicoDb
 * @author  Frederic Guillot
 */
class DriverFactory
{
    /**
     * Get database driver from settings or environment URL
     *
     * @access public
     * @param  array $settings
     * @return Mssql|Mysql|Postgres|Sqlite
     */
    public static function getDriver($db)
    {   
        switch ($db->databaseType) {
            case 'sqlite':
                return new Sqlite($db);
            case 'mssql':
                return new Mssql($db);
            case 'mysqli':
                return new Mysql($db);
            case 'postgres7':
                return new Postgres($db);
            default:
                throw new LogicException('This database driver ('.$db->databaseType.') is not supported');
        }
    }
}
