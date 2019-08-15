# "Textile zu HTML" Migrator

## Keine Lust mehr auf Textile?

Dieses AddOn hilft bei der Umsetellung von Textile zu HTML. 

## Anwendung: 

- AddOn installieren
- Backup der DB erstellen (für alle Fälle) 
- Textile Migration aufrufen und die gewünschte Option ausführen

## Features

- Article Slices migrieren mit automatischer Erkennung (**Experimentell**)
- Article Slices migireren ohne automatische Erkennung
- Datenbank Tabelle migireren

Wird "ohne automatische Erkennung" ausgewählt, kann man selbst festlegen welche Values migriert werden. 

### Beispielcodes

#### Modul
```
modules:
    - id: 122
      values:
        - value: value2
```        

#### MBlock Module:

```
modules:
    - id: 122
      values: 
        - value: value2
          mblock_keys: 
            - 2.0.text
```


#### Example DB only:

```
tables:
    - table: rex_test
      columns:
        - column: textile
        - column: textile2
