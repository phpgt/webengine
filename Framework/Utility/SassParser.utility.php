<?php final class SassParser_Utility {
private $_filePath;
private $_sassParser;

public function __construct($filePath) {
	require_once(GTROOT . "/Framework/Utility/Sass/SassParser.php");
	$filePath = preg_replace("/\/+/", "/", $filePath);
	if(!file_exists($filePath)) {
		return false;
	}

	$this->_filePath = $filePath;
}

public function parse() {
	$this->_sassParser = new SassParser();
	$parsedString = $this->_sassParser->toCss($this->_filePath);
	// Add an automated message to the output file, so developers know not to
	// edit the pre-processed CSS
	$message = <<<MSG
/*******************************************************************************
 * Do not edit this file!
 * This .css file is pre-processed from a sass/scss file.    
 * To make changes, edit {$this->_filePath} instead.
 * Have a nice day :)
 ******************************************************************************/
MSG;
	$parsedString = $message . "\n\n\n\n\n" . $parsedString;
	return $parsedString;
}

}?>