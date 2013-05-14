<?php 
/**
 * Export correspondence file of keyword and code
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2010
 * @package queXC
 * @subpackage admin
 * @link http://www.acspri.org.au/software/ queXC was writen for ACSPRI
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
 * Export functions
 */
include("../functions/functions.export.php");

if (isset($_GET['download']) && isset($_GET['data_id']) && isset($_GET['code_column']) && isset($_GET['text_column']))
{
	export_csv(intval($_GET['data_id']),true,false,false,array(intval($_GET['code_column']),intval($_GET['text_column'])),false);
	exit();
}

xhtml_head(T_("Export code keyword correspondence"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a data file to export data from
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

if ($data_id != 0)
{
	$code_column = 0;
	if (isset($_GET['code_column'])) $code_column = intval($_GET['code_column']);

	$sql = "SELECT c.column_id as value, c.description, CASE WHEN column_id = '$code_column' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column` as c
		WHERE c.data_id = '$data_id'
		AND code_level_id IS NOT NULL";

	//select a column containing a code
	print "<div>" . T_("Select column containing the code: ");
	display_chooser($db->GetAll($sql),'code_column','code_column',true,"data_id=$data_id");
	print "</div>";

	if ($code_column != 0)
	{
		//select a column containing the text
		$text_column = 0;
		if (isset($_GET['text_column'])) $text_column = intval($_GET['text_column']);
	
		$sql = "SELECT c.column_id as value, c.description, CASE WHEN column_id = '$text_column' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `column` as c
			WHERE c.data_id = '$data_id'
			AND c.column_id != '$code_column'";
	
		//select a column containing a code
		print "<div>" . T_("Select column containing the keyword: ");
		display_chooser($db->GetAll($sql),'text_column','text_column',true,"data_id=$data_id&amp;code_column=$code_column");
		print "</div>";

		
		if ($text_column != 0)
		{
			print "<div><a href='?download=download&amp;data_id=$data_id&amp;code_column=$code_column&amp;text_column=$text_column'>" . T_("Download correspondence CSV") . "</a></div>";
		}
	}
}


xhtml_foot();
?>
