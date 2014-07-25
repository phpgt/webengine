<?php
/**
 * PHP.Gt (http://php.gt)
 * @copyright Copyright Ⓒ 2014 Bright Flair Ltd. (http://brightflair.com)
 * @license Apache Version 2.0, January 2004. http://www.apache.org/licenses
 */
namespace Gt\Request;
use \Gt\Core\Obj;

class Standardiser_Test extends \PHPUnit_Framework_TestCase {

public function setUp() {}

public function tearDown() {}

private $uriList = [
	"index",
	"about-me",
	"shop",
	"shop/pie/apple",
];

private $indexNameList = [
	"index",
	"start",
	"home",
];

public function data_uriList() {
	$return = array(["/"]);

	foreach ($this->uriList as $uri) {
		$return []= ["/$uri"];
		$return []= ["/$uri/"];
		$return []= ["/$uri.html"];
		$return []= ["/$uri.html/"];
		$return []= ["/$uri.json"];
		$return []= ["/$uri.json/"];
		$return []= ["/$uri.jpg"];
		$return []= ["/$uri.jpg/"];
	}

	return $return;
}

public function data_uriList_withIndexName() {
	$return = $this->data_uriList();

	foreach ($return as $i => $param) {
		foreach($this->indexNameList as $indexName) {
			$return[$i] []= $indexName;			
		}
	}

	return $return;
}

private function pathinfo($uri, &$file, &$ext) {
	$pathinfo = pathinfo($uri);
	$file = strtok($pathinfo["filename"], "?");
	$ext  = empty($pathinfo["extension"])
		? null
		: strtok($pathinfo["extension"], "?");
}

/**
 * @dataProvider data_uriList
 */
public function testFixHtmlExtension($uri) {
	$this->pathinfo($uri, $file, $ext);
	$standardiser = new Standardiser();

	$this->assertEquals($uri, $standardiser->fixHtmlExtension(
		$uri, $file, $ext, new Obj()) );

	$config = new Obj();
	$config->pageview_html_extension = false;

	$fixed = $standardiser->fixHtmlExtension($uri, $file, $ext, $config);
	$this->assertNotRegexp("/\.html.?$/", $fixed);

	$config = new Obj();
	$config->pageview_html_extension = true;

	$fixed = $standardiser->fixHtmlExtension($uri, $file, $ext, $config);
	if(empty($ext)) {
		if($uri === "/") {
			$this->assertEquals($fixed, $uri);
		}
		else {
			$this->assertRegexp("/\.html.?$/", $fixed);			
		}
	}
	else {
		if($ext === "html") {
			$this->assertRegexp("/\.html.?$/", $fixed);			
		}
		else {
			$this->assertNotRegexp("/\.html.?$/", $fixed);			
		}
	}
}

/**
 * @dataProvider data_uriList_withIndexName
 */
public function testFixIndexFilenameForce($uri, $index) {
	$this->pathinfo($uri, $file, $ext);

	$config = new Obj();
	$config->index_force = true;
	$config->index_filename = $index;

	$standardiser = new Standardiser();
	$fixed = $standardiser->fixIndexFilename($uri, $file, $ext, $config);

	if(empty($file)) {
		$expected = "$uri$index";
		$this->assertEquals($expected, $fixed);
	}
}

/**
 * @dataProvider data_uriList_withIndexName
 */
public function testFixIndexFilenameNoForce($uri, $index) {
	$this->pathinfo($uri, $file, $ext);
	$standardiser = new Standardiser();
	$this->assertEquals($uri, 
		$standardiser->fixIndexFilename($uri, $file, $ext, new Obj()) );

	$config = new Obj();
	$config->index_force = false;
	$config->index_filename = $index;

	$fixed = $standardiser->fixIndexFilename($uri, $file, $ext, $config);

	if($file === $index 
	&&(empty($ext) || $ext === "html") ) {
		$expected = substr($uri, 0, strrpos($uri, $index));
		$this->assertEquals($expected, $fixed, "The ext is $ext");
	}
}

/**
 * @dataProvider data_uriList
 */
public function testFixTrailingSlash($uri) {
	$this->pathinfo($uri, $file, $ext);
	$standardiser = new Standardiser();
	$this->assertEquals($uri,
		$standardiser->fixTrailingSlash($uri, $file, $ext, new Obj()));

	$config = new Obj();
	$config->pageview_trailing_directory_slash = true;

	$fixed = $standardiser->fixTrailingSlash($uri, $file, $ext, $config);

	$lastChar = substr($uri, -1);
	if(empty($ext)) {
		if($lastChar === "/") {
			$this->assertEquals($uri, $fixed);
		}
		else {
			$this->assertEquals($uri . "/", $fixed);
		}
	}
}

/**
 * @dataProvider data_uriList
 */
public function testFixNoTrailingSlash($uri) {
	$this->pathinfo($uri, $file, $ext);
	$standardiser = new Standardiser();
	$this->assertEquals($uri,
		$standardiser->fixTrailingSlash($uri, $file, $ext, new Obj()));

	$config = new Obj();
	$config->pageview_trailing_directory_slash = false;

	$fixed = $standardiser->fixTrailingSlash($uri, $file, $ext, $config);

	$lastChar = substr($uri, -1);
	if(empty($ext)) {
		if($lastChar === "/") {
			$this->assertEquals(substr($uri, 0, -1), $fixed);
		}
		else {
			$this->assertEquals($uri, $fixed);
		}
	}
}

}#