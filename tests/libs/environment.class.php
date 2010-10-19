<?php

// TestEnvironment setup class. for Code Igniter

require_once dirname(__FILE__) . '/base/environment.base.class.php';

// This class swaps out pieces of current environment, to set up a common sandbox
// instead. After tests are done - it reverts environment to what it was before.
// The testing sandbox is set up to the same environment, everytime, before each
// unit test, so you can count in your tests on certain data being or not being there.

class TestEnvironment extends TestEnvironmentBase
{
    static $_instance = NULL;
    
    // This will hold DB entries, created by sandbox
    static $dbEntries = Array();
    
    function __construct()
    {
        self::$_instance = $this;
    }
    
    function getInstance()
    {
        if (self::$_instance === NULL)
            new self();
        
        return self::$_instance;
    }
    
    
    function beforeCustom()
    {
        // Do any initialization for your system environment here..
        
        // ----------------- Index.php -----------------
        error_reporting(E_ALL);
        $system_folder = "system";
        $application_folder = "../app";
        
        if (strpos($system_folder, '/') === FALSE)
        {
            if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
            {
                $system_folder = realpath(dirname(__FILE__) . '/../../').'/'.$system_folder;
            }
        }
        else
        {
            // Swap directory separators to Unix style for consistency
            $system_folder = str_replace("\\", "/", $system_folder); 
        }

        define('EXT', '.php');
        define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
        define('FCPATH', str_replace(SELF, '', __FILE__));
        define('BASEPATH', $system_folder.'/');

        if (is_dir($application_folder))
        {
            define('APPPATH', $application_folder.'/');
        }
        else
        {
            if ($application_folder == '')
            {
                $application_folder = 'application';
            }

            define('APPPATH', BASEPATH.$application_folder.'/');
        }
        
        
        // ----------------- CodeIgniter.php -----------------
        define('CI_VERSION',	'1.7.2');
        require(BASEPATH.'codeigniter/Common'.EXT);
        require(BASEPATH.'codeigniter/Compat'.EXT);
        require(APPPATH.'config/constants'.EXT);
        
        // require_once BASEPATH.'codeigniter/CodeIgniter'.EXT;
        if ( ! is_php('5.0.0'))
        {
            load_class('Loader', FALSE);
            require(BASEPATH.'codeigniter/Base4'.EXT);
        }
        else
        {
            require(BASEPATH.'codeigniter/Base5'.EXT);
        }

        // Load the base controller class
        load_class('Controller', FALSE);
        
        require_once dirname(__FILE__) . "/test_controller.php";
        $test_controller = new Test_controller();
        $test_controller->load->database();
        
        // Load everything we may need
        $test_controller->load->model(Array(
            "block", "campaigns", "error", "faq", "menu", "objects", "users", "balance"));
        $test_controller->load->library(Array(
            "hydrate"));
        
        $test_controller->testMethod();
    }
    
    function sandboxStartCustom()
    {
        self::$dbEntries = Array();
        $this->createTestDBEntries();
    }
    
    function _warning($string)
    {
        echo "{$string} <br />\n";
    }
    
    static $_datetime = FALSE;
    function getTestDBDateTime()
    {
        if (self::$_datetime === FALSE)
            self::$_datetime = date("M j Y  g:iA");
        
        return self::$_datetime;
    }
    
    static $_testDB = FALSE;
    function getTestDB()
    {
        if (self::$_testDB === FALSE)
        {
            require_once APPPATH . "models/Users.php";
            require_once APPPATH . "models/Campaigns.php";
            
            $created = $this->getTestDBDateTime();
            
            self::$_testDB = Array(
                "users" => Array(
                    Array(
                        "__name" => "client1",
                        "id" => 70,
                        "username" => "test_user@test.lt",
                        "email" => "test_user@test.lt",
                        "emails_messages" => "",
                        "messages_low_funds" => 0,
                        "messages_out_of_funds" => 0,
                        "messages_accepted_campaigns" => 0,
                        "messages_accepted_payments" => 0,
                        "messages_new_features" => 0,
                        "messages_new_bills" => 0,
                        "type" => Users::USER_TYPE_CLIENT,
                    ),
                    Array(
                        "__name" => "object1",
                        "id" => 71,
                        "username" => "test_object@test.lt",
                        "email" => "test_object@test.lt",
                        "emails_messages" => "",
                        "messages_low_funds" => 0,
                        "messages_out_of_funds" => 0,
                        "messages_accepted_campaigns" => 0,
                        "messages_accepted_payments" => 0,
                        "messages_new_features" => 0,
                        "messages_new_bills" => 0,
                        "type" => Users::USER_TYPE_OBJECT,
                    ),
                    Array(
                        "__name" => "admin1",
                        "id" => 72,
                        "username" => "test_admin@test.lt",
                        "email" => "test_admin@test.lt",
                        "emails_messages" => "",
                        "messages_low_funds" => 0,
                        "messages_out_of_funds" => 0,
                        "messages_accepted_campaigns" => 0,
                        "messages_accepted_payments" => 0,
                        "messages_new_features" => 0,
                        "messages_new_bills" => 0,
                        "type" => Users::USER_TYPE_ADMINISTRATOR,
                    ),
                ),
                "companies" => Array(
                    Array(
                        "__name" => "client1_company1",
                        "user_id" => "[users.client1]",
                        "status" => Campaigns::COMPANY_STATUS_DEFAULT,
                        "name" => "Test kliento 1 test kompanija 1",
                        "address" => "adresas",
                        "company_code" => "kodas",
                        "pvm_code" => "pvm kodas",
                        "phone" => "telefonas",
                        // "balance" => 0,
                        // "last_payment_balance" => 0,
                    ),
                ),
                "campaigns" => Array(
                    Array(
                        "__name" => "client1_company1_campaign1",
                        "company_id"    => "[companies.client1_company1]",
                        "created" => $created,
                        "approved_datetime" => NULL,
                        "name" => "Test kliento 1 test kompanijos 1 test reklaminė kampanija 1",
                        "status" => Campaigns::STATUS_DEFAULT,
                        "budget" => 0,
                        "track_objects" => 0,
                    ),
                ),
                "campaigns_objects_map" => Array(
                    Array(
                        "__name" => "campaign1_object1",
                        "campaigns_id" => "[campaigns.client1_company1_campaign1]",
                        "adresatai_id" => "[adresatai.object1]",
                        "status" => Campaigns::OBJECTS_MAP_STATUS_DEFAULT,
                        "show_time" => Campaigns::OBJECT_TIME_DEFAULT,
                    ),
                ),
                "reklama" => Array(
                    Array(
                        "__name" => "client1_company1_campaign1_clip1",
                        "ID_uzsakovo" => 0,
                        "campaign_id" => "[campaigns.client1_company1_campaign1]",
                        "data" => $created,
                        "rodyti" => NULL,
                        "pradzios_data" => NULL,
                        "pabaigos_data" => NULL,
                        "rodyti_sekundziu" => NULL,
                        "pavadinimas" => NULL,
                        "komentaras" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "tipas" => NULL,
                        "status" => Campaigns::CLIP_STATUS_DEFAULT,
                        "type" => Campaigns::CLIP_TYPE_TEXT,
                        "approved_datetime" => NULL,
                    ),
                    Array(
                        "__name" => "client1_company1_campaign1_clip2",
                        "ID_uzsakovo" => 0,
                        "campaign_id" => "[campaigns.client1_company1_campaign1]",
                        "data" => $created,
                        "rodyti" => NULL,
                        "pradzios_data" => NULL,
                        "pabaigos_data" => NULL,
                        "rodyti_sekundziu" => NULL,
                        "pavadinimas" => NULL,
                        "komentaras" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "tipas" => NULL,
                        "status" => Campaigns::CLIP_STATUS_DEFAULT,
                        "type" => Campaigns::CLIP_TYPE_TEXT,
                        "approved_datetime" => NULL,
                    ),
                ),
                "reklama_kur" => Array(
                    Array(
                        "__name" => "clip1_object1",
                        "ID_reklamos" => "[reklama.client1_company1_campaign1_clip1]",
                        "ID_adresato" => "[adresatai.object1]",
                        "darbo_laikas_nuo" => NULL,
                        "darbo_laikas_iki" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "kartai" => NULL,
                        "kartai_per_diena" => NULL,
                        "status" => Campaigns::CLIP_OBJECT_MAP_STATUS_UNSET,
                    ),
                    Array(
                        "__name" => "clip1_object2",
                        "ID_reklamos" => "[reklama.client1_company1_campaign1_clip1]",
                        "ID_adresato" => "[adresatai.object2]",
                        "darbo_laikas_nuo" => NULL,
                        "darbo_laikas_iki" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "kartai" => NULL,
                        "kartai_per_diena" => NULL,
                        "status" => Campaigns::CLIP_OBJECT_MAP_STATUS_UNSET,
                    ),
                    Array(
                        "__name" => "clip2_object1",
                        "ID_reklamos" => "[reklama.client1_company1_campaign1_clip2]",
                        "ID_adresato" => "[adresatai.object1]",
                        "darbo_laikas_nuo" => NULL,
                        "darbo_laikas_iki" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "kartai" => NULL,
                        "kartai_per_diena" => NULL,
                        "status" => Campaigns::CLIP_OBJECT_MAP_STATUS_UNSET,
                    ),
                    Array(
                        "__name" => "clip2_object2",
                        "ID_reklamos" => "[reklama.client1_company1_campaign1_clip2]",
                        "ID_adresato" => "[adresatai.object2]",
                        "darbo_laikas_nuo" => NULL,
                        "darbo_laikas_iki" => NULL,
                        "c_action" => 1,
                        "add_date" => NULL,
                        "r_date" => NULL,
                        "r_user" => NULL,
                        "kartai" => NULL,
                        "kartai_per_diena" => NULL,
                        "status" => Campaigns::CLIP_OBJECT_MAP_STATUS_UNSET,
                    ),
                ),
                "adresatai" => Array(
                    Array(
                        "__name" => "object1",
                        "user_ID" => NULL,
                        "imones_kodas" => "imones kodas", 
                        "filialas" =>  "filialas",
                        "pavadinimas" => "test objekto 1 tasko 1 pavadinimas",
                        "adresas" => NULL,
                        "tipas" => NULL,
                        "ID_grupes" => NULL,
                        "user_name" => NULL,
                        "user_psw" => NULL,
                        "add_date" => $created,
                        "r_user" => "user",
                        "r_date" => NULL,
                        "lokacija" => NULL,
                        "city_id" => "[cities.test1]",
                        // "status" => Campaigns::OBJECT_STATUS_DEFAULT,
                        "joomla_user_id" => "[users.object1]",
                        "payout" => 0,
                        "payout_peak" => 0,
                    ),
                    Array(
                        "__name" => "object2",
                        "user_ID" => NULL,
                        "imones_kodas" => "imones kodas 2", 
                        "filialas" =>  "filialas 2",
                        "pavadinimas" => "tasko 2 pavadinimas",
                        "adresas" => NULL,
                        "tipas" => NULL,
                        "ID_grupes" => NULL,
                        "user_name" => NULL,
                        "user_psw" => NULL,
                        "add_date" => $created,
                        "r_user" => "user",
                        "r_date" => NULL,
                        "lokacija" => NULL,
                        "city_id" => "[cities.test1]",
                        // "status" => Campaigns::OBJECT_STATUS_DEFAULT,
                        "joomla_user_id" => "[users.object1]",
                        "payout" => 0,
                        "payout_peak" => 0,
                    ),
                ),
                "regions" => Array(
                    Array(
                        "__name" => "test1",
                        "name" => "Regionas 1",
                    ),
                ),
                "cities" => Array(
                    Array(
                        "__name" => "test1",
                        "region_id" => "[regions.test1]",
                        "name" => "Test miestas 1",
                    ),
                ),
            );
        }
        
        return self::$_testDB;
    }
    
    static $_schema = FALSE;
    function getSchema()
    {
        if (self::$_schema === FALSE)
        {
            require_once APPPATH . "libraries/Hydrate.php";
            self::$_schema = Hydrate_schema::get();
        }
        
        return self::$_schema;
    }
    
    function createTestDBEntries()
    {
        $testDB = $this->getTestDB();
        
        foreach ($testDB as $table_k => $table)
        {
            foreach ($table as $row)
            {
                $this->insertItem($table_k, $row);
            }
        }
    }
    
    function insertItem($table, $row)
    {
        $schema = $this->getSchema();
        
        if (count($schema[$table]["primary"]) == 0)
        {
            $this->_warning("\$schema[{$table}] has no primary fields");
        }
        else
        {
            if (empty($row["__name"]))
                $this->_warning("row in table {$table} has no __name, skipping..");
            else
            {
                // If there are any relations, the corresponding related items must be created first
                foreach ($row as $field_k => $field)
                {
                    if (preg_match("/\[(.*?)\.(.*?)\]/", $field, $matches))
                    {
                        $relatedItemId = $this->getTestDBRelatedItemId($matches[1], $matches[2]);
                        if ($relatedItemId > 0)
                            $row[$field_k] = $relatedItemId;
                        else
                        {
                            $this->_warning("No such item in table {$matches[1]} with name {$matches[2]} "
                                          . "(this item is related to from another table). Skipping item..");
                            return FALSE;
                        }
                    }
                }
                
                $rowInsertData = $row;
                unset($rowInsertData["__name"]);
                
                $CI =& get_instance();
                $CI->db->insert($table, $rowInsertData);
                $id = $CI->db->insert_id();
                
                if ($id !== NULL)
                {
                    $pkField = $schema[$table]["primary"][0];
                    $row[$pkField] = $id;
                }
                
                self::$dbEntries[$table][] = $row;
                
                return $row;
            }
        }
        
        return FALSE;
    }
    
    function getTestDBRelatedItemId($table, $itemName)
    {
        $schema = $this->getSchema();
        $testDB = $this->getTestDB();
        
        $insertedItem = $this->getInsertedItem($table, $itemName);
        if ($insertedItem)
        {
            return $insertedItem[$schema[$table]["primary"][0]];
        }
        
        return FALSE;
    }
    
    // Takes an item from $this->getTestDB(), and returns the inserted item (with primary key field set)
    function getInsertedItem($table, $itemName)
    {
        // Is such an item inserted already?
        if (isset(self::$dbEntries[$table]))
            foreach (self::$dbEntries[$table] as $row)
            {
                if ($row["__name"] == $itemName)
                    return $row;
            }
        
        // Not yet inserted - insert it
        $testDB = $this->getTestDB();
        foreach ($testDB[$table] as $row)
        {
            if (isset($row["__name"]) && $row["__name"] == $itemName)
            {
                return $this->insertItem($table, $row);
            }
        }
        
        return FALSE;
    }
    
    function removeTestDBEntries()
    {
        $schema = $this->getSchema();
        // $testDB = $this->getTestDB();
        $CI =& get_instance();
        
        // foreach ($testDB as $table_k => $table)
        // {
            // if (isset(self::$dbEntries[$table_k]))
                // foreach (self::$dbEntries[$table_k] as $row_k => $row)
                // {
                    // if (count($schema[$table_k]["primary"]) > 0)
                    // {
                        // $pkField = $schema[$table_k]["primary"][0];
                        // $CI->db->where($pkField, $row[$pkField]);
                        // $CI->db->delete($table_k);
                    // }
                    
                    // unset(self::$dbEntries[$table_k][$row_k]);
                // }
        // }
        
        foreach (self::$dbEntries as $table_k => $table)
            foreach (self::$dbEntries[$table_k] as $row_k => $row)
            {
                if (count($schema[$table_k]["primary"]) > 0)
                {
                    $pkField = $schema[$table_k]["primary"][0];
                    $CI->db->where($pkField, $row[$pkField]);
                    $CI->db->delete($table_k);
                }
                
                unset(self::$dbEntries[$table_k][$row_k]);
            }
        
        // Also, consider removing new entries, that other code (code in test cases) may have created.
        // Dunno how to do that nicely though..
    }
    
    
    function sandboxEndCustom()
    {
        $this->removeTestDBEntries();
    }
}
