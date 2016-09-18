<?php

$_mhcRootDir = __dir__.'/'.'../../../mhc';
if (is_dir($_mhcRootDir))
{
    $_jobFileName = $_GET['conf'];
    $_jobDirName  = $_GET['dir'];
    if (preg_match('/^apitest_config\.\w*\.js$/',$_jobFileName))
    {
        $_jobFile = $_mhcRootDir.'/'.$_jobDirName.'/'.$_jobFileName;
        if (file_exists($_jobFile))
        {
            echo file_get_contents($_jobFile);
        }
    }
}
