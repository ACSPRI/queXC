<?
/**
 * Display a list of work for all operators including work not done
 * and allow for the deletion of not complete work_units
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
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * DB functions
 */
include ("../db.inc.php");

if (isset($_GET['del']))
{
	$work_unit_id = intval($_GET['del']);

	$sql = "DELETE FROM work_unit
		WHERE work_unit_id = $work_unit_id
		AND completed IS NULL";
	
	$db->Execute($sql);
}


xhtml_head(T_("Work List"), true, array("../css/table.css"));

$sql = "SELECT wu.work_unit_id, wu.completed,c.name, CASE WHEN wu.completed IS NULL THEN CONCAT('<a href=\'?del=',wu.work_unit_id,'\'>" . T_("Delete") . "</a>') ELSE '' END as del,
		(SELECT CASE WHEN CHAR_LENGTH(TRIM(data)) < " . WORK_HISTORY_STRING_LENGTH . " THEN data ELSE CONCAT(SUBSTR(data,1,(" . (WORK_HISTORY_STRING_LENGTH - 3) . ")),'...') END FROM cell_revision WHERE cell_id = wu.cell_id ORDER BY cell_revision_id DESC LIMIT 1) as data, p.description, o.description as op
	FROM work_unit as wu
	JOIN work AS w ON (w.work_id = wu.work_id)
	JOIN `column` AS c ON (c.column_id = w.column_id)
	JOIN process AS p ON (p.process_id = wu.process_id)
	LEFT JOIN operator as o ON (o.operator_id = wu.operator_id)
	ORDER BY wu.work_unit_id DESC
	LIMIT " . WORK_HISTORY_LIMIT;
	
$work = $db->GetAll($sql);

if (empty($work))
	print "<p>" . T_("No work history") ."</p>";
else
{
	translate_array($work,array("description"));
	xhtml_table($work,array('work_unit_id','completed','name','data','description','op','del'),array(T_("Work Unit ID"),T_("Date Completed"),T_("Column"),T_("Data"),T_("Process"),T_("Operator"),T_("Delete?")));
}

xhtml_foot();


?>
