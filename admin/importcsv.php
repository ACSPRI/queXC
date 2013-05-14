<?php 
/**
 * Import a data file from a CSV file
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

xhtml_head(T_("Import data file"));

if (isset($_POST['import_file']))
{
	//file has been submitted
	$datafname = tempnam("/tmp", "FOO");
	move_uploaded_file($_FILES['datafile']['tmp_name'],$datafname);

	$data_id = new_data($_POST['description']);
	if ($data_id)
	{
		print "<p>" . T_("Created new data entry: ") .  "$data_id</p>";
		$ds = import_csv_columns($datafname,$data_id);
		if ($ds)
		{
			print "<p>" . T_("Created column strucure") .  "</p>";
			$is = import_csv_data($datafname,$data_id);
			if ($is)
				print "<p>" . T_("Data imported successfully") . "</p>";
		}
	}
	
}
else
{
	?>
	<form enctype="multipart/form-data" action="" method="post">
	<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
	<p><?php  echo T_("Choose the headered CSV data file to upload:"); ?><input name="datafile" type="file" /></p>
	<p><?php  echo T_("Description for file:"); ?><input name="description" type="text" /></p>
	<p><input type="submit" name="import_file" value="<?php  echo T_("Create new data file"); ?>"/></p>
	</form>

	<?php 

}

xhtml_foot();
?>
