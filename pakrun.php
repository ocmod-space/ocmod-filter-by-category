<?php

// PAK - OpenCart Extension Packer v0.4.0

require_once '_pak/conf.php';

require_once '_pak/func.php';

require_once '_pak/reqd.php';

/*
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // error was suppressed with the @-operator
    if (@error_reporting() === 0) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
*/

$clo = get_clo();
$basename = strtolower(basename(getcwd()));

if (isset($clo[MAKEZIP]) && !is_false($clo[MAKEZIP])) {
    require_once 'pakdef.php';

    $workdir = get_wd($clo[MAKEZIP]);

    if ($workdir) {
        $srcdir = strtopath(strtopath($workdir) . SRCDIR);
        $zipdir = strtopath(strtopath($workdir) . ZIPDIR);

        if (strpos($workdir, strtopath(ADIR)) === 0) {
            $basename .= str_replace(strtopath(ADIR), '--', $workdir);
        }

        define('MODFILE', $basename);

        $zipfile = $basename;
        $zipfile = $zipdir . $zipfile . ZIPEXT;

        $mod_code = str_replace('--', '|', $basename);

        define('MODCODE', $mod_code);

        $mod_name = str_replace('|', ' ', $mod_code);
        $mod_name = ucwords($mod_name);
        $mod_name = str_replace(' ', '|', $mod_name);
        $mod_name = str_replace('-', ' ', $mod_name);
        $mod_name = ucwords($mod_name);

        define('MODNAME', $mod_name);

        if (chkdir($srcdir) && chkdir($zipdir)) {
            if (is_file($zipfile)) {
                unlink($zipfile);
            }

            mkzip($srcdir, $zipfile, true);
        } else {
            error('Can not create dir: ' . $zipdir);
        }
    } else {
        error('There is no directory corresponding to number ' . $clo[MAKEZIP]);
    }
} elseif (isset($clo[MAKEFCL]) || isset($clo[EXTRFCL]) || isset($clo[LISTFCL])) {
    $fcl_file = strtopath(FCLDIR) . $basename . '.fcl';

    if (isset($clo[MAKEFCL])) {
        chkdir(FCLDIR);

        out(runfcl('make', $fcl_file, '-f' . get_fclignore(FCLIGNORE)));
        out(runhideg($fcl_file));
    } elseif (isset($clo[EXTRFCL]) || isset($clo[LISTFCL])) {
        if (is_file($fcl_file . '.g')) {
            out(runhideg($fcl_file . '.g'));

            if (is_file($fcl_file)) {
                if (isset($clo[EXTRFCL])) {
                    out(runfcl('extr', $fcl_file, '-f'));
                }

                if (isset($clo[LISTFCL])) {
                    out(runfcl('list', $fcl_file));
                }
            } else {
                error('file "' . $fcl_file . '" is missing!');
            }
        } else {
            error('file "' . $fcl_file . '.g' . '" is missing!');
        }
    }

    if (is_file($fcl_file)) {
        unlink($fcl_file);
    }
} else {
    include '_pak/help.php';

    out('Numbers:');

    foreach (get_enumerated() as $idx => $name) {
        out('[' . $idx . '] - ' . $name);
    }
}

exit(0);
