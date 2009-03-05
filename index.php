<?
/**
 * Display the main cleaning/coding page
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

/**
 * Code functinos
 */
include("functions/functions.code.php");

/**
 * Process functinos
 */
include("functions/functions.process.php");

if (isset($_GET['display_codes']))
{
	display_all_codes(intval($_GET['display_codes']));
	print "<script type='text/javascript'>updateevents();</script>";
	exit();
}

$operator_id = get_operator_id();
$code_id = false;

if (isset($_POST['work_unit_id']))
{
	//If we are adding a new code...
	if (isset($_POST['submit_add_parent']) || isset($_POST['submit_add_sibling']))
		$code_id = add_code($_POST);
	else
		save_work_process($_POST); //Posted - save the data 

	//If we are ending work, include endwork function and exit here
	if (isset($_POST['submit_end']))
	{
		include("endwork.php");
		exit();
	}
}

xhtml_head(T_("queXC"), true, array("css/table.css","css/index.css","include/ajax-spell/styles.css"),array('include/ajax-spell/mootools-1.2-core-yc.js','js/index.js'));

//See if there is work for us, if not, display "No more work" and allow for the page to be refreshed, or work to end


if ($operator_id != false)
{
	$work_unit_id = get_work($operator_id);
	
	print "<div id='content'>";

	if ($work_unit_id == false)
	{
		p(T_("No more work currently available"),"p");
		print "<p><a href=''>" . T_("Try again for more work") . "</a></p>";
		print "<p><a href='endwork.php'>" . T_("End work") . "</a></p>";
	}
	else
	{
		print "<div id='heading'>";
		//print "<h2>" . get_work_data_description($work_unit_id) . "</h2>";
		print "</div>";
		
		print "<div id='othervariables'>";
		$othervariables = get_work_other_variables($work_unit_id);
		if (!empty($othervariables))
			xhtml_table($othervariables,array('name','data'));
		print "</div>";
	
		print "<div id='thiscolumn'>";
		$thiscolumn = get_work_column($work_unit_id);
		print "<p>{$thiscolumn['name']}: {$thiscolumn['description']}</p><h3>{$thiscolumn['data'][0]}</h3>";
		print "</div>";
	
		print "<form action='' method='post' id='cleancodeform'>";
		print "<div id='cleancode'>";
		$r = get_work_process($work_unit_id);
		$cell_id = $r['cell_id'];
		$process_id = $r['process_id'];
		$work_id = $r['work_id'];
		$cdata = get_cell_data($cell_id);

		$process_function = get_process_function($process_id);

		if ($process_function == false) //coding
		{
			//coding
			print "<div class='header' id='header'>";
			if ($code_id == false)
				display_codes($work_id,$operator_id,$cdata[0]);
			else
				display_all_codes($code_id);
			print "</div>";
		}
		else
		{
			if (is_callable($process_function))
				call_user_func($process_function,$cell_id,$cdata[0]);
			else
				print "<p>" . T_("Error: Cannot execute process function:") . " $process_function</p>";
		}
		
		print "<input type='hidden' name='work_unit_id' value='$work_unit_id'/></div>";
		print "<p><input type='submit' name='submit' value='" . T_("Submit and continue") . "'/>  <input type='submit' name='submit_end' value='" . T_("Submit and end work") . "'/></p>";
		print "</form>";
	}
	
	print "</div>";

	print "<div id='workhistory'><object class='embeddedobject' id='work-history' data='workhistory.php' standby='" . T_("Loading panel...") . "' type='application/xhtml+xml'><p>" . T_("Error, try with Firefox") . "</p></object></div>";
}
else
	p(T_("No operator"),"p");

xhtml_foot();


?>
