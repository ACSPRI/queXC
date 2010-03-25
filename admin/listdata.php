<?
/**
 * List the data for a data file
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Inc (ACSPRI) 2010
 * @package queXC
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXC was writen for ACSPRI
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
include("../db.inc.php");


/**
 * Work functions
 */
include("../functions/functions.work.php");


xhtml_head(T_("Display data"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a data file to display
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

if ($data_id != 0)
{
	$column_id = 0;
	if (isset($_GET['column_id'])) $column_id = intval($_GET['column_id']);

	//Select variable (column)
	$sql = "SELECT column_id as value, name as description, CASE WHEN column_id = '$column_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column`
		WHERE data_id = '$data_id'";

	print "<div>" . T_("Select variable: ");
	$c = $db->GetAll($sql);

	display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
	print "</div>";

	$sql = "SELECT column_id,name,code_level_id
		FROM `column` 
		WHERE data_id = '$data_id'";

	if ($column_id != 0)
	{
		$sql .= " AND column_id = '$column_id'";

	
	$cols = $db->GetAll($sql);

	$sql = "SELECT c.row_id
		FROM cell as c
		WHERE c.column_id = {$cols[0]['column_id']}
		GROUP BY c.row_id";

	$rows = $db->GetAll($sql);

	print "<table class='tclass'>";

	print "<tr>";
	foreach ($cols as $c)
	{
		print "<th>" . $c['name'] . "</th>";
	}
	print "</tr>";

	foreach ($rows as $r)
	{
		print "<tr>";
		foreach ($cols as $c)
		{
			list($data,$revision) = get_cell_data(get_cell_id($r['row_id'],$c['column_id']));
			//convert to code if one exists?
			print "<td>" . $data . "</td>";
		}
		print "</tr>";
	}
	print "</table>";
	}
}


xhtml_foot();
?>
