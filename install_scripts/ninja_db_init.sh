#!/bin/sh

# setup the db tables required for Ninja

db_user=root
db_pass=

if [ $# -ge 1 ]
then
	prefix=$1
else
	prefix="/opt/monitor/op5/ninja";
fi

run_sql_file () # (db_login_opts, sql_script_path)
{
	db_login_opts=$1
	sql_script_path=$2

	mysql $db_login_opts merlin < $sql_script_path >/dev/null 2>/dev/null
}

if [ "$db_pass" != "" ]
then
	db_login_opts="-u$db_user -p$db_pass"
else
	db_login_opts="-u$db_user"
fi

db_ver=$(mysql $db_login_opts -Be "SELECT version FROM ninja_db_version" merlin 2>/dev/null | sed -n \$p)

if [ "$db_ver" = '' ]
then
	# nothing found, insert ninja.sql
	echo "Installing database tables for Ninja GUI"
	run_sql_file $db_login_opts "$prefix/install_scripts/ninja.sql"
fi

# import users and authorization data
echo "Importing users from cgi.cfg"
/usr/bin/env php "$prefix/install_scripts/auth_import_mysql.php" $prefix

# check if we should add recurring_downtime table
if [ "$db_ver" = '1' ]
then
	# add table for recurring_downtime
	echo "Installing database table for Recurring Downtime"
	run_sql_file $db_login_opts "$prefix/install_scripts/recurring_downtime.sql"
	mysql $db_login_opts -Be "UPDATE ninja_db_version SET version=2" merlin 2>/dev/null
fi
