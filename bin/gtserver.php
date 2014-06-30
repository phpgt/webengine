#!/usr/bin/env php
<?php
/**
 * Shell script interface for launching the built-in webserver.
 *
 * PHP.Gt (http://php.gt)
 * @copyright Copyright Ⓒ 2014 Bright Flair Ltd. (http://brightflair.com)
 * @license Apache Version 2.0, January 2004. http://www.apache.org/licenses
 */
namespace Gt\Cli;
require(__DIR__ . "/../Package/autoload.php");

$options = new Options();
return new Server($options->getArgs());