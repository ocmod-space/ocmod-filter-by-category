<?php

// internal constants

define('FCLDIR', '_fcl');
define('FCLIGNORE', '.fclignore'); // ignorelist for fcl
define('SRCDIR', 'src');
define('ZIPDIR', 'zip');
define('ZIPEXT', '.ocmod.zip');

define('MDIR', 'module'); // dir with module
define('ADIR', 'addons'); // dir with addons

define('MAKEZIP', 'z'); // -z[N] - create zip. N - number of module(0) or addons(1..).
define('GETHELP', 'h'); // -h - print help.
define('MAKEFCL', 'a'); // -a - make encrypted .fcl-archive.
define('EXTRFCL', 'x'); // -x - extract files from fcl-archive.
define('LISTFCL', 'l'); // -l - list of files in fcl-archive.
define('DIRPATH', 'd'); // -d<path> - path to working directory with the `pakdef.php` file.
