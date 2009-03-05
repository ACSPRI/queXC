<?
/**
 * Export Data 
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

/**
 * Export functions
 */
include("../functions/functions.export.php");

if (isset($_GET['data']))
{
	export_fixed_width(intval($_GET['data']));
	exit();
}

if(isset($_GET['ddi']))
{
	export_ddi(intval($_GET['ddi']));
	exit();
}

if(isset($_GET['csv']))
{
	export_csv(intval($_GET['csv']));
	exit();
}

if(isset($_GET['pspp']))
{
	export_pspp(intval($_GET['pspp']));
	exit();
}

xhtml_head(T_("Export Data"),true,array("../css/table.css"),array("../js/display.js"));

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
	//List download links for fixed width and data file

	print "<p><a href='?data_id=$data_id&amp;data=$data_id'>" . T_("Export fixed width data file") . "</a></p>";
	print "<p><a href='?data_id=$data_id&amp;ddi=$data_id'>" . T_("Export DDI file") . "</a></p>";
	print "<p><a href='?data_id=$data_id&amp;pspp=$data_id'>" . T_("Export PSPP file") . "</a></p>";
	print "<p><a href='?data_id=$data_id&amp;csv=$data_id'>" . T_("Export CSV file") . "</a></p>";
}


xhtml_foot();
?>
