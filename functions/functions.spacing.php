<?php 
/**
 * Spacing functions
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');


/**
 * Normalise white space
 *
 * @param string $str The original string
 * @return string The string with white space normalised
 */
function normalise_space($str)
{
	return preg_replace('/\s+/xms', ' ', trim($str));
}

/**
 * Replace common symbols with words
 *
 * @param string $str The original string
 * @return string The string with common symbols replaced
 */
function replace_symbols($str)
{
	return preg_replace("/&/"," and ",$str);
}

/**
 * Remove all non alpha symbols from string
 *
 * @param string $str The original string
 * @return string The string with only alpha symbols
 */
function remove_nonalpha($str)
{
	return preg_replace("/[^A-Za-z ]/", "", $str);
}

/**
 * Remove all non alphanumeric symbols from string
 *
 * @param string $str The original string
 * @return string The string with only alphanumeric symbols
 */
function remove_nonalphanumeric($str)
{
	return preg_replace("/[^A-Za-z0-9 ]/", "", $str);
}

?>
