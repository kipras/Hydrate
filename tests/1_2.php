<?php

require_once dirname(__FILE__) . '/../../../json/json_1_1/json/json.php';

require_once(dirname(__FILE__) . '/1_2.version_to_test.php');


require_once(dirname(__FILE__) . '/simpletest/autorun.php');
// require_once(dirname(__FILE__) . '/simpletest/unit_tester.php');


chdir('..');

require_once dirname(__FILE__) . '/libs/include.php';
require_once dirname(__FILE__) . '/libs/environment.class.php';
require_once dirname(__FILE__) . '/libs/helpers.class.php';


$env = new TestEnvironment();
$env->before();


// Load test units
loadTest("base");