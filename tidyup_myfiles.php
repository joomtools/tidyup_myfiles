<?php
/**
 * Tidyup my files
 *
 * Hier die Beschreibung
 *
 * @author      Guido De Gobbis <support@joomtools.de>
 * @copyright   Copyright since 2018 by JoomTools. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

const _VERSION        = '1.0.10';
const _JEXEC          = 1;
const _ONLY_FILENAMES = array(
	'_rsgallery2_files',
);
const _EXCLUDE_TABLES = array(
	'_advancedmodules',
	'_ak_profiles',
	'_ak_stats',
	'_ak_storage',
	'_akeeba_common',
	'_assets',
	'_associations',
//	'_banner_clients',
//	'_banner_tracks',
//	'_banners',
//	'_categories',
//	'_contact_details',
//	'_content',
	'_content_frontpage',
	'_content_rating',
//	'_content_types',
	'_contentitem_tag_map',
	'_core_log_searches',
//	'_extensions',
	'_fields',
//	'_fields_categories',
//	'_fields_groups',
//	'_fields_values',
//	'_finder_filters',
//	'_finder_links',
	'_finder_links_terms0',
	'_finder_links_terms1',
	'_finder_links_terms2',
	'_finder_links_terms3',
	'_finder_links_terms4',
	'_finder_links_terms5',
	'_finder_links_terms6',
	'_finder_links_terms7',
	'_finder_links_terms8',
	'_finder_links_terms9',
	'_finder_links_termsa',
	'_finder_links_termsb',
	'_finder_links_termsc',
	'_finder_links_termsd',
	'_finder_links_termse',
	'_finder_links_termsf',
	'_finder_taxonomy',
	'_finder_taxonomy_map',
	'_finder_terms',
	'_finder_terms_common',
	'_finder_tokens',
	'_finder_tokens_aggregate',
	'_finder_types',
	'_jevents_catmap',
	'_jevents_exception',
	'_jevents_filtermap',
	'_jevents_repetition',
	'_jevents_rrule',
	'_jev_users',
	'_languages',
//	'_menu',
	'_menu_types',
//	'_messages',
	'_messages_cfg',
//	'_modules',
	'_modules_menu',
//	'_newsfeeds',
//	'_overrider',
	'_patchtester_pulls',
	'_patchtester_tests',
	'_phocamaps_icon',
	'_phocamaps_map',
//	'_postinstall_messages',
//	'_redirect_links',
	'_rsgallery2_config',
	'_schemas',
	'_session',
//	'_tags',
//	'_template_styles',
	'_ucm_base',
//	'_ucm_content',
//	'_ucm_history',
	'_update_sites',
	'_update_sites_extensions',
	'_updates',
	'_user_keys',
	'_user_notes',
	'_user_profiles',
	'_user_usergroup_map',
	'_usergroups',
	'_users',
	'_utf8_conversion',
	'_viewlevels',
//	'_wf_profiles',
	'_zoo_category_item',
	'_zoo_rating',
	'_zoo_search_index',
	'_zoo_tag',
	'_zoo_version',
	'_zoo_zoofilter_searches',
);

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
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Profiler\Profiler;
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
$files              = JFolder::files($folder, $extensions, true, true, $exclude, $excludefilter);
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
	$oldname = basename($file);
	$fileExt = JFile::getExt($oldname);

	if (!in_array($fileExt, $ext))
	{
		continue;
	}

	$urlsafe      = '<span style="color: red;">URL-Safe: </span>';
	$newname      = OutputFilter::stringURLSafe(JFile::stripExt($oldname)) . '.' . strtolower($fileExt);
	$source       = ltrim(str_replace(JPATH_ROOT, '', $file), '\\/');
	$source       = str_replace('\\', '/', $source);
	$relativePath = str_replace($oldname, '', $source);
	$dest         = str_replace('\\', '/', $relativePath) . $newname;

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

	$arrFiles[$dest] = array(
		'src'      => $source,
		'dest'     => $dest,
		'delete'   => $delete,
		'rename'   => false,
		'exists'   => false,
		'tabellen' => [],
	);

	if (file_exists_cs(JPATH_ROOT . '/' . $dest) && $rename === true)
	{
		$arrFiles[$dest]['exists'] = true;

		$exists[] = array(
			'src'  => $source,
			'dest' => $dest,
		);

		echo $urlsafe . '<span style="color: red;">' . $source . '</span><br />';

		continue;
	}

	echo $urlsafe . $source . '<br />';

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

// die;
unset($files, $file);

echo '<br />';
//echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Total') . '</h4>';
echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Dateisuche in ' . $folder) . '</h4>';
echo '<br /><br /><br />';

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
	if (in_array(str_replace($db->getPrefix(), '_', $strTable), _EXCLUDE_TABLES))
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

				if (in_array(str_replace($db->getPrefix(), '_', $strTable), _ONLY_FILENAMES))
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
			$folderObject = new JFilesystemWrapperFolder;

			$folderObject->create(dirname(JPATH_ROOT . '/' . $destFile));
		}

		JFile::move(JPATH_ROOT . '/' . $sourceFile, JPATH_ROOT . '/' . $destFile);
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
 * @param   mixed   $v
 * @param   string  $fileSrc
 * @param   string  $fileDest
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
