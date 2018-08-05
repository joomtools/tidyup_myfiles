# Tidyup my files
Dieses Projekt ist entstanden, um z.B. Joomla-Administratoren einer Redaktionsseite, die keinen Zugriff auf die Konsole des Host haben und auch sonst nicht genug Erfahrung mit Datenbanksystemen haben, die Arbeit zu erleichtern.
 
 Es soll sie dabei unterstützen eine Massenumbenennung von Dateien und Verzeichnissen, samt Anpassung der Datenbank, in ein URL-Konformes Format vorzunehmen. Es berücksichtigt auch Werte, die in der Datenbank mit `json_encode()` und `serialize()` gespeichert wurden.


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

  * `folder=images/banner` Ordner im Joomla Rootverzeichnis, indem nach Dateien gesucht werden soll - **\[default: images\]**

  * `subfolder=1` Alle Unterordner rekursiv nach Dateien durchsuchen - **\[default: 0\]**

  * `ext=pdf,png,doc` Dateiendungen nach denen gesucht werden soll (Werte durch Komma `,` getrennt) - **\[default: pdf,png,jpg,jpeg\]<br>
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
  Durchsucht das Verzeichnis `images/UNTERORDENR` nach Dateien mit der Endung `.pdf, .png, .jpg, .jpeg, .PDF, .PNG, .JPG, .JPEG` und Prüft sie auf URL-Konformität. Die Endungen werden kelingeschrieben und Leerzeichen durch `_` ersetzt, sowie Umlaute umgeschrieben. Es wird in der Datenbank nach Vorkommen der zu ändernden Dateien gesucht und ggf. Umbenannt. 


Ein besonderer Danke geht an die Tester _Elisa Foltyn_, _Christiane Maier-Stadtherr_ und [_Thomas Finnern_](https://github.com/ThomasFinnern), die viel Geduld und Nerven gezeigt haben. :+1:
