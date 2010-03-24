<?
/**
 *  Functions relating to the display and creation of codes
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
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * Display a group of codes given the column_group_id
 *
 * @param int $column_group_id The column_group_id
 */
function display_code_group($column_group_id)
{
	global $db;

	$codes = array();
	$last = 0; //Make sure we don't automatically select this code
	$allow = false; //Not allowed to automatically add codes

	//First see if we are allowed to add codes to this code
	$sql = "SELECT cg.allow_additions, cg.code_group_id
		FROM code_group as cg, `column_group` as cog
		WHERE cg.code_group_id = cog.code_group_id
		AND cog.column_group_id = $column_group_id";
	
	$allowa = $db->GetRow($sql);
	
	if (!empty($allowa) && $allowa['allow_additions'] == 1)
		$allow = true;

	$code_group_id = $allowa['code_group_id'];

	$sql = "SELECT c.code_level_id, c.column_id
		FROM `column` as c, code_level as cl
		WHERE c.column_group_id = '$column_group_id'
		AND cl.code_level_id = c.code_level_id	
		ORDER BY cl.level ASC";

	$levels = $db->GetAll($sql);

	//display levels only up to where selected

	foreach($levels as $level)
	{
		$code_level_id = $level['code_level_id'];
		$column_id = $level['column_id'];

		$sql = "SELECT c.code_id, c.label, c.value
			FROM `code` as c
			WHERE c.code_level_id = '$code_level_id'";

		$codes = $db->GetAll($sql);

		print "<div class='level'>";
		foreach($codes as $code)
		{
			print "<div class='row'><input class='rab' type='radio' name='cc" . $code_level_id . "X" . $column_group_id . "' value='{$code['value']}' id='ccc{$code['code_id']}X" . $code_level_id . "X" . $column_group_id . "'";
	//if ($base && $r['code_id'] == $c && !($last == 1 && ($count == $total)))
	//{
	//	print " checked='checked'";
	//			$lastcode = 1;
	//			$parentcode = $r['code_id'];
	//		}
			print "/><label for='ccc{$code['code_id']}X" . $code_level_id . "X" . $column_group_id . "'>{$code['value']}:{$code['label']}</label></div>";
		}
		print "</div>";
	}
}


/**
 * Display codes given the work_id
 * 
 * @param int $work_unit_id The given work_unit_id
 * @param bool|int $operator_id The operator_id
 * @param string $cdata The code data to search on
 * @see display_all_codes
 */
function display_codes($work_unit_id, $operator_id = false,$cdata = "")
{
	global $db;

	//If the operator_id is set, find out if auto_code is set for this operator and process
	$sql = "SELECT op.auto_code, w.column_multi_group_id as cmgi, w.column_group_id as cgi, ce.row_id as row_id
		FROM operator_process as op, work as w, work_unit as wu, `cell` as ce
		WHERE op.operator_id = $operator_id
		AND op.process_id = w.process_id
		AND w.work_id = wu.work_id
		AND wu.work_unit_id = '$work_unit_id'
		AND ce.cell_id = wu.cell_id";
	
	$ac = $db->GetRow($sql);

	//If this is a multi-group - loop over each unique column group and display entire code set
	if (!empty($ac['cmgi'])) //multi group
	{
		$sql = "SELECT c.column_group_id as cgi, c.column_id, c.description
			FROM `column` as c
			WHERE c.column_multi_group_id = '{$ac['cmgi']}'
			GROUP BY c.column_group_id";

		$rs = $db->GetAll($sql);
		
		print "<script type='text/javascript'>";
		print "document.addEvent('domready', function(){ function jsfcheck(item, state){ alert('test');  } var testMenu = new Mif.Menu().attach('menu-target').load(";
	
		$json = array();
		

		foreach($rs as $r)
		{
			//display root element (checkbox)

			$column_group_id = $r['cgi'];
			$column_description = $r['description'];
		
			$tmp = (object) array('name' => $r['description'], 'id' => 'ci' . $r['column_id'], 'submenu' => 0);
			$tmp->submenu = array();


			//can use action to call a function

			$codes = array();
		
			$sql = "SELECT c.code_level_id, c.column_id
				FROM `column` as c, code_level as cl
				WHERE c.column_group_id = '{$r['cgi']}'
				AND cl.code_level_id = c.code_level_id	
				ORDER BY cl.level ASC";

			$levels = $db->GetAll($sql);


			foreach($levels as $level)
			{
				$code_level_id = $level['code_level_id'];
				$column_id = $level['column_id'];

				$sql = "SELECT c.code_id, c.label, c.value
					FROM `code` as c
					WHERE c.code_level_id = '$code_level_id'";

				$codes = $db->GetAll($sql);

/*				$tmp->submenu[] = (object) array('options' => "{
						onCheck: function(item, state){
							alert('test');
						}
					}");*/

				foreach($codes as $code)
				{
					//print "<div class='row'><input class='rab' type='radio' name='cc" . $code_level_id . "X" . $column_group_id . "' value='{$code['value']}' id='ccc{$code['code_id']}X" . $code_level_id . "X" . $column_group_id . "'";


					//This javascript needs to:
					//	1. search for an existing element of the same name, and if so, append text to it/update value
					//	2. add a new radio input element with the selected value and the text selected - clicking itself should destroy itself
					$jse = "cc" . $code_level_id . "X" . $column_group_id;
					$jset = "tt" . $column_group_id;
					$jsed = "dd" . $code_level_id . "X" . $column_group_id;
					$value = $code['value'];
					$label = $code['label'];

					$js = "	function(item, state) {
							var el = $('$jse');

							if (el != null)
							{
								$('$jsed').destroy();
							}

							txt = $('selectedText').get('value');
							tel = new Element('input');
							tel.set('name', '$jset');
							tel.set('value', txt);
							tel.set('size', txt.length);
							tel.set('id', '$jset');
							tel.set('type', 'text');
							tel.set('readonly','readonly');
							nel = new Element('input');
							nel.set('name', '$jse');
							nel.set('value', '$value');
							nel.set('id', '$jse');
							nel.set('type', 'radio');
							nel.set('checked','true');
							lel = new Element('label');
							lel.set('for', '$jse');
							lel.set('html', '$column_description - $value:$label');
							lel.set('onclick','$(\'$jsed\').destroy();');							

							del = new Element('div');
							del.set('id','$jsed');
							del.adopt(tel);
							del.adopt(lel);
							del.adopt(nel);
							document.id('cleancode').adopt(del);
						}";
					

					$tmp->submenu[] = (object) array('name' => $code['label'], 'checked' => 'false', 'group' => 'ci' . $r['column_id'], 'check' => 'jsfcheck', 'action' => $js);
				}
				break; //temporary: only use first level
			}
			
			$json[] = $tmp;
		}

		print json_encode($json);
		print ")})</script>";
	}
	else if (empty($ac) || $ac['auto_code'] == 0)
	{
		//get a code from this code group

		$sql = "SELECT c.code_id,w.column_group_id
			FROM code as c
			JOIN work_unit AS wu ON (wu.work_unit_id = '$work_unit_id')
			JOIN work as w ON (w.work_id = wu.work_id)
			JOIN column_group as cg ON (cg.column_group_id = w.column_group_id)
			JOIN code_level as cl ON (cl.code_group_id = cg.code_group_id AND c.code_level_id = cl.code_level_id)
			LIMIT 1";

		$r = $db->GetRow($sql);
	
		if (!empty($r))
			display_all_codes($r['code_id'],false,$work_unit_id);
	}
	else
	{
		$sql = "SELECT c.code_id, (c.label LIKE '%$cdata%' OR c.keywords LIKE '%$cdata%') as test,w.column_group_id
			FROM code as c
			JOIN work_unit as wu ON (wu.work_unit_id = '$work_unit_id')
			JOIN work as w ON (w.work_id = wu.work_id)
			JOIN column_group as cg ON (cg.column_group_id = w.column_group_id)
			JOIN code_level as cl ON (cl.code_group_id = cg.code_group_id AND c.code_level_id = cl.code_level_id)
			ORDER BY (c.label LIKE '%$cdata%' OR c.keywords LIKE '%$cdata%') DESC, cl.level ASC
			LIMIT 1";

		$r = $db->GetRow($sql);

		if (!empty($r))
		{
			$tmp = true;
			if ($r['test'] == 0) $tmp = false;
			display_all_codes($r['code_id'],$tmp,$work_unit_id);
		}


	}
}

/**
 * Display multiple choice codes (root is multiple choice, each code (if any) is a child
 * Each column is a root multiple choice code
 *
 * @param int $cmgi Column multi group id
 *
 */
function display_multi_root($cmgi)
{
	global $db;

	//Get all columns with this $cmgi
	$sql = "SELECT column_id,description,code_level_id
		FROM `column`
		WHERE column_multi_group_id = '$cmgi'";

	$rs = $db->GetAll($sql);
	
	//Display columns as rows with check boxes.
	print "<div class='level'>";	
	foreach($rs as $r)
	{
		print "<div class='row'><input class='cb' type='checkbox' name='ci{$r['column_id']}' id='ci{$r['column_id']}' value='1' ";
		if (false)
		{
			print " checked='checked'";
		}
		print "/><label for='ci{$r['column_id']}'>{$r['description']}</label>"; 
	
		print "</div>";
		//display codes under this column
		$sql = "SELECT c.code_id
			FROM code as c
			WHERE c.code_level_id = '{$r['code_level_id']}'
			LIMIT 1";

		$a = $db->GetRow($sql);
	
		if (!empty($a))
		{
			print "<div class='subcode' id='sc{$r['column_id']}' style='display:inline;'>";
			display_all_codes($a['code_id'],false);
			print "</div>";
		}

	}

	//When check boxes selected, should display all relevant codes (if any)


	//Allow for adding a new column
	print "<div class='row'><div><input type='hidden' name='newcodemulti' value='$cmgi'/><input type='text' name='newcodetextm'/><input type='submit' name='submit_add_multi' value='" . T_("Add") . "'/></div></div>";

	
	print "</div>";
}

/** 
 * Display all codes relavent to the given code (all parents and one child if any)
 * 
 * @param int $code_id The given code
 * @param bool $base Whether this is the base group or not
 * @param int $work_unit_id The work_unit_id this belongs to
 * @see display_codes
 */
function display_all_codes($code_id, $base = true, $work_unit_id)
{
	global $db;

	$codes = array();
	$last = 0; //Make sure we don't automatically select this code
	$allow = false; //Not allowed to automatically add codes

	//First see if we are allowed to add codes to this code
	$sql = "SELECT cg.allow_additions,w.column_group_id
		FROM code_group as cg, code_level as cl, code as c, work_unit as wu, work as w
		WHERE c.code_id = $code_id
		AND cl.code_level_id = c.code_level_id
		AND cg.code_group_id = cl.code_group_id
		AND wu.work_unit_id = '$work_unit_id'
		AND w.work_id = wu.work_id";
	
	$allowa = $db->GetRow($sql);

	
	if (!empty($allowa) && $allowa['allow_additions'] == 1)
		$allow = true;

	$column_group_id = $allowa['column_group_id'];

	if ($base)
	{
		//Add a child code if any
		$rs3 = $db->GetRow("	SELECT code_id
					FROM code_parent
					WHERE parent_code_id = '$code_id'");
	
		if (!empty($rs3)){
			$codes[] = $rs3['code_id'];
			$last = 1;
		}
	}

	$codes[] = $code_id;
	
	//Get all parents
	while (($rs = $db->GetRow("SELECT parent_code_id
					FROM code_parent
					WHERE code_id = '$code_id'")))
	{
		$code_id = $rs['parent_code_id'];
		$codes[] = $code_id;
	}

	//Make sure we do this in the right order
	$codes = array_reverse($codes);

	$count = 0;
	$total = count($codes) - 1;

	$newcode = "";

	foreach($codes as $c)
	{
		//Display

		$lastcode = 0;
		$parentcode = 0;


		$sql = "SELECT c.code_level_id,c.code_id,c.label,c.value
			FROM code as c
			JOIN code AS c2 ON (c2.code_id = '$c' AND c2.code_level_id = c.code_level_id)";

		if ($count > 0)
			$sql .= "JOIN code_parent as cp ON (cp.code_id = '$c')
			 JOIN code_parent as cp2 ON (cp2.parent_code_id = cp.parent_code_id AND c.code_id = cp2.code_id)";
	
		$rs2 = $db->GetAll($sql);
		
		print "<div class='level'>";
		foreach($rs2 as $r)
		{
			print "<div class='row'><input class='rb' type='radio' name='cc{$r['code_level_id']}X$column_group_id' value='{$r['value']}' id='ccc{$r['code_id']}X{$r['code_level_id']}X$column_group_id'";
			if ($base && $r['code_id'] == $c && !($last == 1 && ($count == $total)))
			{
				print " checked='checked'";
				$lastcode = 1;
				$parentcode = $r['code_id'];
			}
			print "/><label for='ccc{$r['code_id']}X{$r['code_level_id']}X$column_group_id'>{$r['value']}:{$r['label']}</label></div>";
		}
		if ($allow && ($count == $total))
			print "<div class='row'><div><input type='hidden' name='newcodesibling' value='{$r['code_id']}'/><input type='hidden' name='newcodelevel' value='{$r['code_level_id']}'/><input type='text' name='newcodevaluea' size='2'/><input type='text' name='newcodetexta'/><input type='submit' name='submit_add_sibling' value='" . T_("Add") . "'/></div></div>";
		print "</div>";

		//If a code on the last level has been selected - add a new level with the ability to add a code
		if ($allow && ($lastcode && ($count == $total)))
		{
			print "<div class='level'><div class='row'><div><input type='hidden' name='newcodeparent' value='$parentcode'/><input type='text' name='newcodevalueb' size='2'/><input type='text' name='newcodetextb'/><input type='submit' name='submit_add_parent' value='" . T_("Add") . "'/></div></div></div>";
		}

		$count++;
	}
}


/**
 * Add a code either in an existing level, or also create a new level
 *
 * @param array $post The posted data containing newcodevalue,newcodetext and either newcodeparent or newcodelevel and newcodesibling
 */
function add_code($post)
{
	global $db;

	$code_id = false;

	$db->StartTrans();

	//If we have the sibling and level
	if (isset($post['submit_add_sibling']) && !empty($post['newcodevaluea']) && !empty($post['newcodetexta']))
	{
		$value = $db->qstr($post['newcodevaluea']);
		$text = $db->qstr($post['newcodetexta']);
		$width = strlen($post['newcodevaluea']); //length of the actual entered text

		$level = intval($post['newcodelevel']);
		$sibling = intval($post['newcodesibling']);
		
		$sql = "SELECT width
			FROM code_level
			WHERE code_level_id = $level";

		$clwidth = $db->GetRow($sql);

		//todo: check for type of variable

		if ($width > $clwidth['width']) //if the entered value is wider than the current column
		{
			$sql = "UPDATE code_level
				SET width = '$width'
				WHERE code_level_id = $level";

			$db->Execute($sql);

			//update any columns relying on this code
			$sql = "UPDATE `column`
				SET width = '$width'
				WHERE code_level_id = $level";

			$db->Execute($sql);
		}

		$sql = "INSERT INTO code (code_id,value,label,keywords,code_level_id)
			VALUES (NULL,$value,$text,NULL,$level)";
		$db->Execute($sql);
		
		$code_id = $db->Insert_ID();

		$sql = "INSERT INTO code_parent (code_id,parent_code_id)
			SELECT $code_id,parent_code_id
			FROM code_parent
			WHERE code_id = $sibling
			LIMIT 1";
		$db->Execute($sql);
	}
	else if (isset($post['submit_add_parent']) && !empty($post['newcodevalueb']) && !empty($post['newcodetextb'])) //If we just have the parent - we may need to create a new level
	{
		$value = $db->qstr($post['newcodevalueb']);
		$text = $db->qstr($post['newcodetextb']);

		$parent = intval($post['newcodeparent']);

		//Find any children
		$sql = "SELECT code_id
			FROM code_parent
			WHERE parent_code_id = $parent";

		$allchildren = $db->GetAll($sql);

		if (empty($allchildren))
		{
			//Get the level of the parent code
			$sql = "SELECT cl.code_level_id, cl.code_group_id, cl.level
				FROM code as c, code_level as cl
				WHERE c.code_id = $parent
				AND cl.code_level_id = c.code_level_id";

			$codelevel = $db->GetRow($sql);

			//See if there is another level
			$sql = "SELECT code_level_id
				FROM code_level
				WHERE code_group_id = {$codelevel['code_group_id']}
				AND level > {$codelevel['level']}
				ORDER BY level ASC
				LIMIT 1";

			$newlevel = $db->GetRow($sql);

			//Create a level if it doesn't exist
			if (empty($newlevel))
			{
				$level = $codelevel['level'];
				$level = $level + 1;
				$width = strlen($post['newcodevalueb']); //length of the actual entered text
				$sql = "INSERT INTO code_level (code_level_id,code_group_id,level,width)
					VALUES (NULL,{$codelevel['code_group_id']},$level,$width)";
				$db->Execute($sql);
				$code_level_id = $db->Insert_ID();
			}
			else
				$code_level_id = $newlevel['code_level_id'];

			//Now insert the code
			$sql = "INSERT INTO code (code_id,value,label,keywords,code_level_id)
				VALUES (NULL,$value,$text,NULL,$code_level_id)";
			$db->Execute($sql);
			
			$code_id = $db->Insert_ID();
	
			$sql = "INSERT INTO code_parent (code_id,parent_code_id)
				VALUES ($code_id,$parent)";
			$db->Execute($sql);
		}

	}
	else if (isset($post['submit_add_multi'])) //Add multi level code
	{
		//Must create a new column and a new code level based on the current process

		$cmgi = $db->qstr($post['newcodemulti']);
		$desc = $db->qstr($post['newcodetextm']);

		$sql = "INSERT INTO `column` (data_id,column_group_id,column_multi_group_id,name,description,width)
				SELECT data_id,column_group_id,$cmgi,name,$desc,'1'
				FROM `column` 
				WHERE column_multi_group_id = $cmgi
				LIMIT 1";

		$db->Execute($sql);
	}

	if ($db->CompleteTrans())
		return $code_id;

	return false;
}


?>
