<?
/**
 * Add data to an existing data file from a CSV file
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
 * Import functions
 */
include("../functions/functions.import.php");

xhtml_head(T_("Import data file"),true,false,array('../js/display.js'));

if (isset($_POST['import_file']) && isset($_GET['data_id']) && isset($_GET['column_id']))
{
	//file has been submitted
	$datafname = tempnam("/tmp", "FOO");
	move_uploaded_file($_FILES['datafile']['tmp_name'],$datafname);

	$data_id = intval($_GET['data_id']);
	$column_id = intval($_GET['column_id']);

	$is = import_csv_data($datafname,$data_id,$column_id);
	if ($is != false)
	{
		$rows = count($is);
		print "<p>" . T_("Data imported successfully") . " " . T_("Rows:") . " $rows</p>";
		if ($rows > 0)
		{	
			include("../functions/functions.work.php");
			if (run_auto_processes(get_auto_processes($data_id),$is))
				p(T_("Successfully ran automatic processes"),"p");	
			else
				p(T_("Failed to run automatic processes"),"p");	
		}
	}
	else
		p(T_("Failed to import data"),"p");	
			
}
else
{
	//First choose data file
	$data_id = 0;
	if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

	//Select a data file to mark
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
			WHERE data_id = '$data_id'
			AND in_input = 1";
	
		print "<div>" . T_("Select column with unique identifier: ");
		$c = $db->GetAll($sql);
	
		display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
		print "</div>";
	
		if ($column_id != 0)
		{

			?>
			<form enctype="multipart/form-data" action="" method="post">
			<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
			<p><? echo T_("Choose the CSV data file to upload (Can be compressed with gz):"); ?><input name="datafile" type="file" /></p>
			<p><input type="submit" name="import_file" value="<? echo T_("Add records to data file"); ?>"/></p>
			</form>
			<?

		}
	}
}

xhtml_foot();
?>
