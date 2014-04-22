migration
=========

Test migration tool. Includes importer and generator.

generator
=========

Launch generator to generate a test data

`php generator.php -f [destination_file_name] -n [number_of_rows]`

importer
========

Launches import of test data from generated source file to `actor` and `actor_data` tables

`php importer.php -h [db_host] -u [db_user] -p[db_passwd] -db [db_schema_name] -f [source_file] -t [skip_creating_temp_file_bool]`

`-t` flag is optional. It will not update tmp file, if you already have one and don't want to update it
