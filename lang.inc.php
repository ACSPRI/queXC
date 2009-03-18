<?
/**
 * Language configuration file
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
 * @subpackage configuration
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * The phpgettext package
 */
require_once(dirname(__FILE__).'/include/php-gettext-1.0.7/gettext.inc');

/**
 * Translate the given elements of the array
 *
 * @param array The array to translate
 * @param array The elements in the array to translate
 * @return The array with the elements translated
 */
function translate_array(&$a,$b)
{
	foreach ($a as &$row)
		foreach($b as $el)
			if (isset($row[$el])) $row[$el] = T_($row[$el]);
}


$locale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
if (empty($locale)) $locale = DEFAULT_LOCALE;
T_setlocale(LC_MESSAGES, $locale);
T_bindtextdomain($locale,  dirname(__FILE__)."/locale");
T_bind_textdomain_codeset($locale, 'UTF-8');
T_textdomain($locale);

?>
