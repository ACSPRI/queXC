<?php 
/**
 * Default Configuration file
 *
 *
 *      This file is part of queXC
 *      
 *      queXC is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU Affero General Public License as published by
 *      the Free Software Foundation; either version 3 of the License, or
 *      (at your option) any later version.
 *      
 *      queXC is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU Affero General Public License for more details.
 *      
 *      You should have received a copy of the GNU Affero General Public License
 *      along with queXC; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @author Adam Zammit <adam.zammit@deakin.edu.au>
 * @copyright Deakin University 2007,2008,2009
 * @package queXC
 * @subpackage configuration
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */


/**
 *
 * DO NOT MODIFY THIS FILE!
 *
 * Make your configuration changes in config.inc.php
 *
 *
 */


/**
 * The default locale (language)
 */
if (!defined('DEFAULT_LOCALE')) define('DEFAULT_LOCALE','en');

/**
 * Spelling
 */
if (!defined('SPELL_LANGUAGE')) define('SPELL_LANGUAGE','en_GB');
if (!defined('SPELL_SPELLING')) define('SPELL_SPELLING','british');
if (!defined('SPELL_DICTIONARY_FILE')) define('SPELL_DICTIONARY_FILE',dirname(__FILE__).'/dictionary');

/**
 * Maximum number of records to assign to an operator at one time
 */
if (!defined('ASSIGN_MAX_LIMIT')) define('ASSIGN_MAX_LIMIT',100);

if (!defined('WORK_HISTORY_LIMIT')) define('WORK_HISTORY_LIMIT',50);
if (!defined('WORK_HISTORY_STRING_LENGTH')) define('WORK_HISTORY_STRING_LENGTH',80);

/**
 * For calculating performance, ignore times longer than this (seconds)
 */
if (!defined('PERFORMANCE_IGNORE_LONGER_THAN')) define('PERFORMANCE_IGNORE_LONGER_THAN', 360);

/**
 * Path to ADODB
 */
if (!defined('ADODB_PATH')) define('ADODB_PATH',dirname(__FILE__).'/../adodb/');


/**
 * Database configuration for queXC
 */
if (!defined('DB_USER')) define('DB_USER', 'quexc');
if (!defined('DB_PASS')) define('DB_PASS', 'quexc');
if (!defined('DB_HOST')) define('DB_HOST', 'databasedev.dcarf');
if (!defined('DB_NAME')) define('DB_NAME', 'quexc');
if (!defined('DB_TYPE')) define('DB_TYPE', 'mysqlt');

?>
