
*******************************************************************************************

CustomForm - XML powered module for PHP by Kristijan Burnik

*******************************************************************************************

XML powered Web Forms via PHP implementation


Features
========
XML based structure
Simple language and label handling
Regular Expression checking
Post duplication detection
Mandatory fields
Simple list and option importing (e.g. list of countries in seperate XML file)
File upload with file type restrictions
Simple Class extending with "hook" functionality == simple user form implementation


Samples
========
Main form XML file: userforms/accreditation/accreditation-form.xml
Language resource file: userforms/accreditation/accreditation-resources-en.xml
List of countries: xml/countries-resources.xml


-----

Notes to self:

Form description:

1) Field name
2) Field label
3) Field Type (text,checkbox,radio,select,file)
	File:
		1) Available file type [All | zip | image | text | {extensions:[]}]
		2) Image size (jpg,png,gif)
	Radio/Select:
		List of entries (entries to table)
		Default value: (null -> Text)  or ( ID -> EntryTitle)

4) Field RegEXP (common | {custom:[]} )
5) IsMandatory?
6) MatchWith? (e.g. pwds)
7) Field order (index)

Mail to:
List of e-mails

Save to:
Database

Export:
CSV
XML
JSON
SQL
PHP Array


