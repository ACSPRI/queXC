<?php
/**
 * Display an index of Admin tools
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
 * Language file
 */
include ("../lang.inc.php");

/**
 * Config file
 */
include ("../config.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

xhtml_head(T_("Administrative Tools"),true,array("../css/table.css","../css/admin.css"),array("../js/link.js"));

print "<div id='menu'><ul class='navmenu'>";

print "<li><h3>" . T_("Import data") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','import.php');\">" . T_("Import DDI/Fixed width data") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','importcsv.php');\">" . T_("Import CSV data") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','adddata.php');\">" . T_("Add data to existing (Fixed width)") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','adddatacsv.php');\">" . T_("Add data to existing (CSV)") . "</a></li></ul></li>";

print "<li><h3>" . T_("Codes") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','importcode.php');\">" . T_("Import code group") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','selectblankcode.php');\">" . T_("Select blank code for code group") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','createprocess.php');\">" . T_("Create process from code group") . "</a></li></ul></li>";


print "<li><h3>" . T_("Operator management") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','operatoradd.php');\">" . T_("Add operators") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','operatordata.php');\">" . T_("Assign operators to data") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','operatorprocess.php');\">" . T_("Assign operators to processes") . "</a></li></ul></li>";

print "<li><h3>" . T_("Job management") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','markcolumns.php');\">" . T_("Create work") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','relevantcolumns.php');\">" . T_("Assign relevant columns to codes") . "</a></li></ul></li>";

print "<li><h3>" . T_("Progress") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','progress.php');\">" . T_("Display progress") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','worklist.php');\">" . T_("List all work") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','modificationhistory.php');\">" . T_("Modification history") . "</a></li></ul></li>";


print "<li><h3>" . T_("Performance") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','operatorperformance.php');\">" . T_("Operator performance") . "</a></li></ul></li>";

print "<li><h3>" . T_("Export") . "</h3>";
print "<ul><li><a href=\"javascript:link('mainobj','export.php');\">" . T_("Export data") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','listdata.php');\">" . T_("List data") . "</a></li>";
print "<li><a href=\"javascript:link('mainobj','exportcode.php');\">" . T_("Export code groups") . "</a></li></ul></li>";

print "</ul></div>";


print "<div id='main'><object class='embeddedobject' id='mainobj' data='' standby='" . T_("Choose a menu item from the left") . "' type='application/xhtml+xml'><p>" . T_("Choose a menu item from the left") . "</p></object></div>";


xhtml_foot();

?>

