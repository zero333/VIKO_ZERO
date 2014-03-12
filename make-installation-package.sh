# create viko.conf from example configuration file
mv viko.conf.example viko.conf

# API documentation and tests are not needed in non-dev release,
# so remove tests and script for creating docs.
rm -R test/
rm create-doc.sh

# The .mo files for translations are not stored in repository,
# so we have to create these from .po files:
msgfmt -o locale/et_EE/LC_MESSAGES/VIKO.mo locale/et_EE/LC_MESSAGES/VIKO.po
msgfmt -o locale/ru_RU/LC_MESSAGES/VIKO.mo locale/ru_RU/LC_MESSAGES/VIKO.po

# The normal user has no use of .po .pot files and generation scripts
# for those. So we remove all these.
cd locale/
rm et_EE/LC_MESSAGES/VIKO.po
rm ru_RU/LC_MESSAGES/VIKO.po
rm messages.pot
rm generate-mo.sh
rm refresh-po.sh
cd ..

# Some PEAR packages contain many files that are actually not used.
cd PEAR/
# DB package contains support for a lot of databases, but only MySQL is supported in VIKO
cd DB/
rm dbase.php
rm fbsql.php
rm ibase.php
rm ifx.php
rm msql.php
rm mssql.php
rm mysqli.php
rm oci8.php
rm odbc.php
rm pgsql.php
rm sqlite.php
rm storage.php
rm sybase.php
cd ..
# Text_Wiki contains renderers for plain text and LaTeX, but VIKO only uses Xhtml
cd Text/Wiki/Render/
rm -R Latex/
rm Latex.php
rm -R Plain/
rm Plain.php
cd ../../..

# return to main directory
cd ..
