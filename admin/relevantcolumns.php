<?
/**
 *  Select relevant columns for each data file and process
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
include("../db.inc.php");

if (isset($_GET['remove']))
{
	//remove from cpc
	$process_id = intval($_GET['process_id']);
	$column_id = intval($_GET['column_id']);
	$relevant_column_id = intval($_GET['relevant_column_id']);

	$sql = "DELETE FROM column_process_column
		AND process_id = '$process_id'
		AND column_id = '$column_id'
		AND relevant_column_id = '$relevant_column_id'";
	
	$db->Execute($sql);

}


if (isset($_POST['submit']))
{
	//add to cpc
	$process_id = intval($_POST['process_id']);
	$column_id = intval($_POST['column_id']);
	$relevant_column_id = intval($_POST['relevant_column_id']);
	
	$sql = "INSERT INTO column_process_column (process_id,column_id,relevant_column_id)
		VALUES ('$process_id','$column_id','$relevant_column_id')";
	
	$db->Execute($sql);
}


xhtml_head(T_("Select relevant columns"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a data file
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

if ($data_id != 0)
{
	$column_id = 0;
	if (isset($_GET['column_id'])) $column_id = intval($_GET['column_id']);

	//Get columns in this data file
	$sql = "SELECT c.column_id as value,c.description, CASE WHEN c.column_id = '$column_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column` as c, work as w
		WHERE c.data_id = '$data_id'
		AND c.column_id = w.column_id
		GROUP BY c.column_id";
	
	print "<div>" . T_("Select column: ");
	$c = $db->GetAll($sql);
	display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
	print "</div>";
	

	if ($column_id != 0)
	{

		$process_id = 0;
		if (isset($_GET['process_id'])) $process_id = intval($_GET['process_id']);
	
		//List processes assigned to this data file and column
		$sql = "SELECT p.process_id as value, p.description, CASE WHEN p.process_id = '$process_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `process` as p, work as w, `column` as c
			WHERE w.column_id = '$column_id'
			AND w.process_id = p.process_id
			GROUP BY p.process_id";
	
		print "<div>" . T_("Select process: ");
		$c = $db->GetAll($sql);
		translate_array($c,array("description"));
		display_chooser($c,'process_id','process_id',true,"data_id=$data_id&amp;column_id=$column_id");
		print "</div>";
	
		if ($process_id != 0)
		{
			//Display columns selected for this data file and process
			$sql = "SELECT p.description as pdes, c.description as cdes, CONCAT('<a href=\'?remove=remove&amp;process_id=',cpc.process_id,'&amp;column_id=',cpc.column_id,'&amp;relevant_column_id=',cpc.relevant_column_id,'\'>" . T_("Remove") . "</a>') as link
				FROM process as p, `column` as c, column_process_column as cpc
				WHERE cpc.process_id = '$process_id'
				AND cpc.column_id = '$column_id'
				AND cpc.relevant_column_id = c.column_id
				AND cpc.process_id = p.process_id";

			$rs = $db->GetAll($sql);
		
			xhtml_table($rs,array('pdes','cdes','link'),array(T_("Process"),T_("Related column"),T_("Remove")));
	
			print "<div>" . T_("Select column to add") . "</div>"; 
				
			print "<form action='' method='post'><div><input type='hidden' name='data_id' value='$data_id'/><input type='hidden' name='process_id' value='$process_id'/><input type='hidden' name='column_id' value='$column_id'/></div>";
				
			//display a dropdown of all columns
	
			$sql = "SELECT column_id as value,description, '' AS selected
				FROM `column`
				WHERE data_id = '$data_id'";
	
			display_chooser($db->GetAll($sql),'relevant_column_id','relevant_column_id',true,false,false);
	
			print "<div><input type='submit' name='submit' value='" . T_("Add column") . "'/></div></form>";
	
		}
	}
}

xhtml_foot();
?>
