<?
/**
 * Functions related to XHTML code generation
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Display a javascript tag for each given path
 *
 * @param array A list of javascript include files
 *
 */
function xhtml_script($scripts)
{
	foreach ($scripts as $j) print "<script type='text/javascript' src='$j'></script>";
}

/**
 * Display a valid XHTML Strict header
 *
 * @param string $title HTML title
 * @param bool $body True if to display the end of the head/body
 * @param bool|array $css False for no CSS otherwise array of CSS include files
 * @param bool|array $javascript False for no Javascript otherwise array of Javascript include files
 * @param string $bodytext Space in the body element: good for onload='top.close()' to close validly
 * @param bool|int $refresh False or 0 for no refresh otherwise the number of seconds to refresh
 * @param bool $clearrefresh False if we want to pass on any GET request in header, True to clear
 * 
 * @see xhtml_foot()
 */
function xhtml_head($title="",$body=true,$css=false,$javascript=false,$bodytext=false,$refresh=false,$clearrefresh=false)
{
print "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
	   "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head><title><? if (empty($title)) print "queXC"; else print "queXC: $title"; ?></title>
<?
	if ($css)
		foreach ($css as $c) print "<link rel='stylesheet' href='$c' type='text/css'></link>";
	if ($javascript)
		xhtml_script($javascript);
	if ($refresh)
	{
		print " <!--Set to refresh every $refresh seconds-->
			<meta http-equiv='Cache-Control' content='no-cache'/>
			<meta http-equiv='refresh' content='$refresh";
		if ($clearrefresh) print ";url=?";
		print "'/>";
	}
	if (!$body) return;
?>
	</head>
<?
	if ($bodytext) print "<body $bodytext>"; else print "<body>";
}

/**
 * Print the string enclosed in the given tag
 *
 * @param string $string The string to print
 * @param string $tag The tag
 *
 */
function p($string,$tag)
{
	print "<$tag>$string</$tag>";
}

/**
 * Display a valid XHTML Strict footer
 *
 * @see xhtml_head()
 */

function xhtml_foot()
{
?>
	</body>
	</html>

<?
}

/**
 * Display a valid XHTML Strict table
 *
 * @param array $content Content from database usually an array of arrays
 * @param array $fields The names of fields to display
 * @param bool|array $head False if no header otherwise array of header titles
 * @param string $class Table CSS class
 * @param bool|array $highlight False if nothing to highlight else an array containing the field to highlight
 * 
 */
function xhtml_table($content,$fields,$head = false,$class = "tclass",$highlight=false)
{
	print "<table class='$class'>";
	if ($head)
	{
		print "<tr>";
		foreach ($head as $e)
			print"<th>$e</th>";
		print "</tr>";
	}
	foreach($content as $row)
	{
		if ($highlight && isset($row[key($highlight)]) && $row[key($highlight)] == current($highlight))
			print "<tr class='highlight'>";
		else
			print "<tr>";

		foreach ($fields as $e)
			print "<td>{$row[$e]}</td>";
		
		print "</tr>";
	}
	print "</table>";
}


/**
 * Display a drop down list based on a given array
 *
 * Example SQL:
 *  SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 *  FROM questionnaire
 *
 *
 * @param array $elements An array of arrays containing a value and a description and if selected (3 elements)
 * @param string $selectid The ID of the element
 * @param string $var The var name of the return string
 * @param bool $useblank Add a blank element to the start of the list
 * @param string|bool $pass Anything to pass along in the return string (remember to separate with &amp;)
 * @param bool $js Whether to use JS or not
 * @param bool $div Whether to display in a div or not
 *
 */
function display_chooser($elements, $selectid, $var, $useblank = true, $pass = false, $js = true, $div = true)
{
	if ($div) print "<div>";
	print "<select id='$selectid' name='$selectid' ";
	if ($js) print "onchange=\"LinkUp('$selectid')\"";
	print ">";
	if ($useblank)
	{
		print "<option value='";
		if ($js) print "?";
		if ($pass != false)
			print $pass;
		print "'></option>";
	}
	foreach($elements as $e)
	{
		if ($js)
		{
			print "<option value='?$var={$e['value']}";
			if ($pass != false)
				print "&amp;$pass";
			print "' ";
		}
		else
		{
			print "<option value='{$e['value']}' ";
		}
		if (isset($e['selected'])) print $e['selected']; 
		print ">".$e['description']."</option>";
	}
	print "</select>";
	if ($div) print "</div>";
}


function xhtml_object($data, $id, $class="embeddedobject")
{
	if (browser_ie())
		print '<iframe class="'.$class.'" id="'.$id.'" src="'.$data.'" frameBorder="0"><p>Error, try with Firefox</p></iframe>';
	else
		print '<object class="'.$class.'" id="'.$id.'" data="'.$data.'" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>';
}


/**
 * Detect if the user is running on internet explorer
 *
 * @return bool True if MSIE is detected otherwise false
 */
function browser_ie()
{
    if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
        return true;
    else
        return false;
}


?>
