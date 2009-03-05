<?
/**
 * Display a history of work done, and allow for a new work unit to be specified 
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
 * @subpackage user
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Work functions
 */
include("functions/functions.work.php");

$operator_id = get_operator_id();

if (isset($_GET['redo']))
{
	redo(intval($_GET['redo']));
}

xhtml_head(T_("Work History"), true, array("css/table.css"));

if ($operator_id != false)
{
	$sql = "SELECT wu.work_unit_id,(CASE WHEN DATE(wu.completed) = DATE(NOW()) THEN CONCAT('". T_("Today") ." ',DATE_FORMAT(wu.completed,'%H:%i:%s')) ELSE wu.completed END) as completed,c.name, CONCAT('<a href=\'?redo=',wu.work_unit_id,'\'>" . T_("Redo") . "</a>') as redo,
			(SELECT CASE WHEN CHAR_LENGTH(TRIM(data)) < " . WORK_HISTORY_STRING_LENGTH . " THEN data ELSE CONCAT(SUBSTR(data,1,(" . (WORK_HISTORY_STRING_LENGTH - 3) . ")),'...') END FROM cell_revision WHERE cell_id = wu.cell_id ORDER BY cell_revision_id DESC LIMIT 1) as data, p.description
		FROM work_unit as wu, work as w, `column` as c,process as p
		WHERE wu.operator_id = '$operator_id'
		AND w.work_id = wu.work_id
		AND c.column_id = w.column_id
		AND p.process_id = wu.process_id
		AND completed IS NOT NULL
		ORDER BY completed DESC
		LIMIT " . WORK_HISTORY_LIMIT;
	
	$work = $db->GetAll($sql);
	
	if (empty($work))
		print "<p>" . T_("No work history") ."</p>";
	else
		xhtml_table($work,array('completed','name','data','description','redo'),array(T_("Date"),T_("Column"),T_("Data"),T_("Process"),T_("Redo?")));
}
else
	print "<p>" . T_("No operator") . "</p>";

xhtml_foot();


?>
