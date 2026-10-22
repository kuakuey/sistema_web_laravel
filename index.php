<?php

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

header('Location: '.$base.'/public/');
exit;
