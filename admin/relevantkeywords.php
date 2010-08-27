<?
/**
 *  Assign keywords to columns
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
	//remove 
	$c = intval($_GET['column_code_keyword_id']);

	$sql = "DELETE FROM column_code_keyword
		WHERE column_code_keyword_id = '$c'";

	$db->Execute($sql);

}


if (isset($_POST['submit']))
{
	//add
	$column_group_id = intval($_POST['column_group_id']);
	$column_id = intval($_POST['column_id']);
	$ckg = intval($_POST['code_keyword_group_id']);
	
	$sql = "INSERT INTO column_code_keyword (column_code_keyword_id,column_id,column_group_id,code_keyword_group_id)
		VALUES (NULL,'$column_id','$column_group_id','$ckg')";
	
	$db->Execute($sql);
}


xhtml_head(T_("Select relevant keywords for columns"),true,array("../css/table.css"),array("../js/display.js"));

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
		FROM `column` as c
		WHERE c.data_id = '$data_id'
		GROUP BY c.column_id";
	
	print "<div>" . T_("Select column to auto code from:");
	$c = $db->GetAll($sql);
	display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
	print "</div>";
	

	if ($column_id != 0)
	{

		$column_group_id = 0;
		if (isset($_GET['column_group_id'])) $column_group_id = intval($_GET['column_group_id']);
	
		//List column_groups assigned to this data file 
		$sql = "SELECT cg.column_group_id as value, cg.description, CASE WHEN cg.column_group_id = '$column_group_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `column_group` as cg, work as w, `column` as c, `column` as c2
			WHERE c.column_id = '$column_id'
			AND c2.data_id = c.data_id
			AND c2.column_id = w.column_id
			AND w.column_group_id = cg.column_group_id
			GROUP BY cg.column_group_id";
	
		print "<div>" . T_("Select column group to code to (work must already have been created): ");
		$c = $db->GetAll($sql);
		translate_array($c,array("description"));
		display_chooser($c,'column_group_id','column_group_id',true,"data_id=$data_id&amp;column_id=$column_id");
		print "</div>";
	
		if ($column_group_id != 0)
		{
			//Display columns selected for this data file and process
			$sql = "SELECT ckg.description as pdes, c.description as cdes, CONCAT('<a href=\'?data_id=$data_id&amp;remove=remove&amp;column_code_keyword_id=',cck.column_code_keyword_id,'\'>" . T_("Remove") . "</a>') as link
				FROM code_keyword_group as ckg, `column` as c, column_code_keyword as cck
				WHERE cck.column_group_id = '$column_group_id'
				AND cck.column_id = c.column_id
				AND ckg.code_keyword_group_id = cck.code_keyword_group_id";

			$rs = $db->GetAll($sql);
	
			xhtml_table($rs,array('pdes','cdes','link'),array(T_("Keyword group"),T_("Column"),T_("Remove")));
	
			print "<div>" . T_("Select keyword group to add") . "</div>"; 
				
			print "<form action='' method='post'><div><input type='hidden' name='data_id' value='$data_id'/><input type='hidden' name='column_group_id' value='$column_group_id'/><input type='hidden' name='column_id' value='$column_id'/></div>";
				
			//display a dropdown of all code keyword groups relevant
	
			$sql = "SELECT ckg.code_keyword_group_id as value,ckg.description, '' AS selected
				FROM `code_keyword_group` as ckg, column_group as cg
				WHERE ckg.code_group_id = cg.code_group_id
				AND cg.column_group_id = '$column_group_id'";
			
			$rs = $db->GetAll($sql);

			if (!empty($rs))
			{
				display_chooser($rs,'code_keyword_group_id','code_keyword_group_id',true,false,false);
				print "<div><input type='submit' name='submit' value='" . T_("Add keyword group") . "'/></div></form>";
			}
			else
				print "<div>" . T_("No code keyword group available") . "</div></form>";
	
	
		}
	}
}

xhtml_foot();
?>
