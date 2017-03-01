# SugarCli
SugarCli is a command line tool to install and manage SugarCRM installations.  This version is modified for Plus Consulting use.

# Building
Clone the git repository and copy the commands in the bash/ directory to your /usr/bin directory.

#Commands
###Backup a SugarCRM instance

```bash
Usage: backup [-hducs] [INSTANCE_NAME]...
Backup a SugarCRM instance to your home directory

    -h          Display this help and exit
    -d          Delete previous backup(s) from your home directory
    -u          Include upload/ directory (excluded by default)
    -c          Include cache/ directory  (excluded by default)
    -s          Use simple names (no date in name)

    
    Example:   backup -d SugarCustomer
```
This will backup both the files and database to your home directory.  By default
it will not backup the upload/ or cache/ directories, if you want those backed up you
have to tell the backup command like this

```bash
    backup -duc [SugarInstance]
```

The 'Simple Names' option is really only used for the CLONE command explained later 
in this document.

###Restore a SugarCRM instance

```bash
Usage: restore [-hd] [INSTANCE_NAME] [FILES_ZIP] [SQL_ZIP]
Restore a SugarCRM instance to the directory [INSTANCE_NAME].  
The .htaccess file and config.php will be updated to correct values.

    -h          display this help and exit
    -d          delete the destination directory
    -u          Leave the upload directory from the instance in place
    -c          Leave the cache directory from the instance in place

example: restore mySugarInstance ~/mySugarInstance.tgz ~/mySugarInstance.sql.gz
```

The SQL_ZIP parameter is optional.  So if you have a file from OnDemand where both
the files and the SQL dump are all in the same file then just provide the 
FILES_ZIP parameter.

By default the contents of the uploads/ and cache/ directories are deleted before
the files are copied to the http directory.  If you want to retain them then you have 
to add the -u and/or -c parameters.

This command will handle gzipped, tarred or zip files.

###Clone a SugarCRM instance
```bash
Usage: clone [-hd] [NEW_INSTANCE_NAME] [CURRENT_INSTANCE_NAME]
Clone a SugarCRM instance to the directory [NEW_INSTANCE_NAME] using the [CURRENT_INSTANCE_NAME]
as the source.
    -h          display this help and exit
    -d          delete the destination directory

example: clone myNewSugarInstance myCurrentSugarInstance
```

This command simply runs a 'backup' and then a 'restore' and then deletes the 
backup from the home directory for you.

###Make a backup of a table
```bash
Usage: copyTable [-d] [INSTANCE_NAME] [TABLE_NAME] [NEW_TABLE_NAME]
Make a copy of a database table.

    -d          Drop [NEW_TABLE_NAME] if it exists
    -h          Display this help and exit

example: copyTable -d mySugarInstance accounts accounts_temp
```

To restore the original table back into place you would run

```bash
copyTable -d mySugarInstance accounts_temp accounts
```

###Create an installable package
```bash
Usage: makePackage [-nvamcrtedhu#s$] [INSTANCE]...
Dump the custom directory, custom modules and an array of tables to an installable package

    -h          Display this help and exit
    -n[string]  A name for the new package.
    -v[number]  A version number for the new package. (ex 1.2)
    -s[string]  Include only files that contain this string, AKA Search.
    -a          Add the ACL/Roles tables to the package.
    -t          Add the Teams tables to the package.
    -r          Add the Reports table (saved_reports) to the package.
    -m          Add the modulebuilder/ directory to the package.
    -c          Add the custom fields table (fields_meta_data) to the package.
    -d          Include all the data from custom modules in the package.
    -e          Truncate tables where data is added, otherwise data will be updated
    -o          Include only english language files in the package.
    -u[number]  Include only files that were updated in the last 15 minutes.
```
This command will create an installable zip file for you from a SugarCRM instance.  If you 
simply wanted to create an installable module from all custom code and custom modules 
you would use the command
```bash
makePackage mySugarInstance
```
This would add ALL the files in the custom directory to your installer.  The moduleBuilder
directory is left out by default, you can add it back with -m. Also left out would
be any files that are complied from the custom/Extensions directory.

You could add a name and a version to that file with
```bash
makePackage -nCustomPackage -v2.0 mySugarInstance
```
That would create ~/CustomPackage.zip and the version string in that installer
would be '2.0'.

You could now add data to the installer with
```bash
makePackage -d -nCustomPackage -v2.0 mySugarInstance
```
That would collect a dump of all the tables accociated with your custom modules
and makes post_install scripts out of them.  With the -e command you can make the script
truncate the table before the import.

You can also add other tables to the scripts like the reports table (-r) and all the ACL
related tables (-a).

You can also create 'update' installers by using the -u and -s parameters.  The 'update'
parameter (-u) allows you to include only files that were updated recently.  For example

```bash
MakePackage -u15 mySugarInstance
```

Will make a package that only includes files that were updated in the last 15 minutes.  
This parameter only works on files, not database entries. So if you used the command

```bash
makePackage -du15 mySugarInstance
```

you would get a installer that had the files from the last 15 minutes but all the 
data in the table. [SLATED TO BE FIXED IN VERSION 3.0]

The 'search' parameter allows you to include all files that include a search term.  For
example

```bash
makePackage -sbugfix_020117 mySugarInstance
```

would only include files that had that search term in them.  I normally put these search
terms in the beginning comments like

```php
<?php
//bugfix_020117
```
You can mix the two together like

```bash
makePackage -u15 -sbugfix_020117 mySugarInstance
```

Will do just what you would expect it to do.

###Set permissions on a SugarCRM instance
```bash
Usage: perms [INSTANCE_NAME]
Reset owners and permissions on all SugarCRM files.

example: perms mySugarInstance
```

This will set all the permissions and owners in a SugarCRM instance

###Run a Quick Repair and Rebuild on a SugarCRM instance
```bash
Usage: qrr [INSTANCE_NAME]
Run a Quick Repair and Rebuild on a SugarCRM.

example: qrr mySugarInstance
```

###Make a SugarCRM instance safe to run
```bash
Usage: safety [INSTANCE_NAME] [EMAIL_ADDRESS]
Rewrite all email addresses in an instance so that email wont accidently go out while testing.  You
can optionally specify an email address you have control of in the second param.

example: safety mySugarInstance
                        or
         safety mySugarInstance ken.brill.plus@gmail.com
```
When you install a customers instance on your local machine it might be possible for that instance
to start sending emails to the customer based on actions you take.  With this command you can rewrite
all of the email addresses on the system to point at an email address you can read. 

###Set all the passwords for an instance
```bash
Usage: setPasswords [INSTANCE_NAME]
Resets all user passwords in an instance to 'asdf'.  Also resets
the admin user if it was repurposed or deleted.

example: setPasswords mySugarInstance
```
