<?php
/**
 * Tidyup my files
 *
 * Dieses Projekt ist entstanden, um z.B. Joomla-Administratoren einer Redaktionsseite,
 * die keinen Zugriff via SSH auf die Konsole des Host haben und auch sonst nicht genug
 * Erfahrung im Umgang mit Datenbanksystemen haben, die Arbeit zu erleichtern.
 *
 * Es soll sie dabei unterstützen eine Massenumbenennung von Dateien und Verzeichnissen,
 * samt Anpassung der Datenbank, in ein URL-Konformes Format vorzunehmen.
 * Es berücksichtigt auch Werte, die in der Datenbank mit `json_encode()` und `serialize()`
 * gespeichert wurden.
 *
 * @thanks      Ein besonderer Danke geht an die Tester
 *              Elisa Foltyn, Christiane Maier-Stadtherr und Thomas Finner,
 *              die viel Geduld und Nerven gezeigt haben.
 *
 * @author      Guido De Gobbis
 * @copyright   Copyright since 2018 by JoomTools. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

/**
 * Version
 */
const _VERSION = '1.0.19-rc1';

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


/**
 * Basispfad des Skriptes
 */
define ('SCRIPT_BASE', getcwd());

// Startzeit und Speichernutzung für Auswertung
$startTime = microtime(1);
$startMem  = memory_get_usage();

@set_time_limit(0);
@ini_set('max_execution_time', 0);
@error_reporting(E_ERROR | E_WARNING | E_PARSE & ~E_NOTICE);
@ini_set('display_errors', 1);
@ini_set('track_errors', 1);

// Load system defines
if (file_exists(dirname(SCRIPT_BASE) . '/defines.php'))
{
	require_once dirname(SCRIPT_BASE) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(SCRIPT_BASE));
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
use Joomla\CMS\Http\HttpFactory;

Profiler::getInstance('Tidyup my files')->setStart($startTime, $startMem); ?>
<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<title>Tidyup my files (Version <?php echo _VERSION; ?>)</title>
	<style>
		body {
			font-size: 120%;
			line-height: 1.3;
		}

		pre {
			white-space: normal;
		}

		code {
			padding: 0.2em 0.4em;
			margin: 0;
			font-size: 85%;
			background-color: rgba(27, 31, 35, 0.1);
			border-radius: 3px;
		}

		em {
			font-size: 95%;
		}

		small {
			font-size: 75%;
		}
	</style>
</head>
<body>
<pre>
<h1>Tidyup my files (Version <?php echo _VERSION; ?>)</h1>
<?php
$input = new Input;

// Load Library language
$language = $input->getWord('lang', '');
$lang     = Language::getInstance($language);

// Try the files_joomla file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_joomla.sys', JPATH_SITE, null, false, false)
// Fallback to the files_joomla file in the default language
|| $lang->load('files_joomla.sys', JPATH_SITE, null, true);

$all       = $input->getBool('all', false);
$seo       = $input->getBool('seo', false);
$path      = $input->getBool('path', false);
$subfolder = $input->getBool('subfolder', false);
$rename    = $input->getBool('rename', false);
$delete    = $input->getBool('delete', false);
$update    = $input->getBool('update', false);
$output    = [];

// Auf aktualisierungen prüfen
update($update);

ob_flush();
flush();

if ($rename === false)
{
	$all  = false;
	$path = false;

	if ($delete === false)
	{ ?>
	<p>Dieses Projekt ist entstanden, um z.B. Joomla-Administratoren einer Redaktionsseite, die keinen Zugriff auf die Konsole des Host haben und auch sonst nicht genug Erfahrung mit Datenbanksystemen haben, die Arbeit zu erleichtern.</p>
	<p>Es soll sie dabei unterstützen eine Massenumbenennung von Dateien und Verzeichnissen, samt Anpassung der Datenbank, in ein URL-Konformes Format vorzunehmen. Es berücksichtigt auch Werte, die in der Datenbank mit <code>json_encode()</code> und <code>serialize()</code> gespeichert wurden.</p>
	<p>Zur Verwendung das Verzeichnis <code>tidyup_myfiles</code> in das Joomla Rootverzeichnis kopieren.</p>
	<h3>Aufruf</h3>
	<p><code>https://example.org/tidyup_myfiles/exec.php?rename=1[&amp;all=1][&amp;ext=jpg,jpeg][&amp;folder=images/UNTERORDNER][&amp;debug=off]</code></p>
	<h3>Pflichtparameter:</h3>
	<ul>
		<li>
			<p><code>rename=1</code> Alle Dateien URL-Safe umbenennen die in der Datenbank verwendet werden - <strong>[default: 0]</strong></p>
		</li>
		<li>
			<p><code>delete=1</code> Alle Dateien, die nicht in der Datenbank verwendet werden, werden in den Ordner <code>to_delete</code> verschoben, um gelöscht zu werden - <strong>[default: 0]</strong></p>
		</li>
	</ul>
	<p style="color: red;"><em><strong>ACHTUNG:</strong> <br>
		Bei der Verwendung von <code>delete=1</code> wird dringend empfohlen die Suchergebnisse einzugrenzen, da jede gefundene Datei in jedem Datensatz der Datenbank gesucht wird! Bei zu vielen Dateien kann es sonst zum frühzeitigen Abbruch durch serverseitige Begrenzungen kommen.</em></p>
	<p><em>Die Parameter <code>delete=1</code> und <code>rename=1</code> können unabhängig von einander oder gemeinsam verwendet werden, aber einer der beiden muss angegeben sein.</em></p>
	<h3>Zusatzparameter</h3>
	<ul>
		<li>
			<p><code>path=1</code> Alle Pfade URL-Safe umbenennen, die nicht als gelöscht gekennzeichnet werden - <strong>[default: 0]</strong><br>
				<code>rename=1</code> muss verwendet werden.<br>
				<em>Ist dieser Wert nicht gesetzt, wird nur nach den Dateinamen in der Datenbank gesucht und umbenannt.</em></p>
		</li>
		<li>
			<p><code>all=1</code> Alle Dateien URL-Konform umbenennen - <strong>[default: 0]</strong><br>
				<code>rename=1</code> muss verwendet werden.<br>
				<em>Wird ignoriert, wenn <code>delete=1</code> eingesetzt wird.</em></p>
		</li>
		<li>
			<p><code>seo=1</code> Alle Dateien URL-Konform <strong>und</strong> SEO-Konform umbenennen - <strong>[default: 0]</strong><br>
				<code>rename=1</code> muss verwendet werden.<br>
				<em>Statt Unterstriche <code>_</code> und <code>CameCase</code> zu erlauben, wird alles kleingeschrieben und <code>_</code> in <code>-</code> umgewandelt.<br>
					Wandelt auch die Pfade um, wenn <code>path=1</code> verwendet wird.</em></p>
		</li>
		<li>
			<p><code>folder=images/banner</code> Ordner im Joomla Rootverzeichnis, indem nach Dateien gesucht werden soll - <strong>[default: images]</strong></p>
		</li>
		<li>
			<p><code>subfolder=1</code> Alle Unterordner rekursiv nach Dateien durchsuchen - <strong>[default: 0]</strong></p>
		</li>
		<li>
			<p><code>ext=pdf,png,doc</code> Dateiendungen nach denen gesucht werden soll (Werte durch Komma <code>,</code> getrennt) - **[default: pdf,png,jpg,jpeg]<br>
				<em>Jede angegebene Endung wird automatisch auch in Großbuchstaben gesucht.</em></p>
		</li>
		<li>
			<p><code>exclude=tmp.png,thumb,thumbnails</code> Datei- oder Ordnernamen die von der Suche ausgeschlossen werden sollen (Werte durch Komma <code>,</code> getrennt)</p>
		</li>
		<li>
			<p style="color: orange;"><code>excludeRegex=tmp,thumb,thumbnails</code> Bestimmte Schlagworte in Datei- oder Ordnernamen die von der Suche ausgeschlossen werden sollen (Werte durch Komma <code>,</code> getrennt)</p>
		</li>
		<li>
			<p style="color: red;"><code>debug=off</code> Wird dieser Parameter gesetzt, wird der Testmodus abgestellt und die Änderungen durchgeführt<br>
				<em>Solange der Parameter debug=off nicht verwendet wird, ist es nur eine Simulation, es kann also nichts passieren.</em></p>
		</li>
	</ul>
	<h3>Beispiele:</h3>
	<ul>
		<li>
			<p><code>https://example.org/tidyup_myfiles/exec.php</code><br>
				Gibt diese Hilfe aus</p>
		</li>
		<li>
			<p><code>https://example.org/tidyup_myfiles/exec.php?rename=1&amp;folder=images/UNTERORDNER</code><br>
				Durchsucht das Verzeichnis <code>images/UNTERORDENR</code> nach Dateien mit der Endung <code>.pdf, .png, .jpg, .jpeg, .PDF, .PNG, .JPG, .JPEG</code> und Prüft sie auf URL-Konformität. Die Endungen werden kelingeschrieben und Leerzeichen durch <code>_</code> ersetzt, sowie Umlaute umgeschrieben. Es wird in der Datenbank nach Vorkommen der zu ändernden Dateien gesucht und ggf. Umbenannt.</p>
		</li>
	</ul>
	<p>Ein besonderer Danke geht an die Tester <em>Elisa Foltyn</em>, <em>Christiane Maier-Stadtherr</em> und <em>Thomas Finnern</em>, die viel Geduld und Nerven gezeigt haben.</p>
	<?php die;
	}
}

$excludefilterBase  = array('^\..*');
$excludefilterParam = explode(',', $input->getCmd('excludeRegex', ''));
$excludefilter      = array_filter(
	array_map('trim', array_merge($excludefilterBase, $excludefilterParam))
);
$excludeBase        = array('.svn', '.git', '.gitignore', 'CVS', '.DS_Store', '__MACOSX');
$excludeParam       = explode(',', $input->getString('exclude', ''));
$exclude            = array_filter(
	array_map('trim', array_merge($excludeBase, $excludeParam))
);
$extLower           = explode(',', strtolower($input->getString('ext', 'pdf,png,jpg,jpeg')));
$extUpper           = explode(',', strtoupper($input->getString('ext', 'pdf,png,jpg,jpeg')));
$ext                = array_filter(
	array_map('trim', array_merge($extLower, $extUpper))
);
$debug              = $input->getString('debug', '');
$extensions         = '\.' . implode('|\.', $ext);
$relativeFolder     = trim(
	str_replace('\\', '/', $input->getPath('folder', 'images')),
	'\\/'
);
$folder             = JPATH_ROOT . '/' . $relativeFolder;

if (!is_dir($folder))
{
	die('<h4>Der Ordnerpfad ' . $relativeFolder . ' existiert nicht</h4>');
}

$files = Folder::files($folder, $extensions, $subfolder, true, $exclude, $excludefilter);

echo '<h2>Verwendete Parameter</h2>';

if ($rename === true)
{
	echo '- rename=1<br />';
}

if ($all === true)
{
	if ($delete === true)
	{
		$all = false;
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

if ($seo === true)
{
	echo '- seo=1<br />';

}

if ($delete === true)
{
	echo '- delete=1<br />';
}

if (!empty($input->getString('folder')))
{
	echo '- folder=' . $input->getPath('folder') . '<br />';
}

if (!empty($input->getBool('subfolder')))
{
	echo '- subfolder=' . $input->getBool('subfolder') . '<br />';
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

if (!empty($input->getString('debug')))
{
	echo '- debug=' . $input->getString('debug') . '<br />';
}

echo '<br /><br />';

if ($subfolder === true)
{
	$recursiv = ' (inkl. Unterverzeichnisse)';
}
else
{
	$recursiv = ' (ohne Unterverzeichnisse)';
}

echo '<h2>Suche Dateien mit der Endung: .' . implode(', .', $ext) . '<br />in ' . $folder . $recursiv . '<h2> ';

$arrFiles = [];
$x        = 0;

foreach ($files as $file)
{
	ob_flush();
	flush();

	$x++;
	$fileParts = pathinfo($file);
	$toExclude = false;
	$toIgnore  = false;

	if (!empty($fileParts['extension']) && !in_array($fileParts['extension'], $ext) || empty($fileParts['filename']))
	{
		continue;
	}

	$newName = stringMakeSafe($fileParts['filename'], $seo) . '.' . strtolower($fileParts['extension']);

	$source = ltrim(str_replace(JPATH_ROOT, '', $file), '\\/');
	$source = str_replace('\\', '/', $source);

	$relativePath     = str_replace($fileParts['basename'], '', $source);
	$relativePathSafe = pathMakeSafe($relativePath, $seo);

	$destination = str_replace('\\', '/', $relativePath) . $newName;

	if ($delete === false && $path === true)
	{
		$destination = str_replace('\\', '/', $relativePathSafe) . $newName;
	}

	$arrFile = array(
		'src'      => $source,
		'dest'     => $destination,
		'delete'   => $delete,
		'rename'   => false,
		'exists'   => false,
		'tabellen' => [],
	);

	if (file_exists_cs(JPATH_ROOT . '/' . $destination) && $rename === true)
	{
		$arrFile['exist'] = true;
	}

	if ($source != $destination)
	{
		if ($arrFile['exist'] === true)
		{
			$output['toExclude'][] = $source . ' -> <span style="color: red;">' . $destination . '</span>';

			if ($delete === true)
			{
				$toIgnore = true;
			}

			if ($delete === false)
			{
				continue;
			}
		}
	}

	if ($source == $destination)
	{
		$output['toIgnore'][] = '<span style="color: darkgreen;">' . $source . '</span>';

		if ($delete === false)
		{
			continue;
		}

		$toIgnore = true;
	}

	$arrFiles[$destination] = $arrFile;

	if ($toIgnore === false)
	{
		$output['toSearch'][] = $source . ' -> <span style="color: darkgreen;">' . $destination . '</span>';
	}

	if ($x % 60 === 0)
	{
		echo '.';
	}
}

echo '<br />';

if ($x === 0)
{
	die('<h3>Keine Dateien gefunden.</h3>');
}

if ($x > 0)
{
	echo '<h3>Es wurde/n insgesamt ' . $x . ' Datei/en gefunden.</h3>';
	echo '<br />';
}

if (!empty($output['toExclude']) && $delete === true)
{
	$output['toIgnore'] = array_merge($output['toIgnore'], $output['toExclude']);
	unset($output['toExclude']);
}

$countIgnore = !empty($output['toIgnore']) ? (int) count($output['toIgnore']) : 0;

if ($countIgnore > 0)
{
	echo '<h3>Davon ';

	if ($delete === true)
	{
		echo 'in der Datenbank zur Löschung gesucht, aber ';
	}

	echo 'nicht umbenannt, weil schon URL-Safe: ' . $countIgnore . ' Datei/en</h3>';
	echo implode('<br />', $output['toIgnore']);
	echo '<br /><br />';
}

ob_flush();
flush();

$countExclude = !empty($output['toExclude']) ? (int) count($output['toExclude']) : 0;

if ($countExclude > 0)
{
	echo '<h3>Davon ausgeschlossen, weil die Zieldatei vorhandenem ist: ' . $countExclude . ' Datei/en</h3>';
	echo implode('<br />', $output['toExclude']);
	echo '<br /><br />';
}

ob_flush();
flush();

$countSearch   = !empty($output['toSearch']) ? (int) count($output['toSearch']) : 0;
$countArrFiles = !empty($arrFiles) ? (int) count($arrFiles) : 0;

if ($countArrFiles > 0 && $countSearch === 0)
{
	echo '<h3>Alle Dateien werden in der Datenbank zur ';

	if ($rename === true)
	{
		echo 'Umbenennung';
	}

	if ($rename === true && $delete === true)
	{
		echo '/';
	}

	if ($delete === true)
	{
		echo 'Löschung';
	}

	echo ' gesucht</h3>';
}
else if ($countArrFiles === 0 && $countSearch === 0)
{
	die('<h3>Keine Dateien zum Verarbeiten übrig.</h3>');
}


if ($countSearch > 0)
{
	echo '<h3>Der Rest (' . $countSearch . ' Datei/en) wird, wie folgt, in der Datenbank zur ';

	if ($rename === true)
	{
		echo 'Umbenennung ';
	}

	if ($rename === true && $delete === true)
	{
		echo '/ ';
	}

	if ($delete === true)
	{
		echo 'Löschung ';
	}

	echo 'gesucht</h3>';
	echo implode('<br />', $output['toSearch']);
}

echo '<h4>' . Profiler::getInstance('Tidyup my files')->mark('Dateisuche in ' . $relativeFolder) . '</h4>';
echo '<br /><br />';

ob_flush();
flush();

unset($files, $file, $arrFile, $fileParts, $output, $newName, $source, $destination, $relativePath, $relativePathSafe);

//die;

echo '<h2>Starte Suche nach Datein in der Datenbank ....</h2>';

ob_flush();
flush();

$db         = Factory::getDbo();
$arrTables  = $db->getTableList();
$tblQueries = [];
$sql        = [];

// file_put_contents(JPATH_ROOT . '/cli/tabellen.txt', implode("\n", $arrTables));
// die;

foreach ($arrTables as $strTable)
{
	$hits                = [];
	$search              = 0;
	$strTblWithoutPrefix = str_replace($db->getPrefix(), '', $strTable);

	if (in_array($strTblWithoutPrefix, _EXCLUDE_TABLES))
	{
		continue;
	}

	$tblColumns        = $db->getTableColumns($strTable);
	$arrAllowedColumns = [];

	foreach ($tblColumns as $column => $type)
	{
		if (in_array($type, array('varchar', 'text', 'mediumtext', 'longtext', 'tinytext')))
		{
			$arrAllowedColumns[] = $column;
		}
	}

	unset($column, $type);

	if (empty($arrAllowedColumns))
	{
		continue;
	}

	$query = $db->getQuery(true);

	$query->select('*')
		->from($db->qn($strTable));
	$db->setQuery($query);
	$tblRows = $db->loadAssocList();

	echo 'Durchsuche <strong>' . $strTable . '</strong> mit <strong>' . count($tblRows) . '</strong> Datensätzen ...';

	ob_flush();
	flush();

	foreach ($tblRows as $tblRow)
	{
		if (++$search % 80 === 0)
		{
			echo '.';

			ob_flush();
			flush();
		}

		foreach ($tblRow as $column => $value)
		{
			if (!in_array($column, $arrAllowedColumns) || empty($value))
			{
				continue;
			}

			$valSerialized = false;
			$valJson       = false;
			$tmp           = @json_decode($value);

			if ($tmp !== null)
			{
				$valJson = true;
				$value   = $tmp;
			}

			if ($valJson === false)
			{
				$tmp = @unserialize($value);

				if ($tmp !== false)
				{
					$valSerialized = true;
					$value         = $tmp;
				}
			}

			$dbChanged = false;

			foreach ($arrFiles as $fileKey => $fileParams)
			{
				$valChanged = false;
				$fileSrc    = $fileParams['src'];
				$fileDest   = $fileParams['dest'];

				if (in_array($strTblWithoutPrefix, _ONLY_FILENAMES) || $path === false)
				{
					$fileSrc  = basename($fileParams['src']);
					$fileDest = basename($fileParams['dest']);
				}

				if (is_object($value))
				{
					$value = get_object_vars($value);
				}

				if (findFileInData($fileSrc, $value) === true)
				{
					$hits[] = $fileSrc;
					$hits   = ArrayHelper::arrayUnique($hits);

					$arrFiles[$fileKey]['delete']                = false;
					$arrFiles[$fileKey]['tabellen'][$strTable][] = $column;
					$arrFiles[$fileKey]['tabellen'][$strTable]   = ArrayHelper::arrayUnique($arrFiles[$fileKey]['tabellen'][$strTable]);

					if ($fileParams['exists'] === false && $rename === true && $fileSrc != $fileDest)
					{
						$valChanged = replaceInData($value, $fileSrc, $fileDest);
					}
				}

				if ($valChanged !== false)
				{
					$value                        = $valChanged;
					$arrFiles[$fileKey]['rename'] = true;
					$dbChanged                    = true;
				}

				if ($fileParams['exists'] === false && $valChanged === false && $all === true)
				{
					if ($arrFiles[$fileKey]['delete'] === false && $fileSrc != $fileDest)
					{
						$arrFiles[$fileKey]['rename'] = true;
					}
				}
			}

			if ($dbChanged === false)
			{
				continue;
			}

			$valChanged = $value;
			$value      = $tblRow[$column];

			if ($valSerialized)
			{
				$valChanged = serialize($valChanged);
			}

			if ($valJson)
			{
				$valChanged = json_encode($valChanged);
			}

			if ($rename === true)
			{
				$tableQuery = $db->getQuery(true);

				$tableQuery->update($db->qn($strTable))
					->set($db->qn($column) . '=' . $db->q($valChanged))
					->where($db->qn($column) . '=' . $db->q($value));

				$tblQueries[$strTable][] = htmlspecialchars((string) $tableQuery);
				$sql[]                   = (string) $tableQuery;
			}
		}
	}

	$countHits  = (int) count($hits);
	$hitsOutput = $countHits > 0 ? '<br />(' . implode(', ', $hits) . ')' : '';

	echo '<br /><strong>' . Profiler::getInstance('Tidyup my files')->mark('Datenbanksuche in ' . $strTable . ' und ' . count($tblRows) . ' Datensätzen') . '</strong> ergab <strong>' . $countHits . ' Treffer</strong>' . $hitsOutput . '<br /><br />';

	ob_flush();
	flush();
}

echo '<br />';

$output = [];
$x      = 0;
foreach ($arrFiles as $file)
{
	if (++$x % 60 === 0)
	{
		echo '.';

		ob_flush();
		flush();
	}

	$sourceFile = $file['src'];
	$destFile   = $file['dest'];

	if ($file['delete'] === true)
	{
		$destFile = 'tidyup_myfiles/to_delete/' . $file['src'];

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
			$output['rename'][] = ' in den Tabellen<strong>';

			foreach ($file['tabellen'] as $tblName => $tblColumns)
			{
				$output['rename'][] = ' ' . $tblName . ' (' . implode(', ', $tblColumns) . ')';
			}

			$output['rename'][] = '</strong> gefunden und';
		}

		$output['rename'][] = ' in <strong>' . $destFile . '</strong> umbenannt.<br />';
	}

	if (!empty($tblQueries))
	{
		foreach ($tblQueries as $tableKey => $tableValues)
		{
			if (!empty($tableValues))
			{
				$output['table'][] = '<h4>Für die Tabelle \'' . $tableKey . '\' wurden folgende SQL-Queries ausgeführt:</h4>';
				$output['table'][] = implode('<br />', $tableValues) . '<br />';

				unset($tblQueries[$tableKey]);

				$output['table'][] = '<br /><br /><br />';
			}
		}
	}

	if ($debug === 'off' && ($file['rename'] === true || $file['delete'] === true))
	{
		// If the destination directory doesn't exist we need to create it
		if (!file_exists_cs(dirname(JPATH_ROOT . '/' . $destFile)))
		{
			Folder::create(dirname(JPATH_ROOT . '/' . $destFile));
		}

		File::move(JPATH_ROOT . '/' . $sourceFile, JPATH_ROOT . '/' . $destFile);
	}
}

if ($debug === 'off' && !empty($sql))
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
?>

<br/>
<h4><?php echo Profiler::getInstance('Tidyup my files')->mark('Ende der Verarbeitung'); ?></h4>
<br/><br/><br/>
</pre>
</body>
<?php

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

	$realFile = str_replace('\\', '/', realpath($file));

	if (strcmp($realFile, $file) == 0)
	{
		return true;
	}

	return false;
}

/**
 * @param   string $string
 * @param   bool   $seo
 *
 * @return   string
 * @since    1.0.10
 */
function stringMakeSafe($string, $seo = false)
{
	if ($seo === true)
	{
		return Joomla\CMS\Filter\OutputFilter::stringURLSafe($string);
	}

	$string = str_replace(' ', '_', $string);
	$string = Transliterate::utf8_latin_to_ascii($string);

	return File::makeSafe($string);
}

/**
 * @param   string $path
 * @param   bool   $seo
 *
 * @return   string
 * @since    1.0.10
 */
function pathMakeSafe($path, $seo = false)
{
	if ($seo === true)
	{
		return pathMakeSeoSafe($path);
	}
	$path  = str_replace(' ', '_', $path);
	$path  = Transliterate::utf8_latin_to_ascii($path);
	$regex = array('#[^A-Za-z0-9_\\\/\(\)\[\]\{\}\#\$\^\+\.\'~`!@&=;,-]#');

	return preg_replace($regex, '', $path);
}

/**
 * @param   string $path
 *
 * @return   string
 * @since    1.0.12
 */
function pathMakeSeoSafe($path)
{
	$path = str_replace('-', ' ', $path);
	$path = Transliterate::utf8_latin_to_ascii($path);
	$path = trim(strtolower($path));
	$path = preg_replace('/(\s|[^\/A-Za-z0-9\-])+/', '-', $path);
	$path = trim($path, '-');

	return $path;
}

/**
 * Suche nach neueren Versionen auf Github
 *
 * @param   bool $action
 *
 * @return   void
 * @sinse    1.0.14
 */
function update($action)
{
	$update     = new stdClass;
	$interval   = 1;
	$date       = date('YmdHi');
	$now        = new DateTime($date);
	$updateFile = str_replace('\\', '/', SCRIPT_BASE . '/update.txt');

	if (file_exists($updateFile))
	{
		if ($action === true)
		{
			@unlink($updateFile);
		}
		else
		{
			$update = json_decode(file_get_contents($updateFile));
		}
	}

	if (!empty($update->date))
	{
		$intervalDate = new DateTime($update->date);
		$interval     = date_diff($now, $intervalDate)->h;
	}


	if ($action === true || $interval > 0)
	{
		@unlink($updateFile);

		$repository = 'https://api.github.com/repos/JoomTools/tidyup_myfiles/git/refs/tags';

		$http = HttpFactory::getHttp();
		$http->setOption('userAgent', 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0');

		$data          = $http->get($repository);
		$latest        = array_pop(json_decode($data->body));
		$latestVersion = str_replace('refs/tags/', '', $latest->ref);

		$update->date    = $date;
		$update->version = $latestVersion;
		$update->message = '';

		if ($data->code === 200)
		{
			if (version_compare(_VERSION, $latestVersion, 'lt'))
			{
				$downloadLink    = 'https://github.com/JoomTools/tidyup_myfiles/archive/' . $latestVersion . '.zip';
				$update->message .= '<p>';
				$update->message .= '<strong style="color: darkgreen">Neue Version gefunden: ' . $latestVersion . '</strong><br />';

				$tag = $http->get($latest->object->url);
				$tag = json_decode($tag->body);

				$message         = str_replace($latestVersion . "\n\n", '', $tag->message);
				$update->message .= '<small>' . nl2br($message) . '</small><br /><br />';
				$update->message .= 'Aktuelle Version herunterladen: <a href="' . $downloadLink . '"><code>' . $latestVersion . '</code></a><br />';
				$update->message .= 'Auf Github anschauen: <a href="https://github.com/JoomTools/tidyup_myfiles"><code>JoomTools/tidyup_myfiles</code></a><br /></p>';
			}
		}
		else if ($data->code !== 403)
		{
			$update->message .= '<small style="color: red">Konnte nicht nach Aktualisierungen suchen, die Verbindung zum Repository <a href="https://github.com/JoomTools/tidyup_myfiles"><code>JoomTools/tidyup_myfiles</code></a> war nicht möglich.</small>';
		}

		file_put_contents($updateFile, json_encode($update));
	}

	if (version_compare(_VERSION, $update->version, 'lt'))
	{
		echo $update->message;
	}
}
