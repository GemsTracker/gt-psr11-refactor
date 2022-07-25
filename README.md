# GemsTracker psr11 refactor

This code is used for the transfer op code from the class_name_etc format to namespaces as \class\name\etc.

To use it got to a GT project directory and then run first:

`php -f \this\project\dir\psrGather.php`

This creates psrList.php in the project. If you then run:

`php -f \this\project\dir\psrRefactor.php`

All the files will be scanned and refactored. To get a preview run this command first:

`php -f \this\project\dir\psrRefactor.php -- -p`

After completetion you'll have to edit the Gems_Roles file and remove the leading slash in this line:

`        if (get_class(self::$_instanceOfSelf)!=='\\Gems\\Roles') {`

to 

`        if (get_class(self::$_instanceOfSelf)!=='Gems\\Roles') {`

To get everything to work.


You can use `reset.cmd` in the Windows command line to undo the changes.
