<?php
/**
 * Tidyup my files
 *
 * Dieses Script soll helfen Dateien URL-Safe umzubenennen und bei
 * Verwendung in der Datenbank auch diese Einträge anzupassen.
 * Genaueres zur Anwendung und über die Parameter zur Steuerung kann
 * durch Aufrufen des Scripts über einen Internetbrowser erfahren werden.
 *
 * @author      Guido De Gobbis <support@joomtools.de>
 * @copyright   Copyright since 2018 by JoomTools. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

/**
 * Version
 */
const _VERSION = '1.0.10';

/**
 * Konstante für die Ausführung von Joomla
 */
const _JEXEC = 1;

/**
 * Tabellennamen ohne Prefix bei denen nur
 * nach dem Dateinamen gesucht werden soll
 */
const _ONLY_FILENAMES = array(
	'rsgallery2_files',
);

/**
 * Tabellennamen ohne Prefix die von der
 * Suche ausgeschlossen werden können
 */
const _EXCLUDE_TABLES = array(
	'advancedmodules',
	'ak_profiles',
	'ak_stats',
	'ak_storage',
	'akeeba_common',
	'assets',
	'associations',
//	'banner_clients',
//	'banner_tracks',
//	'banners',
//	'categories',
//	'contact_details',
//	'content',
	'content_frontpage',
	'content_rating',
//	'content_types',
	'contentitem_tag_map',
	'core_log_searches',
//	'extensions',
	'fields',
//	'fields_categories',
//	'fields_groups',
//	'fields_values',
//	'finder_filters',
//	'finder_links',
	'finder_links_terms0',
	'finder_links_terms1',
	'finder_links_terms2',
	'finder_links_terms3',
	'finder_links_terms4',
	'finder_links_terms5',
	'finder_links_terms6',
	'finder_links_terms7',
	'finder_links_terms8',
	'finder_links_terms9',
	'finder_links_termsa',
	'finder_links_termsb',
	'finder_links_termsc',
	'finder_links_termsd',
	'finder_links_termse',
	'finder_links_termsf',
	'finder_taxonomy',
	'finder_taxonomy_map',
	'finder_terms',
	'finder_terms_common',
	'finder_tokens',
	'finder_tokens_aggregate',
	'finder_types',
	'jevents_catmap',
	'jevents_exception',
	'jevents_filtermap',
	'jevents_repetition',
	'jevents_rrule',
	'jev_users',
	'languages',
//	'menu',
	'menu_types',
//	'messages',
	'messages_cfg',
//	'modules',
	'modules_menu',
//	'newsfeeds',
//	'overrider',
	'patchtester_pulls',
	'patchtester_tests',
	'phocamaps_icon',
	'phocamaps_map',
//	'postinstall_messages',
//	'redirect_links',
	'rsgallery2_config',
	'schemas',
	'session',
//	'tags',
//	'template_styles',
	'ucm_base',
//	'ucm_content',
//	'ucm_history',
	'update_sites',
	'update_sites_extensions',
	'updates',
	'user_keys',
	'user_notes',
	'user_profiles',
	'user_usergroup_map',
	'usergroups',
	'users',
	'utf8_conversion',
	'viewlevels',
//	'wf_profiles',
	'zoo_category_item',
	'zoo_rating',
	'zoo_search_index',
	'zoo_tag',
	'zoo_version',
	'zoo_zoofilter_searches',
);

// Startzeit und Speichernutzung für Auswertung
$startTime = microtime(1);
$startMem  = memory_get_usage();

@set_time_limit(0);
@ini_set('max_execution_time', 0);
@error_reporting(E_ERROR | E_WARNING | E_PARSE & ~E_NOTICE);
@ini_set('display_errors', 1);
@ini_set('track_errors', 1);

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
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Transliterate;
use Joomla\CMS\Profiler\Profiler;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Input\Input;
use Joomla\Utilities\ArrayHelper;

Profiler::getInstance('Tidyup my files')->setStart($startTime, $startMem);

echo '<h1>Tidyup my files (Version ' . _VERSION . ')</h1>';

$input = new Input;

// Load Library language
$language = $input->getWord('lang', '');
$lang     = Language::getInstance($language);

// Try the files_joomla file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_joomla.sys', JPATH_SITE, null, false, false)
// Fallback to the files_joomla file in the default language
|| $lang->load('files_joomla.sys', JPATH_SITE, null, true);

$all    = $input->getBool('all', false);
$path   = $input->getBool('path', false);
$rename = $input->getBool('rename', false);
$delete = $input->getBool('delete', false);

if ($rename === false)
{
	$all  = false;
	$path = false;

	if ($delete === false)
	{
		$output[] = '<strong>Einer der folgenden Pflicht-Parameter muss im URL-Aufruf angegeben werden:</strong>';
		$output[] = '<br /><strong>rename=1</strong> (alle Dateien URL-Safe umbenennen die in der Datenbank gefunden werden)';
		$output[] = '<br /><strong>delete=1</strong> (alle Dateien, die nicht in der Datenbank verwendet werden, werden in den Ordner \'to_delete\' verschoben, um gelöscht zu werden)';
		$output[] = '<br /><br /><strong>Optional:</strong>';
		$output[] = '<br /><strong>path=1</strong> (alle Pfade URL-Safe umbenennen die nicht als gelöscht verschoben werden - rename muss gesetzt sein)';
		$output[] = '<br /><strong>all=1</strong> (alle Dateien URL-Safe umbenennen die nicht als gelöscht verschoben werden - rename muss gesetzt sein)';
		$output[] = '<br /><strong>folder=images/banner</strong> (Ordner im Joomla Rootverzeichnis, indem rekursiv nach Dateien gesucht werden soll - [default: images])';
		$output[] = '<br /><strong>ext=pdf,png,doc</strong> (Dateiendungen nach denen gesucht werden soll - [default: pdf,png,jpg,jpeg])';
		$output[] = '<br /><strong>exclude=tmp.png,thumb,thumbnails</strong> (Datei- oder Ordnernamen die von der Verarbeitung ausgeschlossen werden sollen)';
		$output[] = '<br /><span style="color: orange;"><strong>excludeRegex=tmp,thumb,thumbnails</strong> (Bestimmte Schlagworte in Datei- oder Ordnernamen die von der Verarbeitung ausgeschlossen werden sollen)</span>';
		$output[] = '<br /><span style="color: red;"><strong>debug=off</strong> (Wird dieser Parameter gesetzt, wird der Testmodus abgestellt und die Änderungen durchgeführt)</span>';
		$output[] = '<br /><h4>' . Profiler::getInstance('Tidyup my files')->mark('Total') . '</h4>';
		die('<div style="font-size: 125%;">' . implode('', $output) . '</div>');
	}
}

$excludefilterBase  = array('^\..*');
$excludefilterParam = explode(',', $input->getCmd('excludeRegex', ''));
$excludefilter      = array_filter(array_merge($excludefilterBase, $excludefilterParam));
$excludeBase        = array('.svn', '.git', '.gitignore', 'CVS', '.DS_Store', '__MACOSX');
$excludeParam       = explode(',', $input->getString('exclude', ''));
$exclude            = array_filter(array_merge($excludeBase, $excludeParam));
$extLower           = explode(',', strtolower($input->getString('ext', 'pdf,png,jpg,jpeg')));
$extUpper           = explode(',', strtoupper($input->getString('ext', 'pdf,png,jpg,jpeg')));
$ext                = array_merge($extLower, $extUpper);
$debug              = strtolower($input->getString('debug', ''));
$extensions         = '\.' . implode('|\.', $ext);
$folder             = str_replace('\\', '/', $input->getPath('folder', 'images'));
$folder             = JPATH_ROOT . '/' . trim($folder, '\\/');
$files              = Folder::files($folder, $extensions, true, true, $exclude, $excludefilter);
$arrFiles           = [];
$exists             = [];

if (!is_dir($folder))
{
	die('<h4>Der Ordnerpfad ' . $input->getPath('folder') . 'existiert nicht</h4>');
}

echo '<pre>';
echo '<h2>Verwendete Parameter</h2>';

if ($rename === true)
{
	echo '- rename=1<br />';
}

if ($all === true)
{
	if ($delete === true)
	{
		echo '<span style="color:#999">- all=1</span><br />';
	}
	else
	{
		echo '- all=1<br />';
	}
}

if ($path === true)
{
	if ($delete === true)
	{
		echo '<span style="color:#999">- path=1</span><br />';
	}
	else
	{
		echo '- path=1<br />';
	}
}

if ($delete === true)
{
	echo '- delete=1<br />';
}

if (!empty($input->getString('folder')))
{
	echo '- folder=' . $input->getPath('folder') . '<br />';
}

if (!empty($input->getString('ext')))
{
	echo '- ext=' . $input->getString('ext') . '<br />';
}

if (!empty($input->getString('exclude')))
{
	echo '- exclude=' . $input->getString('exclude') . '<br />';
}

if (!empty($input->getCmd('excludeRegex')))
{
	echo '- exclude=' . $input->getCmd('excludeRegex') . '<br />';
}

echo '<br /><br />';

ob_flush();
flush();

echo '<h2>Gefundene Dateien mit der Endung: .' . implode(', .', $ext) . '</h2>';

foreach ($files as $file)
{
	$fileParts = pathinfo($file);

	if (!empty($fileParts['extension']) && !in_array($fileParts['extension'], $ext) || empty($fileParts['filename']))
	{
		continue;
	}

	$urlSafe          = '<span style="color: red;">URL-Safe: </span>';
	$newName          = stringMakeSafe($fileParts['filename']) . '.' . strtolower($fileParts['extension']);
	$source           = ltrim(str_replace(JPATH_ROOT, '', $file), '\\/');
	$source           = str_replace('\\', '/', $source);
	$relativePath     = str_replace($fileParts['basename'], '', $source);
	$relativePathSafe = pathMakeSafe($relativePath);

	$destination = str_replace('\\', '/', $relativePath) . $newName;

	if ($delete === true)
	{
		if ($source == $destination)
		{
			$urlSafe = '<span style="color: darkgreen;">URL-Safe: </span>';
		}
	}
	else
	{
		if ($path === true)
		{
			$destination = str_replace('\\', '/', $relativePathSafe) . $newName;
		}

		if ($source == $destination)
		{
			continue;
		}
	}

	$arrFiles[$destination] = array(
		'src'      => $source,
		'dest'     => $destination,
		'delete'   => $delete,
		'rename'   => false,
		'exists'   => false,
		'tabellen' => [],
	);

	if (file_exists_cs(JPATH_ROOT . '/' . $destination) && $rename === true)
	{
		$arrFiles[$destination]['exists'] = true;

		$exists[] = array(
			'src'  => $source,
			'dest' => $destination,
		);

		echo $urlSafe . '<span style="color: red;">' . $source . '</span><br />';

		continue;
	}

	echo $urlSafe . $source . ' -> ' . $destination . '<br />';

	ob_flush();
	flush();
}

if (!empty($arrFiles) || !empty($exists))
{
	echo '<br />';
	echo 'Es wurde(n) insgesamt ' . ((int) count($arrFiles) + (int) count($exists)) . ' Datei(en) gefunden.';
	echo '<br /><br />';
}


if (!empty($exists))
{
	foreach ($exists as $exist)
	{
		$output['exist'][] = 'Die Datei <strong>' . $exist['src'] . '</strong> wird nicht verarbeitet.';
		$output['exist'][] = ' Die Zieldatei <strong>' . $exist['dest'] . '</strong> ist bereits vorhanden, oder wird durch eine vorherige Datei schon vorher erzeugt. Bitte prüfen.<br />';
	}

	$output['exist'][] = 'Davon ' . (int) count($exists) . ' Datei(en) nicht verarbeitet.<br /><br />';
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

unset($files, $file);

echo '<br />';
//echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Total') . '</h4>';
echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Dateisuche in ' . $folder) . '</h4>';
echo '<br /><br /><br />';

//die;

echo '<h2>Starte Suche nach Datein in der Datenbank ....</h2>';

ob_flush();
flush();

$db           = Factory::getDbo();
$arrTables    = $db->getTableList();
$tableQueries = [];
$sql          = [];

// file_put_contents(JPATH_ROOT . '/cli/tabellen.txt', implode("\n", $arrTables));
// die;

foreach ($arrTables as $strTable)
{
	$strTableOhnePrefix = str_replace($db->getPrefix(), '', $strTable);

	if (in_array($strTableOhnePrefix, _EXCLUDE_TABLES))
	{
		continue;
	}

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

	echo 'Durchsuche <strong>' . $strTable . '</strong> mit <strong>' . count($stmt) . '</strong> Datensätzen ...<br />';

	ob_flush();
	flush();

	foreach ($stmt as $row)
	{
		foreach ($row as $k => $v)
		{
			if (!in_array($k, $columns) || empty($v))
			{
				continue;
			}

			$blnSerialized = false;
			$blnJson       = false;
			$tmp           = @json_decode($v);

			if ($tmp !== null)
			{
				$blnJson = true;
				$v       = $tmp;
			}

			if ($blnJson === false)
			{
				$tmp = @unserialize($v);

				if ($tmp !== false)
				{
					$blnSerialized = true;
					$v             = $tmp;
				}
			}

			$dbFound = false;

			foreach ($arrFiles as $fileKey => $fileParams)
			{
				if ($fileParams['exists'] === true)
				{
					continue;
				}

				$w        = false;
				$fileSrc  = $fileParams['src'];
				$fileDest = $fileParams['dest'];

				if (in_array($strTableOhnePrefix, _ONLY_FILENAMES))
				{
					$fileSrc  = basename($fileParams['src']);
					$fileDest = basename($fileParams['dest']);
				}

				if (is_object($v))
				{
					$v = get_object_vars($v);
				}
				if (findFileInData($fileSrc, $v) === true)
				{
					$arrFiles[$fileKey]['delete'] = false;

					if ($rename === true && $fileSrc != $fileDest)
					{
						$w = replaceInData($v, $fileSrc, $fileDest);
					}
				}

				if ($w !== false)
				{
					$v                                           = $w;
					$arrFiles[$fileKey]['tabellen'][$strTable][] = $k;
					$arrFiles[$fileKey]['tabellen'][$strTable]   = ArrayHelper::arrayUnique($arrFiles[$fileKey]['tabellen'][$strTable]);
					$arrFiles[$fileKey]['rename']                = true;
					$dbFound                                     = true;
				}

				if ($w === false && $all === true)
				{
					if ($arrFiles[$fileKey]['delete'] === false)
					{
						$arrFiles[$fileKey]['rename'] = true;
					}
				}
			}

			if ($dbFound === false)
			{
				continue;
			}

			$w = $v;

			if ($blnSerialized)
			{
				$v = $row[$k];
				$w = serialize($w);
			}

			if ($blnJson)
			{
				$v = $row[$k];
				$w = json_encode($w);
			}

			if ($rename === true)
			{
				$tableQuery = $db->getQuery(true);

				$tableQuery->update($db->qn($strTable))
					->set($db->qn($k) . '=' . $db->q($w))
					->where($db->qn($k) . '=' . $db->q($v));

				$tableQueries[$strTable][] = htmlspecialchars((string) $tableQuery);
				$sql[]                     = (string) $tableQuery;
			}
		}
	}

	echo '<br /><strong>' . Profiler::getInstance('Tidyup my files')->mark('Datenbanksuche in ' . $strTable . ' und ' . count($stmt) . ' Datensätzen') . '</strong><br /><br />';

	ob_flush();
	flush();
}

echo '<br />';

$output = [];

foreach ($arrFiles as $file)
{
	$sourceFile = $file['src'];
	$destFile   = $file['dest'];

	if ($file['delete'] === true)
	{
		$sourceFile = $file['src'];
		$destFile   = 'to_delete/' . $file['src'];

		$output['delete'][] = 'Datei <strong>' . $sourceFile . '</strong> verschoben nach <strong>' . $destFile . '</strong>.<br />';
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
			if (!empty($file['tabellen']))
			{
				$output['rename'][] = ' in den Tabellen<strong>';

				foreach ($file['tabellen'] as $tblName => $tblColumns)
				{
					$output['rename'][] = ' ' . $tblName . ' (' . implode(', ', $tblColumns) . ')';
				}

				$output['rename'][] = '</strong> gefunden und';
			}
		}

		$output['rename'][] = ' in <strong>' . $destFile . '</strong> umbenannt.<br />';
	}

	if (!empty($tableQueries))
	{
		foreach ($tableQueries as $tableKey => $tableValues)
		{
			if (!empty($tableValues))
			{
				$output['table'][] = '<h4>Für die Tabelle \'' . $tableKey . '\' wurden folgende SQL-Queries ausgeführt:</h4>';
				$output['table'][] = implode('<br />', $tableValues) . '<br />';

				unset($tableQueries[$tableKey]);

				$output['table'][] = '<br /><br /><br />';
			}
		}
	}

	if ($debug === 'off' && ($file['rename'] === true || $file['delete'] === true))
	{
		// If the destination directory doesn't exist we need to create it
		if (!file_exists(dirname(JPATH_ROOT . '/' . $destFile)) && $file['delete'] === true)
		{
			Folder::create(dirname(JPATH_ROOT . '/' . $destFile));
		}

		File::move(JPATH_ROOT . '/' . $sourceFile, JPATH_ROOT . '/' . $destFile);
	}
}

if ($debug === 'off')
{
	$query = implode(';', $sql);
	$db->setQuery($query)->execute();
}

if ($rename === true)
{
	echo '<h2 style="color: darkgreen;">Umbenannte Dateien</h2>';

	if (!empty($output['rename']))
	{
		echo '<div style="color: darkgreen;">' . implode('', $output['rename']) . '</div>';
	}
	else
	{
		echo '<div style="color: darkgreen;">Es mussten keine Dateien umbenannt werden.</div>';
	}

	echo '<br /><br />';

	echo '<h2 style="color: darkgreen;">Bearbeitete Tabellen</h2>';

	if (!empty($output['table']))
	{
		echo '<div style="color: darkgreen;">' . implode('', $output['table']) . '</div>';
	}
	else
	{
		echo '<div style="color: darkgreen;">Es mussten keine Tabelle bearbeitet werden.</div>';
	}

	echo '<br /><br />';
}

if ($delete === true)
{
	echo '<h2 style="color: orange;">Zur Löschung vorgeschlage Dateien, die nicht in der Datenbank verwendet werden</h2>';

	if (!empty($output['delete']))
	{
		echo '<div style="color: orange;">' . implode('', $output['delete']) . '</div>';
	}
	else
	{
		echo '<div style="color: darkgreen;">Alle Dateien wurden in der Datenbank gefunden.</div>';
	}

	echo '<br /><br />';
}

if ($debug !== 'off')
{
	echo '<h1>Debug Modus aktiv, nix passiert :-)</h1>';
	echo '<br /><br /><br />';
}

echo '<br />';
echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Ende der Verarbeitung') . '</h4>';
echo '<br /><br /><br />';

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

/**
 * @param   string $fileSrc
 * @param   mixed  $data
 *
 * @return   bool
 * @since    1.0.10
 */
function findFileInData($fileSrc, $data)
{
	if (is_array($data))
	{
		foreach ($data as $v)
		{
			if (is_object($v))
			{
				$v = get_object_vars($v);
			}

			if (is_array($v) && findFileInData($fileSrc, $v)
				|| !is_array($v) && strpos($v, $fileSrc) !== false)
			{
				return true;
			}
		}

		return false;
	}

	if (strpos($data, $fileSrc) !== false)
	{
		return true;
	}

	return false;
}

/**
 * @param   mixed  $v
 * @param   string $fileSrc
 * @param   string $fileDest
 *
 * @return   mixed
 * @since    1.0.10
 */
function replaceInData($v, $fileSrc, $fileDest)
{
	if (is_array($v))
	{
		$w = array_str_replace($fileSrc, $fileDest, $v);
	}
	else
	{
		$w = str_replace($fileSrc, $fileDest, $v);
	}

	return $w;
}

/**
 * @param   string $strSearch
 * @param   string $strReplace
 * @param   array  $arrData
 *
 * @return   array
 * @since    1.0.10
 */
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

/**
 * @param   string $file
 *
 * @return   bool
 * @since    1.0.10
 */
function file_exists_cs($file)
{
	if (!file_exists($file))
	{
		return false;
	}

	if (strcmp(basename(realpath($file)), basename($file)) == 0)
	{
		return true;
	}

	return false;
}

/**
 * @param   string $string
 *
 * @return   string
 * @since    1.0.10
 */
function stringMakeSafe($string)
{
	$string = str_replace(' ', '_', $string);
	$string = Transliterate::utf8_latin_to_ascii($string);

	return File::makeSafe($string);
}

/**
 * @param   string $path
 *
 * @return   string
 * @since    1.0.10
 */
function pathMakeSafe($path)
{
	$path  = str_replace(' ', '_', $path);
	$path  = Transliterate::utf8_latin_to_ascii($path);
	$regex = array('#[^A-Za-z0-9_\\\/\(\)\[\]\{\}\#\$\^\+\.\'~`!@&=;,-]#');

	return preg_replace($regex, '', $path);
}
