<?php
/**
 * @package         Snippets
 * @version         5.0.4
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Item Table
 */
class SnippetsTableItem extends JTable
{
	/**
	 * Constructor
	 *
	 * @param    object    Database object
	 *
	 * @return    void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__snippets', 'id', $db);
	}

	/**
	 * Overloaded check function
	 *
	 * @return boolean
	 */
	public function check()
	{
		$this->name  = trim($this->name);
		$this->alias = trim($this->alias);

		// Check for valid name
		if (empty($this->name))
		{
			$this->setError(JText::_('SNP_THE_ITEM_MUST_HAVE_A_NAME'));

			return false;
		}

		if (empty($this->alias))
		{
			$this->setError(JText::_('SNP_THE_ITEM_MUST_HAVE_AN_ID'));

			return false;
		}

		if ($this->aliasExists())
		{
			$this->setError(JText::sprintf('SNP_ID_ALREADY_EXISTS', $this->alias));

			return false;
		}

		return true;
	}

	private function aliasExists()
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('count(id)')
			->from($db->quoteName('#__snippets'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($this->alias));

		if (!empty($this->id))
		{
			$query->where($db->quoteName('id') . ' != ' . (int) $this->id);
		}

		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}
}
