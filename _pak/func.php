<?php

// internal functions

function get_clo() {
    $o = '';

    $o .= MAKEZIP . '::';
    $o .= GETHELP;
    $o .= MAKEFCL;
    $o .= EXTRFCL;
    $o .= LISTFCL;

    $options = getopt($o);

    if ($options) {
        $clo = array();

        if (isset($options['z'])) {
            $clo[MAKEZIP] = $options[MAKEZIP] !== false ? (int)$options[MAKEZIP] : false;
        } elseif (isset($options[GETHELP])) {
            $clo[GETHELP] = 1;
        } elseif (isset($options[MAKEFCL])) {
            $clo[MAKEFCL] = 1;
        } elseif (isset($options[EXTRFCL])) {
            $clo[EXTRFCL] = 1;
        } elseif (isset($options[LISTFCL])) {
            $clo[LISTFCL] = 1;
        }

        return $clo;
    }

    return false;
}

function get_wd($num) {
    if ($num === 0 && is_dir(MDIR)) {
        return MDIR;
    }

    $addons = get_dir_list(ADIR);

    if ($addons && isset($addons[$num - 1])) {
        $addon_id = $addons[$num - 1];

        return strtopath(ADIR) . $addon_id;
    }

    return false;
}

function out(string $text = '') {
    echo $text . "\n";
}

function info(string $text = '') {
    info('INFO: ' . $text);
}

function error(string $text = '') {
    out('ERROR: ' . $text);

    exit(1);
}

// returns directory path with a directory separator on the right
function strtopath(string $dir) {
    $dir = trim($dir);
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);
    $dir .= DIRECTORY_SEPARATOR;

    return $dir;
}

function get_dir_list($path) {
    $list = array();

    if (is_dir($path)) {
        foreach (scandir($path) as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($path . DIRECTORY_SEPARATOR . $dir)) {
                // $list[] = $path . DIRECTORY_SEPARATOR . $dir;
                $list[] = $dir;
            }
        }
    }

    return $list;
}

function is_false($x) {
    if ($x === false) {
        return true;
    }

    return false;
}

function chkdir(string $dir) {
    if (is_dir($dir)) {
        return true;
    }

    return mkdir($dir);
}

function mkzip($srcdir, $zipfile, $force = false) {
    if (is_file($zipfile) && !$force) {
        error($zipfile . ' already exists! Use force flag');
    }

    $zip = new ZipArchive();

    if ($zip->open($zipfile, ZipArchive::CREATE) === true) {
        foreach (get_file_list($srcdir) as $file) {
            $relative = substr($file, strlen($srcdir));

            if (is_file($file)) {
                $content = replacer($file);

                $zip->addFromString($relative, $content);
            } elseif (is_dir($file)) {
                $zip->addEmptyDir($relative);
            }
        }

        try {
            $zip->close();
        } catch (Exception $e) {
            error(' creating "' . $zipfile . '" error:' . "\n" . $e);
        }

        // PHP >= 8.0.0, PECL zip >= 1.16.0
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $zip->open($zipfile);

            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $stat = $zip->statIndex($i);

                $zip->setMtimeIndex($i, strtotime('2023-01-01 00:00:01'));
            }

            $zip->close();
        }
    } else {
        error('can not create "' . $zipfile . '"!');
    }
}

function get_file_list($path) {
    $files = array();

    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                // continue;
            }

            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function replacer($file, $to_replace = array()) {
    if (!$to_replace) {
        $to_replace = get_defined_constants(true)['user'];
    }

    $content = '';

    if ($pointer = fopen($file, 'r')) {
        while (!feof($pointer)) {
            $line = fgets($pointer);

            if (strpos($line, '<insertfile>') !== false) {
                $ifile = get_string_between($line, '<insertfile>', '</insertfile>');

                if (empty($ifile) || !is_file($ifile)) {
                    error('in "' . $file . '". Check placeholder file "' . $ifile . '"');
                }

                $ifile = preg_replace('/[^a-z0-9]+$/i', '', $ifile);
                $line = file_get_contents($ifile);
            }

            while (strpos($line, '<insertvar>') !== false) {
                $ivar = get_string_between($line, '<insertvar>', '</insertvar>');
                $ivar = preg_replace('/[^a-z0-9]+$/i', '', $ivar);

                if (empty($ivar) || !array_key_exists($ivar, $to_replace)) {
                    error('in "' . $file . '". Check placeholder var "' . $ivar . '"');
                }

                $search = '<insertvar>' . $ivar . '</insertvar>';
                $replace = $to_replace[$ivar];
                $line = str_replace($search, $replace, $line);
            }

            $content .= $line;
        }

        fclose($pointer);
    }

    return $content;
}

function get_string_between($string, $start, $end) {
    $ini = strpos($string, $start);

    if ($ini === false) {
        return '';
    }

    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;

    return substr($string, $ini, $len);
}

function get_fclignore($file) {
    $fclignore = '';

    if (is_file($file)) {
        if ($pointer = fopen($file, 'r')) {
            while (!feof($pointer)) {
                $line = fgets($pointer);
                $line = trim($line);
                $line = rtrim($line, DIRECTORY_SEPARATOR);

                if ($line && strpos($line, '#') !== 0) {
                    if (strpos($line, '!') === 0) {
                        $fclignore .= ' -D' . $line;
                    } else {
                        $fclignore .= ' -E' . $line;
                    }
                }
            }

            fclose($pointer);
        }
    }

    return $fclignore;
}

function runfcl(string $cmd, string $file, string $opts = '') {
    return shell_exec('fcl ' . $cmd . ($opts ? ' ' . $opts : '') . ' ' . $file);
}

function runhideg($file) {
    if (!is_file('hideg.pwd')) {
        // $f = fopen("hideg.pwd", "w") or die("Unable to open file!");
        // $n = readline('Enter name: ');
        // $p = readline('Enter password: ');
        // fwrite($f, $n . PHP_EOL);
        // fwrite($f, $p . PHP_EOL);
        // fclose($f);

        out('File hideg.pwd is missing!');
        out('Enter your name and press ENTER, then do the same for a password...');
        shell_exec('hideg');
    }

    return shell_exec('hideg ' . $file);
}

function delete_content($path) {
    try {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot()) {
                continue;
            }
            if ($fileinfo->isDir()) {
                if (delete_content($fileinfo->getPathname())) {
                    @rmdir($fileinfo->getPathname());
                }
            }
            if ($fileinfo->isFile()) {
                @unlink($fileinfo->getPathname());
            }
        }
    } catch (Exception $e) {
        // write log
        return false;
    }

    return true;
}

function get_enumerated() {
    $enumerated = array();

    if (is_dir(MDIR)) {
        $enumerated[] = strtolower(basename(getcwd()));
    } else {
        $enumerated[] = false;
        unset($enumerated[0]);
    }

    if (is_dir(ADIR)) {
        $addons = get_dir_list(ADIR);

        foreach ($addons as $name) {
            $enumerated[] = strtopath(ADIR) . $name;
        }
    }

    return $enumerated;
}
