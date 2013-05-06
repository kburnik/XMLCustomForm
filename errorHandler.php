<?

function errorHandler($level, $message, $file, $line, $context) {
    //Handle user errors, warnings, and notices ourself
    if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) {
        echo '<strong>Error:</strong><br />'.$message;
		//if ($level === E_USER_ERROR) die();
        return(true); //And prevent the PHP error handler from continuing
    }
    return(false); //Otherwise, use PHP's error handler
}

function debug() {
	foreach (func_get_args() as $text) {
		if (is_array($text)) $text = var_export($text,true);
		echo "<pre><strong>$title</strong><br/>".htmlentities($text)."</pre>";
	}
}

set_error_handler("errorHandler");
?>