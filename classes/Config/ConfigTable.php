<?php
/**
 ***********************************************************************************************
 * Class manages the configuration table "adm_plugin_preferences" for the Awards plugin
 ***********************************************************************************************
 */

namespace Plugins\Awards\classes\Config;

class ConfigTable
{
    public $config = array();

    protected $tableName;
    protected static $shortcut = 'AWA';
    protected static $version;
    protected static $stand;
    protected static $dbtoken;
    public $config_default = array();

    public function __construct()
    {
        global $g_tbl_praefix;

        require_once(__DIR__ . '/../../system/version.php');
        require_once(__DIR__ . '/../../system/configdata.php');

        $this->tableName = $g_tbl_praefix . '_plugin_preferences';

        if (isset($plugin_version)) {
            self::$version = $plugin_version;
        }
        if (isset($plugin_stand)) {
            self::$stand = $plugin_stand;
        }
        if (isset($dbtoken)) {
            self::$dbtoken = $dbtoken;
        }
        $this->config_default = $config_default;
    }

    public function init(): void
    {
        // check table exists
        $sql = 'SELECT * FROM ' . $this->tableName;
        $pdoStatement = $GLOBALS['gDb']->queryPrepared($sql, array(), false);

        if ($pdoStatement === false) {
            $sql = 'CREATE TABLE ' . $this->tableName . ' (
                plp_id      integer     unsigned not null AUTO_INCREMENT,
                plp_org_id  integer     unsigned not null,
                plp_name    varchar(255) not null,
                plp_value   text,
                primary key (plp_id) )
                engine = InnoDB
                auto_increment = 1
                default character set = utf8
                collate = utf8_unicode_ci';
            $GLOBALS['gDb']->queryPrepared($sql);
        }

        $this->read();

        $this->config['Plugininformationen']['version'] = self::$version;
        $this->config['Plugininformationen']['stand'] = self::$stand;

        $configIst = $this->config;

        foreach ($this->config_default as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value) {
                if (isset($configIst[$section][$key])) {
                    unset($configIst[$section][$key]);
                } else {
                    $this->config[$section][$key] = $value;
                }
            }
            if (isset($configIst[$section]) && count($configIst[$section]) === 0) {
                unset($configIst[$section]);
            }
        }

        foreach ($configIst as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value) {
                $plp_name = self::$shortcut . '__' . $section . '__' . $key;
                $sql = 'DELETE FROM ' . $this->tableName . '
                          WHERE plp_name = ?
                            AND plp_org_id = ? ';
                $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));

                unset($this->config[$section][$key]);
            }
            if (isset($this->config[$section]) && count($this->config[$section]) === 0) {
                unset($this->config[$section]);
            }
        }

        $this->save();
    }

    public function save(): void
    {
        foreach ($this->config as $section => $sectiondata) {
            foreach ($sectiondata as $key => $value) {
                if (is_array($value)) {
                    $value = '((' . implode(self::$dbtoken, $value) . '))';
                }

                $plp_name = self::$shortcut . '__' . $section . '__' . $key;

                $sql = ' SELECT plp_id
                           FROM ' . $this->tableName . '
                          WHERE plp_name = ?
                            AND ( plp_org_id = ?
                             OR plp_org_id IS NULL ) ';
                $statement = $GLOBALS['gDb']->queryPrepared($sql, array($plp_name, $GLOBALS['gCurrentOrgId']));
                $row = $statement->fetchObject();

                if (isset($row->plp_id) && strlen($row->plp_id) > 0) {
                    $sql = 'UPDATE ' . $this->tableName . '
                            SET plp_value = ?
                             WHERE plp_id = ? ';
                    $GLOBALS['gDb']->queryPrepared($sql, array($value, $row->plp_id));
                } else {
                    $sql = 'INSERT INTO ' . $this->tableName . ' (plp_org_id, plp_name, plp_value)
                            VALUES (? , ? , ?)';
                    $GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId'], $plp_name, $value));
                }
            }
        }
    }

    public function read(): void
    {
        $this->config = array();

        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE plp_org_id = ?';
        $pdoStatement = $GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId']));

        while ($row = $pdoStatement->fetch()) {
            $plp_name = explode('__', $row['plp_name']);
            $section = $plp_name[1];
            $key = $plp_name[2];

            // arrays are stored with leading and trailing double brackets
            if (substr($row['plp_value'], 0, 2) == '((' && substr($row['plp_value'], -2) == '))') {
                $row['plp_value'] = substr($row['plp_value'], 2, strlen($row['plp_value']) - 4);
                $this->config[$section][$key] = explode(self::$dbtoken, $row['plp_value']);
            } else {
                $this->config[$section][$key] = $row['plp_value'];
            }
        }
    }
}
