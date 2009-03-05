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
 * Display codes given the work_id
 * 
 * @param int $work_id The given work_id
 * @param bool|int $operator_id The operator_id
 * @param string $cdata The code data to search on
 * @see display_all_codes
 */
function display_codes($work_id,$operator_id = false,$cdata = "")
{
	global $db;

	//If the operator_id is set, find out if auto_code is set for this operator and process
	$sql = "SELECT op.auto_code
		FROM operator_process as op, work as w
		WHERE op.operator_id = $operator_id
		AND op.process_id = w.process_id
		AND w.work_id = '$work_id'";
	
	$ac = $db->GetRow($sql);

	if (empty($ac) || $ac['auto_code'] == 0)
	{
		//get a code from this code group

		$sql = "SELECT c.code_id
			FROM code as c
			JOIN work as w ON (w.work_id = '$work_id')
			JOIN column_group as cg ON (cg.column_group_id = w.column_group_id)
			JOIN code_level as cl ON (cl.code_group_id = cg.code_group_id AND c.code_level_id = cl.code_level_id)
			LIMIT 1";

		$r = $db->GetRow($sql);
	
		if (!empty($r))
			display_all_codes($r['code_id'],false);
	}
	else
	{
		$sql = "SELECT c.code_id, (c.label LIKE '%$cdata%' OR c.keywords LIKE '%$cdata%') as test
			FROM code as c
			JOIN work as w ON (w.work_id = '$work_id')
			JOIN column_group as cg ON (cg.column_group_id = w.column_group_id)
			JOIN code_level as cl ON (cl.code_group_id = cg.code_group_id AND c.code_level_id = cl.code_level_id)
			ORDER BY (c.label LIKE '%$cdata%' OR c.keywords LIKE '%$cdata%') DESC, cl.level ASC
			LIMIT 1";

		$r = $db->GetRow($sql);

		if (!empty($r))
		{
			$tmp = true;
			if ($r['test'] == 0) $tmp = false;
			display_all_codes($r['code_id'],$tmp);
		}


	}

}

/** 
 * Display all codes relavent to the given code (all parents and one child if any)
 * 
 * @param int $code_id The given code
 * @param bool $base Whether this is the base group or not
 * @see display_codes
 */
function display_all_codes($code_id, $base = true)
{
	global $db;

	$codes = array();
	$last = 0; //Make sure we don't automatically select this code
	$allow = false; //Not allowed to automatically add codes

	//First see if we are allowed to add codes to this code
	$sql = "SELECT cg.allow_additions
		FROM code_group as cg, code_level as cl, code as c
		WHERE c.code_id = $code_id
		AND cl.code_level_id = c.code_level_id
		AND cg.code_group_id = cl.code_group_id";
	
	$allowa = $db->GetRow($sql);
	
	if (!empty($allowa) && $allowa['allow_additions'] == 1)
		$allow = true;

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
			print "<div class='row'><input class='rb' type='radio' name='cli{$r['code_level_id']}' value='{$r['value']}' id='c{$r['code_id']}'";
			if ($base && $r['code_id'] == $c && !($last == 1 && ($count == $total)))
			{
				print " checked='checked'";
				$lastcode = 1;
				$parentcode = $r['code_id'];
			}
			print "/><label for='c{$r['code_id']}'>{$r['value']}:{$r['label']}</label></div>";
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

		$level = intval($post['newcodelevel']);
		$sibling = intval($post['newcodesibling']);
		
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
				$width = strlen($value);
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

	if ($db->CompleteTrans())
		return $code_id;

	return false;
}


?>
