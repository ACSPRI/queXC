<?
/**
 * Configuration file
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
 * Only some of the configuration directives are here. See the file: config.default.php for them all
 *
 * Make your configuration changes in this file only - they will "overwrite" the default configuration
 *
 */

/**
 * Database configuration for queXC
 */
define('DB_USER', 'quexc');
define('DB_PASS', 'quexc');
define('DB_HOST', 'databasedev.dcarf');
define('DB_NAME', 'quexc');


//Do not modify the following line:
include(dirname(__FILE__).'/config.default.php');
?>
