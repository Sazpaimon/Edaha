<?php

include "init.php";
DEFINE('IN_MANAGE', true);

//Load the command resolver
kxCmdResolv::run(kxEnv::getInstance());

exit();
?>