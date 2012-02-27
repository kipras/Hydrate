<?php

function loadTest($testName)
{
    require_once(dirname(__FILE__) . "/../tests/{$testName}.test.php");
}

function db_decode($data)
{
    return $data;
}

// For backwards-compatibility with Hydrate 1.2.x
function trace()
{
    st();
}


$pathToLibs = dirname(__FILE__) . '/../../../../../';

// Additional libraries, from libs
require_once $pathToLibs . 'libraries/database/database.lib/database.lib.php';
require_once $pathToLibs . 'libraries/database/db.class.ciar.php';


// Additional functions, from libs
require_once $pathToLibs . 'functions/database/export_db.function.php';
require_once $pathToLibs . 'functions/array/arrays_identical.php';
require_once $pathToLibs . 'functions/array/arrays_diff.php';
require_once $pathToLibs . 'functions/debug/e.php';
require_once $pathToLibs . 'functions/debug/st.php';
require_once $pathToLibs . 'functions/io/recursive_directory_scan.php';
require_once $pathToLibs . 'functions/debug/timer.php';




