<?
/**
 * Functions for processes
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
 * XHTML functions
 */
include_once(dirname(__FILE__).'/functions.xhtml.php');

/**
 * Display XHTML for spelling
 *
 * @param int $cell_id The cell to display data for
 * @param string $cell_data The data in the cell
 * @param int $work_unit_id
 */
function spelling_display($cell_id,$cell_data,$work_unit_id)
{
	xhtml_script(array('include/ajax-spell/spell_checker.js'));
	print "<p><textarea class='spell_check' rows='10' cols='50' name='ci$cell_id' id='ci$cell_id'>$cell_data</textarea></p>";
}


/**
 * Display XHTML for email
 *
 * @param int $cell_id The cell to display data for
 * @param string $cell_data The data in the cell
 * @param int $work_unit_id
 */
function email_display($cell_id,$cell_data,$work_unit_id)
{
	print "<p><textarea rows='10' cols='50' name='ci$cell_id' id='ci$cell_id'>$cell_data</textarea></p>";
}


?>
