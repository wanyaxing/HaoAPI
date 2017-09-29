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
            $eTag = md5_file($_jobFile);
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH']==$eTag)
            {
                header('HTTP/1.0 304 Not Modified');
                exit;
            }
            header('Cache-Control:public');
            header("ETag: ".$eTag);
            echo file_get_contents($_jobFile);
        }
    }
}
