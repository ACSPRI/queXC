<?php 
/**
 * Spelling functions
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
 * Spell checking needs to be global or memory leak occurs
 */
$pspell_config = pspell_config_create(SPELL_LANGUAGE,SPELL_SPELLING);
pspell_config_mode($pspell_config, PSPELL_FAST);
pspell_config_personal($pspell_config, SPELL_DICTIONARY_FILE);
$pspell_link = pspell_new_config($pspell_config);

/**
 * Add a word to the default dictionary
 * 
 * @param string $word The word to add to the dictionary
 */
function add_to_dictionary($word)
{
	global $pspell_link;
	
	pspell_add_to_personal($pspell_link, $word);

	//save additions
	pspell_save_wordlist($pspell_link);
}

/**
 * Check an entire string
 *
 * @param string $words The string of words to check
 * @return int 2 if blank, 1 if spelt incorrectly, 0 if correct
 */
function check_words($words)
{
	if (empty($words) || $words == " ") return 2;

	$result = 0;

	$wordlist = explode(" ", remove_nonalpha($words));

	foreach($wordlist as $word)
		if (check_word($word) == 1)
			return 1;

	return 0;
}

/**
 * Check the spelling of a word using the global dictionary
 * 
 * @param string $word The word to check
 * @return int 2 if blank, 1 if spelt incorrectly, 0 if correct
 */
function check_word($word) {

	if (empty($word) || $word == " ") return 2;
	
	global $pspell_link;

	if (pspell_check($pspell_link, $word))
		return 0; // correct
	else
		return 1; // incorrect spelling
}


/**
 * Return an array of suggestions for the mis-spelt word
 *
 * @param string $word The misspelt word
 * @return array An array of words which are suggestions for this misspelt word
 */
function suggested_words($word) {

	global $pspell_link;
	
	$suggestions = pspell_suggest($pspell_link, $word);
	return $suggestions;
}



?>
