<?php

// --------------------------------- APP FUNCTIONS ---------------------------------

// Almost identical to uri_string(), except that uri_string() returns current
// uri, starting with (/), which is a bit problematic.
// This function removes this leading slash.
function uri()
{
    $uri = uri_string();
    if (strlen($uri) > 0 AND $uri[0] == "/")
        $uri = substr($uri, 1);
    return $uri;
}

function uriChangeParam($param, $value = FALSE)
{
    $CI =& get_instance();
    $uri = $CI->_uri;
    $params = $CI->_params;
    
    if ($value === FALSE)
        unset($params[$param]);
    else
        $params[$param] = $value;
    
    $paramsStr = "";
    foreach ($params as $k => $v)
        $paramsStr .= "/{$k}/{$v}";
    
    return $uri . $paramsStr;
}

function emailStart()
{
    $CI =& get_instance();
    $CI->load->library('email');
    $CI->email->clear();
}

function systemEmailStart()
{
    emailStart();
    
    $CI =& get_instance();
    $CI->email->from($CI->config->item("siteEmail"), $CI->config->item("siteName"));
}

function billname($paymentId)
{
    return sprintf("%08d", $paymentId);
}

function db_encode($val)
{
    if (is_array($val))
        foreach ($val as $row_k => $row)
            $val[$row_k] = db_encode($row);
    else
        $val = lt_utf8_reduce($val);
    
    return $val;
}

function db_decode($val)
{
    if (is_array($val))
        foreach ($val as $row_k => $row)
            $val[$row_k] = db_decode($row);
    else
        $val = lt_to_utf8($val);
    
    return $val;
}

// MS SQL uses a different date format. Use this function to transform dates to the standard dates.
// NOTE: doing this manually, instead of a simple date("Y-m-d H:i:s", strtotime($date)), because
// that does not work on the server for some reason (strtotime() returns FALSE).
function stdDate($mssqlDate)
{
    // date can be returned from MSSQL either as:
    // Nov 18 2010  3:10PM (localhost)
    // Nov 18 2010 03:10:17:000PM (server - i'm assuming probably because of FreeTDS)
    
    $time = strtotime($mssqlDate);
    
    // Localhost - handled by strtotime()
    if ($time !== FALSE AND $time !== -1)
        return date("Y-m-d H:i:s", $time);
    
    // Server - handled manually
    if ($mssqlDate)
    {
        $dateArr = explode(" ", shorten_whitespace(trim($mssqlDate)));
        $monthNameShort = $dateArr[0];
        $month = date('n', strtotime($monthNameShort));
        $day = $dateArr[1];
        $year = $dateArr[2];
        
        $timeArr = explode(":", $dateArr[3]);
        $pm = substr($dateArr[3], strlen($dateArr[3]) - 2) == "PM" ? TRUE : FALSE;
        $hour = $timeArr[0] + $pm ? 12 : 0;
        $minute = $timeArr[1];
        $second = $timeArr[2];
        
        return date("Y-m-d H:i:s", mktime($hour, $minute, $second, $month, $day, $year));
    }
    
    return FALSE;
}

function msSqlDate($date)
{
    $CI =& get_instance();
    
    if ($CI->config->item("server_name") == "localhost")
    {
        $time = strtotime($date);
        $getdate = getdate($time);
        $hours12 = $getdate["hours"] % 12;
        if ($hours12 % 12 >= 10 OR $hours12 == 0)
            return date("M j Y g:iA", $time);
        else
            return date("M j Y  g:iA", $time);
    }
        
    else
        return date("M j Y h:i:s:000A", strtotime($date));
}



// --------------------------------- COMMON FUNCTIONS ---------------------------------

function prepare_input($data)
{
    if (!empty($data))
    {
        if (is_array($data))
            foreach ($data as $k => $v)
                $data[$k] = prepare_input($v);
        else  
            $data = trim(strip_tags($data));
        return $data;
    } else return $data;
}

function prepare_text_input($data)
{
    if (!empty($data))
    {
        if (is_array($data))
            foreach ($data as $k => $v)
                $data[$k] = prepare_input($v);
        else  
            $data = trim($data);
        return $data;
    } else return $data;
}

// POST from PHP with no cURL, plainly using the PHP streams layer.
//
// PHP >= 5
//
function do_post_request($url, $data, $optional_headers = null)
{
    $data = http_build_query($data);
    $params = array('http' => array(
        'method'    => 'POST',
        'header'    => 'Content-Type: application/x-www-form-urlencoded',
        'content'   => $data,
    ));
    if ($optional_headers !== null) {
        $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', FALSE, $ctx);
    if (!$fp) {
        // throw new Exception("Problem with $url, $php_errormsg");
        return FALSE;
    }
    $response = @stream_get_contents($fp);
    if ($response === FALSE) {
        // throw new Exception("Problem reading data from $url, $php_errormsg");
        return FALSE;
    }
    return $response;
}

// A more readable print_r()
function e($arr, $return = FALSE)
{
    if ($arr === NULL)
        $text = 'NULL<br />';
    else
    {
        $text = print_r($arr, TRUE);
        $text = "<pre>\n{$text}</pre>\n\n";
    }
    
    if ($return) return $text; else echo $text;
}

function getCachedValue(&$var, $getterFunction)
{
    if ($var === NULL)
    {
        $args = func_get_args();
        array_shift($args); // $var
        array_shift($args); // $getterFunction
        $var = call_user_func_array($getterFunction, $args);
    }
    
    return $var;
}

// Jei tekste vietoje lietuvisku raidziu matote tokius simbolius: Ä… Ä Ä™ ir pan., reiskia blogai buvo suzaista su koduotemis -
// tekstas kuris buvo uzkoduotas utf-8, atidarytas ar persaugotas ar irasytas kaip ansi ar kita ne utf-8 koduote.
// Todel tie simboliai kurie reiske lt raides patapo tokiais hieroglifais.
// Cia yra daznai pasitaikanti problema importuojant duombazes ir pan.
// Praleiskite teksto kintamuosius kurie yra paveikti to, pro sita funkcija ir persaugokite kad pataisytumete.
function lt_utf8_reduce($string)
{
    $lt_transform = Array(
        chr(196).chr(133) => chr(224), // ą
        chr(196).chr(141) => chr(232), // č
        chr(196).chr(153) => chr(230), // ę
        chr(196).chr(151) => chr(235), // ė
        chr(196).chr(175) => chr(225), // į
        chr(197).chr(161) => chr(240), // š
        chr(197).chr(179) => chr(248), // ų
        chr(197).chr(171) => chr(251), // ū
        chr(197).chr(190) => chr(254), // ž
        chr(196).chr(132) => chr(192), // Ą
        chr(196).chr(140) => chr(200), // Č
        chr(196).chr(152) => chr(198), // Ę
        chr(196).chr(150) => chr(203), // Ė
        chr(196).chr(174) => chr(193), // Į
        chr(197).chr(160) => chr(208), // Š
        chr(197).chr(178) => chr(216), // Ų
        chr(197).chr(170) => chr(219), // Ū
        chr(197).chr(189) => chr(222), // Ž

    );
    $string = strtr($string, $lt_transform);
    return $string;
}

// Atvirkstine funkcija:

// Pavercia Windows-1257 koduotes lietuviskus simbolius ju UTF-8 atitikmenimis.
function lt_to_utf8($string)
{
    $lt_transform = Array(
        chr(224) => chr(196).chr(133), // ą
        chr(232) => chr(196).chr(141), // č
        chr(230) => chr(196).chr(153), // ę
        chr(235) => chr(196).chr(151), // ė
        chr(225) => chr(196).chr(175), // į
        chr(240) => chr(197).chr(161), // š
        chr(248) => chr(197).chr(179), // ų
        chr(251) => chr(197).chr(171), // ū
        chr(254) => chr(197).chr(190), // ž
        chr(192) => chr(196).chr(132), // Ą
        chr(200) => chr(196).chr(140), // Č
        chr(198) => chr(196).chr(152), // Ę
        chr(203) => chr(196).chr(150), // Ė
        chr(193) => chr(196).chr(174), // Į
        chr(208) => chr(197).chr(160), // Š
        chr(216) => chr(197).chr(178), // Ų
        chr(219) => chr(197).chr(170), // Ū
        chr(222) => chr(197).chr(189), // Ž

    );
    $string = strtr($string, $lt_transform);
    return $string;
}

function utf8_bom()
{
    return chr(0xEF).chr(0xBB).chr(0xBF);
}

function shorten_whitespace($text)
{
    return preg_replace('{[ \r\n\t]+}', ' ', $text); 
}

function array_filter_keys($array, $keys)
{
    if (is_array($array))
        foreach ($array as $k => $v)
            if (! in_array($k, $keys))
                unset($array[$k]);
    
    return $array;
}

// Tests if two arrays are identical.
// Arrays are identical if all their values are equal and type equal (===) (recursively).
// Also, the arrays must contain the same values (all values present in $a1
// must also be present in $a2 and vice-versa).
// NOTE: that, however the order of array elements is not checked for (it can be different,
// and the arays will still be considered identical).
function arrays_identical($a1, $a2, $nonStrictEquality = FALSE)
{
    foreach ($a1 as $k => $v)
    {
        if (!array_key_exists($k, $a2))
            return FALSE;
        if (is_array($a1[$k]) && ! is_array($a2[$k]))
            return FALSE;
        if (! is_array($a1[$k]) && is_array($a2[$k]))
            return FALSE;
        if (is_array($a1[$k]) && is_array($a2[$k]))
        {
            if (arrays_identical($a1[$k], $a2[$k], $nonStrictEquality) == FALSE)
                return FALSE;
        }
        else
        {
            if ($nonStrictEquality)
            {
                if ($a1[$k] != $a2[$k])
                    return FALSE;
            }
            else
            {
                if ($a1[$k] !== $a2[$k])
                    return FALSE;
            }
        }
        
        unset($a2[$k]);
    }
    
    if (count($a2) > 0)
        return FALSE;
    
    return TRUE;
}

function arrays_diff($a1, $a2, $nonStrictEquality = FALSE)
{
    $diffA = Array();
    $diffB = Array();
    
    foreach ($a1 as $k => $v)
    {
        if (!array_key_exists($k, $a2))
            $diffA[$k] = $a1[$k];
        if (is_array($a1[$k]) && ! is_array($a2[$k]) OR
          ! is_array($a1[$k]) &&   is_array($a2[$k]))
        {
            $diffA[$k] = $a1[$k];
            $diffB[$k] = $a2[$k];
        }
        if (is_array($a1[$k]) && is_array($a2[$k]))
        {
            $innerDiff = arrays_diff($a1[$k], $a2[$k], $nonStrictEquality);
            if ($innerDiff["A"])
                $diffA[$k] = $innerDiff["A"];
            if ($innerDiff["B"])
                $diffB[$k] = $innerDiff["B"];
        }
        else
        {
            if ($nonStrictEquality)
            {
                if ($a1[$k] != $a2[$k])
                {
                    $diffA[$k] = $a1[$k];
                    $diffB[$k] = $a2[$k];
                }
            }
            else
            {
                if ($a1[$k] !== $a2[$k])
                {
                    $diffA[$k] = $a1[$k];
                    $diffB[$k] = $a2[$k];
                }
            }
        }
        
        unset($a2[$k]);
    }
    
    foreach ($a2 as $k => $v)
        $diffB[$k] = $a2[$k];
    
    return Array(
        "A" => $diffA,
        "B" => $diffB,
    );
}

/*
 * A jQuery style $.extend for PHP. Currently only supports one extending array.
 * This differs from the jQuery implementation, in the way that this does a deep
 * extend by default, whereas jQuery does a shallow extend by default.
 * $deep defaults to TRUE, because there really is no point in using this function
 * for a shallow extend, we can just use array_merge() for that.
 */
function extend($deep = TRUE, $target, $array1 = FALSE)
{
    if (is_array($deep)) {
        $array1 = $target;
        $target = $deep;
        $deep = TRUE;
    }
    
    foreach ($array1 as $k => $v) {
        if (is_array($v) && $deep == TRUE) {
            if (isset($target[$k]))
                $target[$k] = extend($deep, $target[$k], $v);
            else
                $target[$k] = $v;
        } else {
            $target[$k] = $v;
        }
    }
    
    return $target;
}


function timer_start()
{
    global $____timer_start;
    $____timer_start = microtime(); $____timer_start = explode(" ", $____timer_start); $____timer_start = $____timer_start[1] + $____timer_start[0];
}

function timer_stop()
{
    global $____timer_start;
    $time = microtime(); $time = explode(" ", $time); $time = $time[1] + $time[0]; $finish = $time; $totaltime = ($finish - $____timer_start);
    printf ("This page took %f seconds to load.", $totaltime);
}

// A more concise version of trace(). Prints out just the important information.
function trace($return = FALSE, $noArgs = FALSE)
{
    $debug_backtrace = debug_backtrace();
    
    foreach ($debug_backtrace as $k => $v)
    {
        $debug_backtrace[$k] = Array();
        
        if ($noArgs == FALSE)
            $debug_backtrace[$k]['args'] = $v['args'];
        
        if (isset($v['file']))
            $debug_backtrace[$k]['file'] = $v['file'];
        if (isset($v['line']))
            $debug_backtrace[$k]['line'] = $v['line'];
        if (isset($v['class']))
            $debug_backtrace[$k]['class'] = $v['class'];
        if (isset($v['type']))
            $debug_backtrace[$k]['type'] = $v['type'];
        
        $debug_backtrace[$k]['function'] = $v['function'];
    }
    
    return e($debug_backtrace, $return);
}

// Function which creates a directory (if it does not yet exist), creating
// subdirectories along the way (if necessary).
// Assigns the passed permissions to newly created directories.
//
// NOTE: does not guarantee that the created directory will have the passed permissions,
// because that directory may already exist, but with different permissions.
// If you need to create a writable directory, you should afterwards do a manual
// check with is_writable()
function create_dir($directory, $permissions = 0777)
{
    // if the path has a slash at the end we remove it here
    if(substr($directory,-1) == '/')
    {
        $directory = substr($directory,0,-1);
    }
    
    // if there is no such directory yet - continue
    if(! is_dir($directory))
    {
        // First - recursively create the parent directory
        $directory_arr = explode("/", $directory);
        array_pop($directory_arr);
        $parent = join("/", $directory_arr);
        
        if ($parent)
            create_dir($parent, $permissions);
        
        // Create our directory now
        return @mkdir($directory, $permissions);
    }
    
    return TRUE; // Directory already exists
}

function create_file($file, $permissions = 0777)
{
    $dirname = @dirname($file);
    if (@create_dir($dirname, $permissions))
    {
        if (@touch($file))
        {
            @chmod($file, $permissions);
            return TRUE;
        }
    }
    
    return FALSE;
}

function stream_file($data, $filename, $options = Array())
{
    $filename = str_replace(array("\n","'"),"", $filename);
    $attach = (isset($options["attachment"]) && $options["attachment"]) ? "attachment" : "inline";
    $mime = isset($options["mime"]) ? $options["mime"] : "text/plain";;
    
    header("Cache-Control: private");
    header("Content-type: $mime");
    header("Content-Disposition: $attach; filename=\"$filename\"");
    
    //header("Content-length: " . $size);

    echo $data;
    exit;
}

// ------------ lixlpixel recursive PHP functions -------------
// recursive_remove_directory( directory to delete, empty )
// expects path to directory and optional TRUE / FALSE to empty
// of course PHP has to have the rights to delete the directory
// you specify and all files and folders inside the directory
// ------------------------------------------------------------

// to use this function to totally remove a directory, write:
// recursive_remove_directory('path/to/directory/to/delete');

// to use this function to empty a directory, write:
// recursive_remove_directory('path/to/full_directory',TRUE);
function recursive_remove_directory($directory, $empty=FALSE)
{
    // if the path has a slash at the end we remove it here
    if(substr($directory,-1) == '/')
    {
        $directory = substr($directory,0,-1);
    }
 
    // if the path is not valid or is not a directory ...
    if(!file_exists($directory) || !is_dir($directory))
    {
        // ... we return false and exit the function
        return FALSE;
 
    // ... if the path is not readable
    }elseif(!is_readable($directory))
    {
        // ... we return false and exit the function
        return FALSE;
      // ... else if the path is readable
    }else{
 
        // we open the directory
        $handle = opendir($directory);
 
        // and scan through the items inside
        while (FALSE !== ($item = readdir($handle)))
        {
            // if the filepointer is not the current directory
            // or the parent directory
            if($item != '.' && $item != '..')
            {
                // we build the new path to delete
                $path = $directory.'/'.$item;
 
                // if the new path is a directory
                if(is_dir($path)) 
                {
                    // we call this function with the new path
                    recursive_remove_directory($path);
 
                // if the new path is a file
                }else{
                    // we remove the file
                    @unlink($path);
                }
            }
        }
        // close the directory
        closedir($handle);
 
        // if the option to empty is not set to true
        if($empty == FALSE)
        {
            // try to delete the now empty directory
            if(!rmdir($directory))
            {
                // return false if not possible
                return FALSE;
            }
        }
        // return success
        return TRUE;
    }
}

function moneyWords($suma) {
	if ($suma < 0) { return "Neigiama suma!"; }
	if ($suma == 0) { return "Suma lygi nuliui!"; }
	if ($suma > 9999.99) { return "Įveskite sumą."; }
	$ct = round(($suma - floor($suma))*100);
	if ($ct < 10) $ct = "0" . $ct;
	if ($suma >= 1000) { $t = floor($suma/1000) ; $suma -= $t*1000 ; }
	if ($suma >= 100) { $s =  floor($suma/100) ; $suma -= $s*100 ; }
	if ($suma >= 10) { $d =  floor($suma/10) ; $suma -= $d*10 ; }
	if ($suma >=1) { $v = floor($suma) ; }
	$zodziu = "";
	if (@$t) {
			switch ($t) {
					case 1 : $zodziu = "vienas tūkstantis "; break;
					case 2 : $zodziu = "du tūkstančiai "; break;
					case 3 : $zodziu = "trys tūkstančiai "; break;
					case 4 : $zodziu = "keturi tūkstančiai "; break;
					case 5 : $zodziu = "penki tūkstančiai "; break;
					case 6 : $zodziu = "šeši tūkstančiai "; break;
					case 7 : $zodziu = "septyni tūkstančiai "; break;
					case 8 : $zodziu = "aštuoni tūkstančiai "; break;
					case 9 : $zodziu = "devyni tūkstančiai "; break;
					}
			}
	if (@$s) {
			switch ($s) {
					case 1 : $zodziu .= "vienas šimtas "; break;
					case 2 : $zodziu .= "du šimtai "; break;
					case 3 : $zodziu .= "trys šimtai "; break;
					case 4 : $zodziu .= "keturi šimtai "; break;
					case 5 : $zodziu .= "penki šimtai "; break;
					case 6 : $zodziu .= "šeši šimtai "; break;
					case 7 : $zodziu .= "septyni šimtai "; break;
					case 8 : $zodziu .= "aštuoni šimtai "; break;
					case 9 : $zodziu .= "devyni šimtai "; break;
					}
			}
	$a = 0;
	if (@$d) {
			switch ($d) {
					case 1 : $a = 1; break;
					case 2 : $zodziu .= "dvidešimt "; break;
					case 3 : $zodziu .= "trisdešimt "; break;
					case 4 : $zodziu .= "keturiasdešimt "; break;
					case 5 : $zodziu .= "penkiasdešimt "; break;
					case 6 : $zodziu .= "šešiasdešimt "; break;
					case 7 : $zodziu .= "septyniasdešimt "; break;
					case 8 : $zodziu .= "aštuoniasdešimt "; break;
					case 9 : $zodziu .= "devyniasdešimt "; break;
					}
			}
	if (!@$v) { if ($a == 1) { $zodziu .= "dešimt " ; } }
	if (@$v) { if ($a == 1) {
			switch ($v) {
					case 1 : $zodziu .= "vienuolika "; break;
					case 2 : $zodziu .= "dvylika "; break;
					case 3 : $zodziu .= "trylika "; break;
					case 4 : $zodziu .= "keturiolika "; break;
					case 5 : $zodziu .= "penkiolika "; break;
					case 6 : $zodziu .= "šešiolika "; break;
					case 7 : $zodziu .= "septyniolika "; break;
					case 8 : $zodziu .= "aštuoniolika "; break;
					case 9 : $zodziu .= "devyniolika "; break;
					}
			}
			else {
			switch ($v) {
					case 1 : $zodziu .= "vienas "; break;
					case 2 : $zodziu .= "du "; break;
					case 3 : $zodziu .= "trys "; break;
					case 4 : $zodziu .= "keturi "; break;
					case 5 : $zodziu .= "penki "; break;
					case 6 : $zodziu .= "šeši "; break;
					case 7 : $zodziu .= "septyni "; break;
					case 8 : $zodziu .= "aštuoni "; break;
					case 9 : $zodziu .= "devyni "; break;
					}
			}
	}
	if ($zodziu == "") $zodziu = "0 ";
	return $zodziu . " Lt " . $ct . " ct";
}

function timediff($from, $to)
{
    if (! is_numeric($from))
        $from = strtotime($from);
    if (! is_numeric($to))
        $to = strtotime($to);
    
    $difference = abs($to - $from);
    
    $string = sprintf("%02ds", $difference % 60);
    
    $difference = floor($difference / 60);
    if ($difference > 0)
    {
        $string = sprintf("%02dm", $difference % 60) . " " . $string;
        
        $difference = floor($difference / 60);
        if ($difference > 0)
        {
            $string = sprintf("%02h", $difference % 24) . " " . $string;
            
            $difference = floor($difference / 24);
            if ($difference > 0)
            {
                $string = sprintf("%d", $difference % 365) . " " . $string;
                
                $difference = floor($difference / 365.25);
                if ($difference > 0)
                {
                    $string = sprintf("%dyr", $difference) . " " . $string;
                }
            }
        }
    }

    return $string;
}

// Returns a human readable reresentation of the given memory amount (in bytes)
function mem($bytes)
{
    $amount = $bytes;
    $string = sprintf("%db", $amount % 1024);
    
    $amount = floor($amount / 1024);
    if ($amount > 0)
    {
        $string = sprintf("%dkb", $amount % 1024) . " " . $string;
        
        $amount = floor($amount / 1024);
        if ($amount > 0)
        {
            $string = sprintf("%dMB", $amount % 1024) . " " . $string;
            
            $amount = floor($amount / 1024);
            if ($amount > 0)
            {
                $string = sprintf("%dGB", $amount % 1024) . " " . $string;
                
                $amount = floor($amount / 1024);
                if ($amount > 0)
                {
                    $string = sprintf("%dTB", $amount % 1024) . " " . $string;
                }
            }
        }
    }

    return $string;
}




