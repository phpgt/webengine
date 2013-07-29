<?php final class FileOrganiser {
/**
 * This class works closely with ClientSideCompiler to ensure that all source
 * files are stored ouside of the webroot (www directory), but the compiled or
 * minified versions are copied correctly when required.
 *
 * The order of execution is controlled by the Dispatcher.
 *
 * 1) go functions are executed. This will trigger any PageTools' clientSide()
 * function, which adds <script> and <link> elements into the DOM head.
 * 2) FileOrganiser checks if files are needed to be written to www directory.
 * 3) FileOrganiser writes files to www directory.
 * 4) ClientSideCompiler pre-processes any SCSS source files.
 * 5) If client is compiled, FileOrganiser triggers the last step on the
 * ClientSideCompiler, minifying and compiling all files together and removing
 * the originals.
 *
 * All of this is only done if there are modifications to the source files since
 * the modified time within www directory.
 */

private $_wwwDir;
private $_cacheFile;

public function __construct() {
	$this->_wwwDir = APPROOT . "/www";
	$this->_cacheFile = "{$this->_wwwDir}/www.cache";
}

/**
 * In production, cache is *always* valid when the www.cache file is present.
 * To invalidate, just remove the file.
 *
 * In non-production, simply obtains the latest modified file time and compares
 * it to the www.cache's time.
 *
 * @return bool True if the www directory needs refreshing.
 */
public function checkFiles() {
	$cacheFileExists = file_exists($this->_cacheFile);
	if(!$cacheFileExists) {
		return true;
	}
	if(App_Config::isProduction()) {
		return !$cacheFileExists;
	}

	$sourceDirectoryArray = array("Asset", "Script", "Style");
	$fileMTime = 0;

	// First build up the array of files in the source directories.
	foreach ($sourceDirectoryArray as $sourceDirectory) {
		// GTROOT comes first in the array, so that they will be overrided by
		// any files that have the same name in the APPROOT.
		$directoryPathArray = array(
			GTROOT . "/$sourceDirectory",
			APPROOT . "/$sourceDirectory",
		);

		foreach($directoryPathArray as $directoryPath) {
			if(!is_dir($directoryPath)) {
				continue;
			}

			foreach ($iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directoryPath,
					RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST) as $item) {

				$pathName = $iterator->getPathName();
				$subPathName = $iterator->getSubPathName();

				// Don't check on hidden files or directories.
				if(strpos($item->getFileName(), ".") === 0
				|| $item->isDir()) {
					continue;
				}

				$thisFileMTime = filemtime($pathName);
				if($thisFileMTime > $fileMTime) {
					$fileMTime = $thisFileMTime;
				}
			}
		}
	}


	$cacheMTime = filemtime($this->_cacheFile);
	// Returns if the cache is out of date.
	return $cacheMTime < $fileMTime;
}

/**
 * Removes all files within any subdirectories in the www directory. Doesn't 
 * remove any files within the root www directory.
 */
public function clean() {
	$directoryArray = array("Asset", "Script", "Style");
	foreach($directoryArray as $directory) {
		$directoryPath = "{$this->_wwwDir}/$directory";

		if(!is_dir($directoryPath)) {
			continue;
		}

		foreach ($iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directoryPath,
				RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST) as $item) {

			$pathName = $iterator->getPathName();
			unset($pathName);
		}

		rmdir($directoryPath);
	}

	if(file_exists($this->_cacheFile)) {
		unset($this->_cacheFile);
	}
}

/**
 * Performs the actual copying of resources within the three source directories.
 * Because PageTools can inject client-side resources in the DOM head, a list
 * of matching elements could be passed in to be included in the file copying.
 */
public function update($pageToolElements = array()) {
	$time = time();

	$directoryArray = array("Asset", "Script", "Style");
	foreach($directoryArray as $directory) {
		$sourceDirectoryArray = array(
			GTROOT . "/$directory",
			APPROOT . "/$directory",
		);

		foreach ($sourceDirectoryArray as $sourceDirectory) {
			if(!is_dir($sourceDirectory)) {
				continue;
			}
			
			foreach ($iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($sourceDirectory,
					RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST) as $item) {

				$pathName = $iterator->getPathName();
				$subPathName = $iterator->getSubPathName();
				$fileName = $item->getFileName();
				if($fileName[0] === ".") {
					continue;
				}

				$destinationFile = "{$this->_wwwDir}/$directory/$subPathName";
				$destinationDir = dirname($destinationFile);

				if(!is_dir($destinationDir)) {
					mkdir($destinationDir, 0775, true);
				}

				if($item->isDir()) {
					continue;
				}

				copy($pathName, $destinationFile);
			}
		}
	}

	// TODO: PageTool stuff.

	file_put_contents($this->_cacheFile, $time);
	return $time;
}

/**
 * Some files will require preprocessing, such as SCSS source files. The source
 * files will be present in the www directory structure, so this function will
 * REPLACE the source files.
 */
public function process($clientSideCompiler) {
	$count = 0;

	foreach ($iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator("$this->_wwwDir/Style",
			RecursiveDirectoryIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST) as $item) {

		$pathName = $iterator->getPathName();
		$fileName = $item->getFileName();
		$extension = strtolower($item->getExtension());
		
		if($extension !== "scss") {
			continue;
		}
		
		if($clientSideCompiler->process($pathName)) {
			$count++;			
		}

	}
	return $count;
}

/**
 * If client-side compilation is turned on in the App_Config file, this function
 * compiles all client-side resources into a single resource and DELETES the
 * original files from the www directory. Note that if the original files do not
 * change, and the dom head stays the same, the check() function will not allow
 * this CPU-intensive function to be fired.
 */
public function compile($clientSideCompiler, $domHead) {
	$clientSideCompiler->combine($domHead);
	return;
}

}#