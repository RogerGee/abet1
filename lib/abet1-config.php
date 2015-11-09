<?php

/* abet1-config.php - configuration functionality

    This library loads configuration key-value pairs from the application
    config file. The config file should be named 'abet1.config' and should
    be located in the /etc/abet1 directory. All config keys (regardless of how
    they are typed in the file) are uppercase once loaded into the program. Do
    not put this file in the Web root: it is a library.
*/

// AbetConfig - creates objects that act like arrays containing the
// configuration key-value pairs; throws an Exception if the config file
// couldn't be opened
class AbetConfig extends ArrayObject {

    function __construct() {
        $file = '/etc/abet1/abet1.config';
        $f = fopen($file,'r');
        if (!$f)
            throw new Exception("couldn't open $file for reading");

        // read key-value pairs from the file and store in array; all keys
        // will employ uppercase names
        $arr = array();
        while (($line = fgets($f)) !== false) {
            $line = trim($line);
            if (strlen($line) > 0) {
                if ($line[0] == '#')
                    continue; // comment line
                $i = strpos($line,"=");
                if ($i !== false) {
                    $k = substr($line,0,$i);
                    $v = substr($line,$i+1);
                    if ($k!=='' || $k!=='')
                        $arr[strtoupper($k)] = $v;
                }
            }
        }

        // create base class with array
        parent::__construct($arr);
        fclose($f);
    }
}
