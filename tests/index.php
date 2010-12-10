<?php

require_once dirname(__FILE__) . '/../../../json_1_1/json/json.php';

require_once(dirname(__FILE__) . '/version_to_test.php');


require_once(dirname(__FILE__) . '/simpletest/autorun.php');
// require_once(dirname(__FILE__) . '/simpletest/unit_tester.php');


chdir('..');

require_once dirname(__FILE__) . '/libs/include.php';
require_once dirname(__FILE__) . '/libs/environment.class.php';
require_once dirname(__FILE__) . '/libs/helpers.class.php';


$env = new TestEnvironment();
$env->before();


// Make database schema, so we don't have to edit it manually, when things change
$CI =& get_instance();
file_put_contents(dirname(__FILE__) . "/sql/" . date("Y_m_d__H_i_s") . ".sql"
                , export_db(
                    $CI->db->hostname
                  , $CI->db->username
                  , $CI->db->password
                  , $CI->db->database
));


// Load test units
loadTest("hydrate");