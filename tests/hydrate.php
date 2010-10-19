<?php

require_once(dirname(__FILE__) . '/simpletest/autorun.php');
// require_once(dirname(__FILE__) . '/simpletest/unit_tester.php');


chdir('..');

require_once dirname(__FILE__) . '/libs/environment.class.php';
require_once dirname(__FILE__) . '/libs/helpers.class.php';


$env = new TestEnvironment();
$env->before();

// Load test units
require_once(dirname(__FILE__) . '/hydrate.test.php');

?>
