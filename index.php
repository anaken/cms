<? 
function stripslashes_array(&$array, $iterations=0) {
    if ($iterations < 3) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                stripslashes_array($array[$key], $iterations + 1);
            } else {
                $array[$key] = stripslashes($array[$key]);
            }
        }
    }
}

if (get_magic_quotes_gpc()) {
    stripslashes_array($_GET);
    stripslashes_array($_POST);
    stripslashes_array($_COOKIE);
}

require_once(dirname(__FILE__).'/core/fc.php');
echo FC()->handle();