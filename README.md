# Tidyup my files

## Anleitung / Manual
<details>
  <summary>Deutsch/German</summary>

# Deutsche Anleitung

* Beseitige ungenutzten Datenmüll (zum Beispiel ungenutzte Bilder im Images Ordner)
* Benenne alle Dateien webkonform bzw. SEO Konform um

Dieses Projekt ist entstanden, um z.B. Joomla-Administratoren einer Redaktionsseite, die keinen Zugriff auf die Konsole des Host haben und auch sonst nicht genug Erfahrung mit Datenbanksystemen haben, die Arbeit zu erleichtern.
 
 Es soll sie dabei unterstützen eine Massenumbenennung von Dateien und Verzeichnissen, samt Anpassung der Datenbank, in ein URL-Konformes Format vorzunehmen. Es berücksichtigt auch Werte, die in der Datenbank mit `json_encode()` und `serialize()` gespeichert wurden.
 
 ### Achtung
 Dieses Skript berücksichtigt *KEINE* Dateien die dynamisch bezogen werden. 
 Das könnten zum Beispiel sein: 
 - Bildergalerien die aus einem Verzeichnis geladen werden. 
 - "Hart codierte" Bildpfade die in Overrides oder Templatedateien o.ä verwendet werden.
 - Thumbs/Vorschaubilder die automatisch bezogen werden. 
 *Vor der Nutzung muss ein Backup gemacht werden*

Zur Verwendung das Verzeichnis `tidyup_myfiles` in das Joomla Rootverzeichnis kopieren.

### Aufruf

`https://example.org/tidyup_myfiles/exec.php?rename=1[&all=1][&ext=jpg,jpeg][&folder=images/UNTERORDNER][&debug=off]`

### Pflichtparameter:

* `rename=1` Alle Dateien URL-Safe umbenennen die in der Datenbank verwendet werden - **\[default: 0\]**

* `delete=1` Alle Dateien, die nicht in der Datenbank verwendet werden, werden in den Ordner `to_delete` verschoben, um gelöscht zu werden - **\[default: 0\]**


_**ACHTUNG:** <br>
Bei der Verwendung von `delete=1` wird dringend empfohlen die Suchergebnisse einzugrenzen, da jede gefundene Datei in jedem Datensatz der Datenbank gesucht wird! Bei zu vielen Dateien kann es sonst zum frühzeitigen Abbruch durch serverseitige Begrenzungen kommen._


_Die Parameter `delete=1` und `rename=1` können unabhängig von einander oder gemeinsam verwendet werden, aber einer der beiden muss angegeben sein._


### Zusatzparameter
  * `path=1` Alle Pfade URL-Safe umbenennen, die nicht als gelöscht gekennzeichnet werden - **\[default: 0\]**<br>
  `rename=1` muss verwendet werden.<br>
  _Ist dieser Wert nicht gesetzt, wird nur nach den Dateinamen in der Datenbank gesucht und umbenannt._

  * `all=1` Alle Dateien URL-Konform umbenennen - **\[default: 0\]**<br>
  `rename=1` muss verwendet werden.<br>
  _Wird ignoriert, wenn `delete=1` eingesetzt wird._

  * `seo=1` Alle Dateien URL-Konform **und** SEO-Konform umbenennen - **\[default: 0\]**<br>
 `rename=1` muss verwendet werden.<br>
  _Statt Unterstriche `_` und `CameCase` zu erlauben, wird alles kleingeschrieben und `_` in `-` umgewandelt.<br>
  Wandelt auch die Pfade um, wenn `path=1` verwendet wird._

  * `folder=images/banner` Ordner im Joomla Rootverzeichnis, indem nach Dateien gesucht werden soll - **\[default: images\]**

  * `subfolder=1` Alle Unterordner rekursiv nach Dateien durchsuchen - **\[default: 0\]**

  * `ext=pdf,png,doc` Dateiendungen nach denen gesucht werden soll (Werte durch Komma `,` getrennt) - **\[default: pdf,png,jpg,jpeg\]**<br>
  _Jede angegebene Endung wird automatisch auch in Großbuchstaben gesucht._

  * `exclude=tmp.png,thumb,thumbnails` Datei- oder Ordnernamen die von der Suche ausgeschlossen werden sollen (Werte durch Komma `,` getrennt)

  * `excludeRegex=tmp,thumb,thumbnails` Bestimmte Schlagworte in Datei- oder Ordnernamen die von der Suche ausgeschlossen werden sollen (Werte durch Komma `,` getrennt)

  * `debug=off` Wird dieser Parameter gesetzt, wird der Testmodus abgestellt und die Änderungen durchgeführt<br>
  _Solange der Parameter debug=off nicht verwendet wird, ist es nur eine Simulation, es kann also nichts passieren._
  

### Beispiele:

*  
  `https://example.org/tidyup_myfiles/exec.php`<br>
  Gibt diese Hilfe aus

* `https://example.org/tidyup_myfiles/exec.php?rename=1&folder=images/UNTERORDNER`<br>
  Durchsucht das Verzeichnis `images/UNTERORDNER` nach Dateien mit der Endung `.pdf, .png, .jpg, .jpeg, .PDF, .PNG, .JPG, .JPEG` und Prüft sie auf URL-Konformität. Die Endungen werden kleingeschrieben und Leerzeichen durch `_` ersetzt, sowie Umlaute umgeschrieben. Es wird in der Datenbank nach Vorkommen der zu ändernden Dateien gesucht und ggf. Umbenannt. 


Ein besonderer Dank geht an die Tester _Elisa Foltyn_, [_Christiane Maier-Stadtherr_](https://www.chmst.de) und [_Thomas Finnern_](https://github.com/ThomasFinnern), die viel Geduld und Zeit investiert haben. :+1:
</details>

<details>
  <summary>Englisch/English</summary>

# English Manual

* Get rid of unused Data on your Page (for example unused images in the images folder) 
* rename files to be websafe / seo-safe

This project was created to facilitate the work of e.g. Joomla administrators of an editorial page who do not have access to the console of the host and also do not have enough experience with database systems.
 
It should help you to rename files and directories, including their adaptation inside the database, into a URL-compliant format. It also takes into account values stored in the database with `json_encode()` and `serialize()`.
 
 ### Attention
 This script does *not* take files into account that are dynamically pulled on the website. 
 These could be, for example: 
 - Image galleries loaded from a directory. 
 - "Hard-coded" image paths used in overrides or template files or similar.
 - Thumbs that are automatically displayed or whatever
 *Before using the script take a backup first*

For use copy the directory `tidyup_myfiles` into the Joomla root directory.

### Call

`https://example.org/tidyup_myfiles/exec.php?rename=1[&all=1][&ext=jpg,jpeg][&folder=images/SUBFOLDER][&debug=off]`

### Mandatory parameters:

You can use one of those two Parameter or both to call the script

* `rename=1` Rename all files URL-Safe used in the database - **\[default: 0\]**

* `delete=1` All files that are not used in the database are moved to the `to_delete` folder to be deleted - **\[default: 0\]**


_**CAUTION:** <br>
When using `delete=1` it is strongly recommended to narrow down the search results by using the parameter `folder=images/subfolder`, as every file found is searched in every record of the database! Otherwise, too many files may terminate prematurely due to server-side limitations._

_The parameters `delete=1` and `rename=1` can be used independently or together, but one of them must be specified._


### Additional parameters
* `path=1` Rename all paths URL-Safe that are not marked as deleted - **\[default: 0\]**<br>
  `rename=1` must be used in connection to the path parameter<br>
  _If this value is not set, only the file names in the database are searched for and renamed._

* `all=1` rename all files websafe - **\[default: 0\]***<br>
  `rename=1` must be used in connection to the all parameter<br>
  _Will be ignored if `delete=1` is used._

* `seo=1` Rename all files websafe **and** SEO safe - **\[default: 0\]**<br>
 `rename=1` must be used in connection to the seo parameter.<br>
  _Instead of allowing underscores `_` and `CamelCase`, everything is lower case and `_` is converted to `-`.<br>
  Also converts the paths when `path=1` is used._

* `folder=images/SUBFOLDER` folder in the Joomla root directory to search for files - **\[default: images\]**

* `subfolder=1` Search all subfolders recursively for files - **\[default: 0\]**

* `ext=pdf,png,doc` file extensions to search for (values separated by commas `,`) - **\[default: pdf,png,jpg,jpeg\]**<br>
  _Each specified extension is automatically searched in upper case letters too._

* `exclude=tmp.png,thumb,thumbnails` File or folder names to exclude from search (values separated by `,`)

* `excludeRegex=tmp,thumb,thumbnails` Certain keywords in file or folder names to be excluded from the search (values separated by commas `,`)

* `debug=off` If this parameter is set, the test mode is switched off and the changes are made<br>
  _As long as the debug=off parameter is not used, it is only a simulation, so nothing will be processed, just simulated._
  

### Examples:

* `https://example.org/tidyup_myfiles/exec.php`<br>
  Output of this help

* `https://example.org/tidyup_myfiles/exec.php?rename=1&folder=images/SUBFOLDER`<br>
  Searches the `images/SUBFOLDER` directory for files ending with `.pdf, .png, .jpg, .jpeg, .PDF, .PNG, .JPG, .JPEG` and checks them for websafe names. The endings are written in lower case and spaces are replaced by `_`, as well as umlauts. The database is searched for occurrences of the files to be changed and renamed if necessary. 


Special thanks go to the testers _Elisa Foltyn_, [_Christiane Maier-Stadherr_](https://www.chmst.de) and [_Thomas Finnern_](https://github.com/ThomasFinnern), who invested a lot of patience and time. :+1:
</details>
