<?php
/**
 * Tinyup my files
 *
 * Hier die Beschreibung
 *
 * @author      Guido De Gobbis <support@joomtools.de>
 * @copyright   Copyright since 2018 by JoomTools. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 *
 * @version     1.0.0
 */

const _JEXEC = 1;

$startTime = microtime(1);
$startMem  = memory_get_usage();

set_time_limit(0);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(getcwd()) . '/defines.php'))
{
	require_once dirname(getcwd()) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(getcwd()));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// Load needed Namespaces
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Profiler\Profiler;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

Profiler::getInstance('Tinyup my files')->setStart($startTime, $startMem)->mark('Total');
echo '<h1>Tinyup my files</h1>';

$input = new Input;

// Load Library language
$language = $input->getWord('lang', '');
$lang     = Language::getInstance($language);

// Try the files_joomla file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_joomla.sys', JPATH_SITE, null, false, false)
// Fallback to the files_joomla file in the default language
|| $lang->load('files_joomla.sys', JPATH_SITE, null, true);

//jimport('joomla.filesystem.file');
//jimport('joomla.filesystem.folder');
\JLoader::import('joomla.filesystem.file');
\JLoader::import('joomla.filesystem.folder');

$all    = $input->getBool('all', false);
$rename = $input->getBool('rename', false);
$delete = $input->getBool('delete', false);

if ($rename === false)
{
	$all = false;

	if ($delete === false)
	{
		$output[] = '<strong>Einer der folgenden Pflicht-Parameter muss im URL-Aufruf angegeben werden:</strong>';
		$output[] = '<br /><strong>rename=1</strong> (alle Dateien URL-Safe umbenennen die in der Datenbank gefunden werden)';
		$output[] = '<br /><strong>delete=1</strong> (alle Dateien, die nicht in der Datenbank verwendet werden, werden in den Ordner \'to_delete\' verschoben, um gelöscht zu werden)';
		$output[] = '<br /><br /><strong>Optional:</strong>';
		$output[] = '<br /><strong>all=1</strong> (alle Dateien URL-Safe umbenennen die nicht als gelöscht verschoben werden - rename muss gesetzt sein)';
		$output[] = '<br /><strong>ext=pdf,png,doc</strong> (Dateiendungen nach denen gesucht werden soll - [default: pdf,png,jpg,jpeg])';
		$output[] = '<br /><strong>folder=images/banner</strong> (Ordner im Joomla Rootverzeichnis, indem rekursiv nach Dateien gesucht werden soll - [default: images])';
		$output[] = '<br /><span style="color: red;"><strong>debug=off</strong> (Wird dieser Parameter gesetzt, wird der Testmodus abgestellt und die Änderungen durchgeführt)</span>';
		$output[] = '<br /><h4>' . Profiler::getInstance('Tinyup my files')->mark('Total') . '</h4>';
		die('<div style="font-size: 125%;">' . implode('', $output) . '</div>');
	}
}

$ext        = explode(',', $input->getString('ext', 'pdf,png,jpg,jpeg'));
$debug      = strtolower($input->getString('debug', ''));
$extensions = '\.' . implode('|\.', $ext);
$folder     = $input->getPath('folder', 'images');
$folder     = JPATH_ROOT . '/' . trim($folder, '\\/');
$files      = JFolder::files($folder, $extensions, true, true);
$arrFiles   = [];
$exists     = [];

echo '<pre>';
echo '<h2>Gefundene Dateien mit der Endung: .' . implode(', .', $ext) . '</h2>';

foreach ($files as $file)
{
	$oldname = basename($file);
	$fileExt = JFile::getExt($oldname);

	if (!in_array($fileExt, $ext))
	{
		continue;
	}

	$urlsafe      = '<span style="color: red;">URL-Safe: </span>';
	$newname      = OutputFilter::stringURLSafe(JFile::stripExt($oldname)) . '.' . $fileExt;
	$source       = ltrim(str_replace(JPATH_ROOT, '', $file), '\\/');
	$relativePath = str_replace($oldname, '', $source);
	$dest         = $relativePath . $newname;

	if ($delete === true)
	{
		if ($source == $dest)
		{
			$urlsafe = '<span style="color: darkgreen;">URL-Safe: </span>';
		}
	}
	else
	{
		if ($source == $dest)
		{
			continue;
		}
	}

	if (file_exists(JPATH_ROOT . '/' . $dest) && $source != $dest)
	{
		$exists[] = array(
			'src'  => $source,
			'dest' => $dest,
		);

		echo $urlsafe . '<span style="color: red;">' . $source . '</span><br />';
		continue;
	}

	$arrFiles[$dest] = array(
		'src'      => $source,
		'dest'     => $dest,
		'delete'   => $delete,
		'rename'   => false,
		'tabellen' => [],
	);

	echo $urlsafe . $source . '<br />';
}

echo '<br /><br />';

if (!empty($exists))
{
	foreach ($exists as $exist)
	{
		$output['exist'][] = 'Die Datei <strong>' . $exist['src'] . '</strong> wurde nicht verarbeitet.';
		$output['exist'][] = ' Die Zieldatei <strong>' . $exist['dest'] . '</strong> ist bereits vorhanden, bitte Prüfen.<br /><br />';
	}
}

if (!empty($output['exist']))
{
	echo '<h2 style="color: red;">Achtung!</h2>';
	echo '<div style="color: red;">' . implode('', $output['exist']) . '</div>';
	echo '<br /><br /><br />';
}

if (empty($arrFiles))
{
	die('Keine Dateien zum Verarbeiten gefunden.');
}

echo '<h3>Starte Suche in Datenbank....</h3>';

$db           = Factory::getDbo();
$arrTables    = $db->getTableList();
$tableQueries = [];

foreach ($arrTables as $strTable)
{
	$tblColumns = $db->getTableColumns($strTable);
	$columns    = [];

	foreach ($tblColumns as $key => $type)
	{
		if (in_array($type, array('varchar', 'text', 'mediumtext', 'longtext', 'tinytext')))
		{
			$columns[] = $key;
		}
	}

	if (empty($columns))
	{
		continue;
	}

	$query = $db->getQuery(true);
	$query->select('*')
		->from($db->qn($strTable));
	$db->setQuery($query);
	$stmt = $db->loadAssocList();

	foreach ($stmt as $row)
	{
		foreach ($row as $k => $v)
		{
			if (!in_array($k, $columns))
			{
				continue;
			}

			$blnSerialized = false;
			$blnJson       = false;
			$tmp           = @unserialize($v);

			if ($tmp !== false)
			{
				$blnSerialized = true;
				$v             = $tmp;
			}

			if ($blnSerialized === false)
			{
				$tmp = @json_decode($v);

				if ($tmp !== null)
				{
					$blnJson = true;
					$v       = $tmp;
				}
			}

			$w = false;

			foreach ($arrFiles as $filename => $value)
			{
				if (is_object($v))
				{
					$v = get_object_vars($v);
				}

				if (is_array($v) && array_strpos($v, $value['src']) !== false)
				{
					$w = array_str_replace($value['src'], $value['dest'], $v);
				}

				if (!is_array($v) && strpos($v, $value['src']) !== false)
				{
					$w = str_replace($value['src'], $value['dest'], $v);
				}

				if ($w !== false)
				{
					$v = $w;

					if ($delete === true)
					{
						$arrFiles[$filename]['delete'] = false;
					}

					$arrFiles[$filename]['tabellen'][] = $strTable;
					$arrFiles[$filename]['tabellen']   = ArrayHelper::arrayUnique($arrFiles[$filename]['tabellen']);
				}

				if (($rename === true && $value['src'] != $value['dest'] && $w !== false)
					|| ($w === false && $all === true))
				{
					if ($arrFiles[$filename]['delete'] === false)
					{
						$arrFiles[$filename]['rename'] = true;
					}
				}
			}

			if ($w === false)
			{
				continue;
			}

			if ($blnJson)
			{
				$v = $row[$k];
				$w = json_encode($w);
			}

			if ($blnSerialized)
			{
				$v = $row[$k];
				$w = serialize($w);
			}

			if ($rename === true)
			{
				$query = $db->getQuery(true);

				$query->update($db->qn($strTable))
					->set($db->qn($k) . '=' . $db->q($w))
					->where($db->qn($k) . '=' . $db->q($v));

				$tableQueries[$strTable][] = htmlspecialchars((string) $query);

				if ($debug === 'off')
				{
					$db->setQuery($query)->execute();
				}
			}
			echo '<br />';
		}
	}
}

$output = [];

foreach ($arrFiles as $file)
{
	$delPath    = '';
	$sourceFile = $file['src'];
	$destFile   = $file['dest'];

	if ($file['delete'] === true)
	{
		$delPath    = 'to_delete/';
		$sourceFile = $file['src'];
		$destFile   = $file['src'];

		$output['delete'][] = 'Datei <strong>' . $sourceFile . '</strong> wurde nicht in der Datenbank gefunden und wird zur Löschung verschoben nach <strong>' . $delPath . $destFile . '</strong>.<br /><br />';
	}

	if ($file['rename'] === true)
	{
		$output['rename'][] = 'Die Datei <strong>' . $sourceFile . '</strong> wurde';

		if (empty($file['tabellen']))
		{
			$output['rename'][] = ' zwar in keiner Tabelle gefunden, aber trotzdem für die Zukunft';
		}
		else
		{
			foreach ($file['tabellen'] as $tabelle)
			{
				if (!empty($tableQueries[$tabelle]))
				{
					$output['rename'][] = ' in der Tabelle <strong>' . $tabelle . '</strong> gefunden und mit dem SQL-Query<br />';
					$output['rename'][] = implode('<br />', $tableQueries[$tabelle]) . '<br /><br />';
					$output['rename'][] = 'ausgetauscht und';
				}
			}
		}

		$output['rename'][] = ' in <strong>' . $delPath . $destFile . '</strong> umbenannt.<br /><br /><br />';
	}

	if ($debug === 'off')
	{
		// If the destination directory doesn't exist we need to create it
		if (!file_exists(dirname(JPATH_ROOT . '/' . $delPath . $destFile)))
		{
			$folderObject = new JFilesystemWrapperFolder;

			$folderObject->create(dirname(JPATH_ROOT . '/' . $delPath . $destFile));
		}

		JFile::move(JPATH_ROOT . '/' . $sourceFile, JPATH_ROOT . '/' . $delPath . $destFile);
	}
}

if (!empty($output['rename']))
{
	echo '<h2 style="color: darkgreen;">Bearbeitete Dateien</h2>';
	echo '<div style="color: darkgreen;">' . implode('', $output['rename']) . '</div>';
	echo '<br /><br />';
}

if (!empty($output['delete']))
{
	echo '<h2 style="color: orange;">Zur Löschung vorgeschlagen</h2>';
	echo '<div style="color: orange;">' . implode('', $output['delete']) . '</div>';
	echo '<br /><br />';
}

echo '<br />';
echo '<h4>' . Profiler::getInstance('Tinyup my files')->mark('Total') . '</h4>';

if ($debug !== 'off')
{
	echo '<br /><br /><br />';
	echo '<h1>Debug Modus aktiv, nix passiert :-)</h1>';
	echo '<br /><br /><br />';
}

echo '</pre>';

function array_strpos($arrHaystack, $strNeedle)
{
	foreach ($arrHaystack as $v)
	{
		if (is_object($v))
		{
			$v = get_object_vars($v);
		}
		if (is_array($v) && array_strpos($v, $strNeedle) || !is_array($v) && strpos($v, $strNeedle) !== false)
		{
			return true;
		}
	}

	return false;
}

function array_str_replace($strSearch, $strReplace, $arrData)
{
	foreach ($arrData as $k => $v)
	{
		if (is_array($v))
		{
			$arrData[$k] = array_str_replace($strSearch, $strReplace, $v);
		}
		elseif (is_string($v))
		{
			$arrData[$k] = str_replace($strSearch, $strReplace, $v);
		}
	}

	return $arrData;
}

