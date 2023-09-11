<?php

// check required tools and php modules

if (!extension_loaded('zip')) {
	error('php-zip module not installed!');
}

if (!is_file('/usr/local/bin/hideg')) {
	error('/usr/local/bin/hideg is missing!');
}

if (!is_file('/usr/local/bin/fcl')) {
	error('/usr/local/bin/fcl is missing!');
}
