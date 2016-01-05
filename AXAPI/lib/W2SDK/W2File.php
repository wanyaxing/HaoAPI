<?php
/**
 * 文件处理函数库文件
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */

class W2File {
    /**
     * 读取文件内容,返回字符串
     * @param string $p_filePath 文件路径
     * @return sting 文件内容
     */
    public static function loadContentByFile($p_filePath){
        if(!file_exists($p_filePath) || filesize($p_filePath)==0){
            return null;
        }
        $fp = fopen($p_filePath, 'r');
        $c = fread($fp, filesize($p_filePath));
        fclose($fp);
        return $c;
    }

    /**
     * 读取文件内容中的数组
     * @param string $p_filePath 文件路径
     * @return sting 数组
     */
    public static function loadArrayByFile($p_filePath){
        return json_decode(W2File::loadContentByFile($p_filePath),true);
    }

    /**
     * 读取文件内容中的对象
     * @param string $p_filePath 文件路径
     * @return sting 对象
     */
    public static function loadObjectByFile($p_filePath){
        $o = json_decode(W2File::loadContentByFile($p_filePath),false);
        return is_array($o)?(object)$o:$o;
    }

    /**
     * 将对象或文本写入文件
     * @param string $p_filePath 文件路径
     * @param mixed $p_content 要写入的内容, 内容为对象或者数组时, 会自动转换为json格式写入
     * @param string $p_mode 文件打开方式,默认为w
     */
    public static function writeFile($p_filePath, $p_content, $p_mode='w'){
        $fp = fopen($p_filePath, $p_mode);
        fwrite($fp, (is_array($p_content)||is_object($p_content))?json_encode($p_content):$p_content);
        fclose($fp);
    }
    /**
     * 判断目标文件夹是否存在，如不存在则尝试以0777创建
     * @param string $dir 文件路径
     */
    public static function directory($dir){
        // echo $dir;
        return   is_dir ( $dir )  or  (W2File::directory(dirname( $dir ))  and  mkdir ( $dir ) );
    }

    /**
     * 删除目标文件夹（及其所有子文件）
     * @param  [type] $dir [description]
     * @return [type]      [description]
     */
    public static function deldir($dir) {
        //先删除目录下的文件：
        if (is_dir($dir))
        {
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir."/".$file;
                    if (!is_dir($fullpath)) {
                        unlink($fullpath);
                    } else {
                        static::deldir($fullpath);
                    }
                }
            }

            closedir($dh);
            //删除当前文件夹：
            if (rmdir($dir))
            {
                return true;
            }
        }
        return false;
    }



}

/**
 * unit test
 */
/*
if(array_key_exists('argv', $GLOBALS) && realpath($argv[0]) == __file__){
    $f1 = '/Users/Wan/Project/_file-upload/aa';
    writeFile($f1, array(1,2,3,4));
    var_dump(loadContentByFile($f1));
    var_dump(loadArrayByFile($f1));
    var_dump(loadObjectByFile($f1));

    $f2 = '/Users/Wan/Project/_file-upload/bb';
    writeFile($f2, array('a'=>1,'b'=>2,'c'=>3,'d'=>4));
    var_dump(loadContentByFile($f2));
    var_dump(loadArrayByFile($f2));
    var_dump(loadObjectByFile($f2));
}
*/

?>
