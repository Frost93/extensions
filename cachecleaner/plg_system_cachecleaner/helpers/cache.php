<?php
/**
 * @package         Cache Cleaner
 * @version         5.0.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2016 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class PlgSystemCacheCleanerHelperCache
{
	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = PlgSystemCacheCleanerHelpers::getInstance();

		$this->params = $this->helpers->getParams();
	}

	public function getIgnoreFolders()
	{
		if (empty($this->params->ignore_folders))
		{
			return array();
		}

		if (!empty($this->ignore_folders))
		{
			return $this->ignore_folders;
		}

		$ignore_folders = explode("\n", str_replace('\n', "\n", $this->params->ignore_folders));
		foreach ($ignore_folders as &$folder)
		{
			if (trim($folder) == '')
			{
				continue;
			}
			$folder = rtrim(str_replace('\\', '/', trim($folder)), '/');
			$folder = str_replace('//', '/', JPATH_SITE . '/' . $folder);
		}

		return $ignore_folders;
	}


	public function emptyFolders()
	{
		// Empty tmp folder
		if ($this->params->clean_tmp)
		{
			$this->emptyFolder(JPATH_SITE . '/tmp');
		}

	}


	public function emptyFolder($path)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		if (!JFolder::exists($path))
		{
			return;
		}

		if ($this->params->show_size)
		{
			$size = $this->getFolderSize($path);
		}

		// remove folders
		$folders = JFolder::folders($path);
		foreach ($folders as $folder)
		{
			$f = $path . '/' . $folder;
			if (in_array($f, $this->getIgnoreFolders()) || !@opendir($path . '/' . $folder))
			{
				continue;
			}

			if ($this->isIgnoredParent($f))
			{
				$this->emptyFolder($f);
				continue;
			}

			if (!JFolder::delete($path . '/' . $folder))
			{
				$this->setError(JText::sprintf('JLIB_FILESYSTEM_ERROR_FOLDER_DELETE', $path . '/' . $folder));
			}

			// Zoo folder needs to be placed back, otherwise Zoo will break (stupid!)
			if ($folder == 'com_zoo')
			{
				JFolder::create($path . '/' . $folder);
			}
		}

		// remove files
		$files = JFolder::files($path);
		foreach ($files as $file)
		{
			if ($file == 'index.html' || in_array($path . '/' . $file, $this->getIgnoreFolders()))
			{
				continue;
			}

			if (!JFile::delete($path . '/' . $file))
			{
				$this->setError(JText::sprintf('JLIB_FILESYSTEM_DELETE_FAILED', $path . '/' . $file));
			}
		}

		if ($this->params->show_size)
		{
			$size -= $this->getFolderSize($path);
			$this->helpers->getParams()->size += $size;
		}
	}

	/*
	 * Check if folder is a parent path of something in the ignore list
	 */
	public function isIgnoredParent($path)
	{
		$check = $path . '/';
		$len   = strlen($check);

		foreach ($this->getIgnoreFolders() as $ignore_folder)
		{
			if (substr($ignore_folder, 0, $len) == $check)
			{
				return true;
			}
		}

		return false;
	}

	public function getFolderSize($path)
	{
		jimport('joomla.filesystem.file');

		if (JFile::exists($path))
		{
			return @filesize($path);
		}

		jimport('joomla.filesystem.folder');
		if (!JFolder::exists($path) || !(@opendir($path)))
		{
			return 0;
		}

		$size = 0;
		foreach (JFolder::files($path) as $file)
		{
			$size += @filesize($path . '/' . $file);
		}

		foreach (JFolder::folders($path) as $folder)
		{
			if (!@opendir($path . '/' . $folder))
			{
				continue;
			}

			$size += $this->getFolderSize($path . '/' . $folder);
		}

		return $size;
	}

	public function getSize()
	{
		if ($this->helpers->getParams()->size >= 1048576)
		{
			// Return in MBs
			return (round($this->helpers->getParams()->size / 1048576 * 100) / 100) . 'MB';
		}

		// Return in KBs
		return (round($this->helpers->getParams()->size / 1024 * 100) / 100) . 'KB';
	}

	public function setMessage($message = '')
	{
		$this->helpers->getParams()->message = $message;
	}

	public function setError($error = true)
	{
		$this->helpers->getParams()->error = $error;
	}

}
