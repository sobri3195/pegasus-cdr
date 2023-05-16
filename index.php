<?php

require_once ("src/Cdr.php");
$report = new Cdr("mysql:host=localhost;dbname=asteriskcdrdb", "root", "");
$report->run();
