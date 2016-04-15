<?php
/**
 * @package         ReReplacer
 * @version         7.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/regularlabs/helpers/assignments.php';

class PlgSystemReReplacerHelperAssignments
{
	public function __construct()
	{
		$this->assignments = new RLAssignmentsHelper;
	}

	public function itemPass($item, $article = 0)
	{
		$ass  = $this->assignments->getAssignmentsFromParams($item);
		$pass = $this->assignments->passAll($ass, $item->match_method, $article);

		if (!$pass && $item->other_doreplace)
		{
			$item->replace = $item->other_replace;
			// replace \n with newline
			$item->replace = preg_replace('#(?<!\\\)\\\n#', "\n", $item->other_replace);
			$pass          = 1;
		}

		return $pass ? $item : 0;
	}
}
/* <<< [PRO] <<< */
