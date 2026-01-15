<?php
/**
 ***********************************************************************************************
 * Configuration management for Awards Plugin
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

namespace Plugins\Awards\classes\Config;

use Admidio\Infrastructure\Database\Database;

class ConfigTable
{
    private $gDb;
    private $table_name;
    public $config;
    
    public function __construct()
    {
        global $gDb;
        $this->gDb = $gDb;
        $this->table_name = TABLE_PREFIX . '_awards_config';
        $this->config = array();
    }
    
    /**
     * Check if config table exists
     */
    public function tableExists()
    {
        try {
            $sql = 'SELECT 1 FROM ' . $this->table_name . ' LIMIT 1';
            $result = $this->gDb->queryPrepared($sql, array(), false);
            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Create config table
     */
    public function createTable()
    {
        try {
            $sql = 'CREATE TABLE IF NOT EXISTS ' . $this->table_name . ' (
                        cfg_id INT AUTO_INCREMENT PRIMARY KEY,
                        cfg_org_id INT NOT NULL,
                        cfg_name VARCHAR(100) NOT NULL,
                        cfg_value TEXT,
                        cfg_type VARCHAR(20) NOT NULL DEFAULT \'string\',
                        INDEX idx_org_name (cfg_org_id, cfg_name)
                    )';
            $this->gDb->queryPrepared($sql);
            
            // Initialize default jubilee years
            $this->setDefaultJubileeYears();
        } catch (\Exception $e) {
            // Table creation failed, log error but continue
            error_log('Awards Plugin: Failed to create config table - ' . $e->getMessage());
        }
    }
    
    /**
     * Set default jubilee years configuration
     */
    private function setDefaultJubileeYears()
    {
        try {
            global $gCurrentOrganization;
            $orgId = $gCurrentOrganization->getValue('org_id');
            
            // Check if already exists
            $sql = 'SELECT cfg_value FROM ' . $this->table_name . '
                     WHERE cfg_org_id = ? AND cfg_name = ?';
            $statement = $this->gDb->queryPrepared($sql, array($orgId, 'jubilee_years'));
            
            if ($statement === false || $statement->rowCount() == 0) {
                // Insert default years: 25, 40, 50, 60, 70
                $defaultYears = json_encode(array(25, 40, 50, 60, 70));
                $sql = 'INSERT INTO ' . $this->table_name . ' 
                        (cfg_org_id, cfg_name, cfg_value, cfg_type) 
                        VALUES (?, ?, ?, ?)';
                $this->gDb->queryPrepared($sql, array($orgId, 'jubilee_years', $defaultYears, 'json'));
            }
        } catch (\Exception $e) {
            // Failed to set default jubilee years, log error but continue
            error_log('Awards Plugin: Failed to set default jubilee years - ' . $e->getMessage());
        }
    }
    
    /**
     * Read configuration from database
     */
    public function read()
    {
        global $gCurrentOrganization;
        $orgId = $gCurrentOrganization->getValue('org_id');
        
        if (!$this->tableExists()) {
            $this->createTable();
        }
        
        $sql = 'SELECT cfg_name, cfg_value, cfg_type 
                  FROM ' . $this->table_name . '
                 WHERE cfg_org_id = ?';
        
        try {
            $statement = $this->gDb->queryPrepared($sql, array($orgId));
            
            if ($statement === false) {
                // Query failed, return with empty config
                $this->config['jubilee_years'] = array(25, 40, 50, 60, 70);
                return;
            }
            
            while ($row = $statement->fetch()) {
                $value = $row['cfg_value'];
                
                // Convert based on type
                if ($row['cfg_type'] === 'json') {
                    $value = json_decode($value, true);
                } elseif ($row['cfg_type'] === 'int') {
                    $value = (int)$value;
                } elseif ($row['cfg_type'] === 'bool') {
                    $value = (bool)$value;
                }
                
                $this->config[$row['cfg_name']] = $value;
            }
            
            // Ensure jubilee_years exists
            if (!isset($this->config['jubilee_years'])) {
                $this->config['jubilee_years'] = array(25, 40, 50, 60, 70);
                $this->save('jubilee_years', $this->config['jubilee_years'], 'json');
            }
        } catch (\Exception $e) {
            // If query fails, set default config
            $this->config['jubilee_years'] = array(25, 40, 50, 60, 70);
        }
    }
    
    /**
     * Save configuration value
     */
    public function save($name, $value, $type = 'string')
    {
        try {
            global $gCurrentOrganization;
            
            if (!$gCurrentOrganization) {
                error_log('Awards Plugin: gCurrentOrganization is not set');
                return false;
            }
            
            $orgId = $gCurrentOrganization->getValue('org_id');
            error_log('Awards Plugin: save() called - name=' . $name . ', type=' . $type . ', orgId=' . $orgId);
            
            // Ensure table exists before attempting to save
            if (!$this->tableExists()) {
                error_log('Awards Plugin: Table does not exist, creating...');
                $this->createTable();
            }
            
            // Convert value based on type
            if ($type === 'json') {
                $jsonValue = json_encode($value);
                error_log('Awards Plugin: JSON value to save: ' . $jsonValue);
                $value = $jsonValue;
            }
            
            // Check if exists
            $sql = 'SELECT cfg_id FROM ' . $this->table_name . '
                     WHERE cfg_org_id = ? AND cfg_name = ?';
            error_log('Awards Plugin: Checking if config exists with SQL: ' . $sql);
            $statement = $this->gDb->queryPrepared($sql, array($orgId, $name));
            
            if ($statement === false) {
                error_log('Awards Plugin: SELECT query failed - checking database error');
                // Get database error if available
                if (method_exists($this->gDb, 'getError')) {
                    error_log('Awards Plugin: DB Error: ' . $this->gDb->getError());
                }
                return false;
            }
            
            $rowCount = $statement->rowCount();
            error_log('Awards Plugin: Found ' . $rowCount . ' existing rows');
            
            if ($rowCount > 0) {
                // Update
                $sql = 'UPDATE ' . $this->table_name . ' 
                           SET cfg_value = ?, cfg_type = ?
                         WHERE cfg_org_id = ? AND cfg_name = ?';
                error_log('Awards Plugin: Updating with SQL: ' . $sql . ' with params: [' . substr($value, 0, 100) . ', ' . $type . ', ' . $orgId . ', ' . $name . ']');
                $updateStatement = $this->gDb->queryPrepared($sql, array($value, $type, $orgId, $name));
                if ($updateStatement === false) {
                    error_log('Awards Plugin: UPDATE query failed');
                    if (method_exists($this->gDb, 'getError')) {
                        error_log('Awards Plugin: DB Error: ' . $this->gDb->getError());
                    }
                    return false;
                }
                error_log('Awards Plugin: UPDATE successful');
            } else {
                // Insert
                $sql = 'INSERT INTO ' . $this->table_name . ' 
                        (cfg_org_id, cfg_name, cfg_value, cfg_type) 
                        VALUES (?, ?, ?, ?)';
                error_log('Awards Plugin: Inserting with SQL: ' . $sql);
                $insertStatement = $this->gDb->queryPrepared($sql, array($orgId, $name, $value, $type));
                if ($insertStatement === false) {
                    error_log('Awards Plugin: INSERT query failed');
                    if (method_exists($this->gDb, 'getError')) {
                        error_log('Awards Plugin: DB Error: ' . $this->gDb->getError());
                    }
                    return false;
                }
                error_log('Awards Plugin: INSERT successful');
            }
            
            // Update in-memory config
            if ($type === 'json') {
                $this->config[$name] = json_decode($value, true);
            } else {
                $this->config[$name] = $value;
            }
            
            error_log('Awards Plugin: save() completed successfully');
            return true;
        } catch (\Exception $e) {
            error_log('Awards Plugin: Exception in save() - ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Get jubilee years as sorted array
     */
    public function getJubileeYears()
    {
        if (!isset($this->config['jubilee_years']) || !is_array($this->config['jubilee_years'])) {
            return array(25, 40, 50, 60, 70);
        }
        
        $years = $this->config['jubilee_years'];
        // Ensure all values are integers
        $years = array_map('intval', $years);
        sort($years, SORT_NUMERIC);
        return $years;
    }
    
    /**
     * Add a jubilee year
     */
    public function addJubileeYear($year)
    {
        $years = $this->getJubileeYears();
        $year = (int)$year; // Ensure consistent type
        
        error_log('Awards Plugin: Adding year ' . $year . ' to existing years: ' . json_encode($years));
        
        // Check if year already exists with type-safe comparison
        $exists = false;
        foreach ($years as $existingYear) {
            if ((int)$existingYear === $year) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $years[] = $year;
            sort($years, SORT_NUMERIC);
            error_log('Awards Plugin: Saving updated years: ' . json_encode($years));
            $result = $this->save('jubilee_years', $years, 'json');
            error_log('Awards Plugin: Save result: ' . ($result ? 'success' : 'failed'));
            return $result !== false;
        }
        
        error_log('Awards Plugin: Year ' . $year . ' already exists');
        return false;
    }
    
    /**
     * Remove a jubilee year
     */
    public function removeJubileeYear($year)
    {
        $years = $this->getJubileeYears();
        $year = (int)$year; // Ensure consistent type
        
        error_log('Awards Plugin: Removing year ' . $year . ' from existing years: ' . json_encode($years));
        
        $originalCount = count($years);
        
        // Filter out the year to remove, ensuring type-safe comparison
        $years = array_filter($years, function($existingYear) use ($year) {
            return (int)$existingYear !== $year;
        });
        
        // Check if anything was removed
        if (count($years) < $originalCount) {
            $years = array_values($years); // Re-index array
            sort($years, SORT_NUMERIC);
            error_log('Awards Plugin: Saving updated years: ' . json_encode($years));
            $result = $this->save('jubilee_years', $years, 'json');
            error_log('Awards Plugin: Save result: ' . ($result ? 'success' : 'failed'));
            return $result !== false;
        }
        
        error_log('Awards Plugin: Year ' . $year . ' not found in array');
        return false;
    }
}
