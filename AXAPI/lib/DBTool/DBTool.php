<?php
/**
 * 通用的表操作类，可以用来执行或查询SQL语句
 * @package DBTool
 * @author axing
 * @since 1.0
 * @version 1.0
 */
// v161206 新增事务支持
// v161116 支持queryValues queryValue
// v161020 支持getLastInsertId
// v161012 sql 分析，支持select的order
// v160918 return number of single executeSql
// v160129 where with qutoedKeys
// v151014  (is_string($p_value) || is_int($p_value)))
// v150923 优化t1逻辑
// v150317 fix 别名，（和！的情况
// 150207  conditions_push
// 150122.1 + throw errow
// 141115.1 fixed -defaultThisValueTOcheckIFNOVALUEFORTHIS
// 141115 -defaultThisValueTOcheckIFNOVALUEFORTHIS
// 141019 edit
    include_once 'DBpwd.php';
    include_once 'DBConnect.php';

class DBTool
{
        public static $conn = null;
        private function __construct()
        {}
        private function __clone()
        {}
        public static function getCoon()
        {
            if(static::$conn == NULL)
            {
                static::$conn = new DBConnect();
            }
            return static::$conn->getCoon();
        }
        // 断开数据库连接
        public static function close()
        {
            if(static::$conn != NULL)
            {
                static::$conn->close();
            }
        }

        public static function debug($_str)
        {
            if (function_exists('AX_DEBUG'))
            {
                AX_DEBUG($_str);
            }
            else if (defined('IS_SQL_PRINT') && IS_SQL_PRINT)
            {
                print("\n");
                print($_str);
                print("\n");
            }
            else if (class_exists('W2Log'))
            {
                W2Log::debug($_str);
            }
        }
        /**
         * 将sql字符串中的干扰文字进行重编码
         * @param string sql字符串
         * @param boolen 是否以单引号将该字符串括起来
         * @return string 转码后的字符串
         */
        public static function wrap2Sql($p_str, $p_withSingleQuotes=false){
            // if (defined('IS_AX_DEBUG')){print("\n");print(W2Time::microtimetostr());print("\n");var_export(debug_backtrace());print("\n");}
            if (isset($p_str))
            {
                $_str = mysqli_real_escape_string(self::getCoon(),$p_str);
                if ($p_withSingleQuotes) {
                    $_str = '\''.$_str.'\'';
                }
                return $_str;
            }
            return $p_str;
        }

        /**
         * 分页limit字串
         * @param  [int] $p_pageIndex [分页，1开始]
         * @param  [int] $p_pageSize  [分页大小]
         * @return [string]            空字符串 或 limit 0,10 类似
         */
        public static function conditions_getLimit($p_pageIndex=null , $p_pageSize=null){
            $sqlLimit = '';
            if (isset($p_pageIndex))
            {
                $sqlLimit = sprintf(' limit %d,%d'
                                    ,($p_pageIndex-1)*$p_pageSize
                                    ,$p_pageSize
                                    );
            }
            return $sqlLimit;
        }


        /**
         * 组合where字符串
         * @param  array &$conditions  目标数组
         * @param  string $p_strFormat key值或序号
         * @param  string $p_value     值
         * @param  string  $t1          　　表别名
         * @return null
         */
        public static function conditions_push(&$conditions, $p_strFormat , $p_value = '-defaultThisValueTOcheckIFNOVALUEFORTHIS' ,$t1=null){
            if ($t1!=null)
            {
                if (is_int($p_strFormat) && $p_value!==null)
                {
                    $GLOBALS['t1tmpfordbtool'] = $t1;
                    if (strpos($p_value,$t1)===false)
                    {
                        // if (preg_match('/\(\s*([^\s][^\)]+)\)/',$p_value))
                        // {
                        //  $p_value = preg_replace_callback('/(\(\s*)([^\s][^\)]+)(\))/'
                        //                                  , function($matches){return $matches[1].$GLOBALS['t1tmpfordbtool'].'.'.$matches[2].$matches[3];}
                        //                                  , $p_value
                        //                                  );
                        // }
                        // else
                        // {
                            $p_value = preg_replace_callback('/(^|\s|\()([^\.\(\s]+)(\s*?[!=\>\<]|\s+?(is |not |in |like |between ))/'
                                , function($matches){return $matches[1].$GLOBALS['t1tmpfordbtool'].'.'.$matches[2].$matches[3];}
                                , $p_value
                                );
                        // }
                    }
                }
                else if($p_strFormat!==null && !preg_match('/^[^\.\s]+\.[^\.\s]+/', $p_strFormat ) )
                {
                    $p_strFormat = $t1.'.'.$p_strFormat;
                }
            }
            if (is_int($p_strFormat) && $p_value!==null)
            {
                $conditions[] =  $p_value;
            }
            else if ($p_value === '-defaultThisValueTOcheckIFNOVALUEFORTHIS')
            {
                $conditions[] =  $p_strFormat;
            }
            else if ($p_value === null)
            {
                return;//提供了p_value 却为null值，则说明是无效数据
            }
            else
            {
                $p_value = self::wrap2Sql($p_value);
                if (strpos($p_strFormat,'%')!==false && (isset($p_value) || $p_value==='') )
                {

                    if (preg_match('/\s*\S+\s+in\s+\([^\)]*\)\s*/', $p_strFormat))
                    {
                        if (preg_match('/\s*\S+\s+in\s+\(\s*\'\%s\'\s*\)\s*/', $p_strFormat))
                        {
                            if (strpos($p_value, ',')!=false)
                            {
                                array_push($conditions, sprintf($p_strFormat ,      implode('\',\'',explode(',',$p_value))));
                            }
                            else
                            {
                                $p_strFormat = preg_replace('/in\s+\(\s*\'.*\'\s*\)/', '= \'%s\'', $p_strFormat);
                                array_push($conditions, sprintf($p_strFormat ,       $p_value  ));
                            }
                        }
                        else
                        {
                            if (strpos($p_value, ',')!=false)
                            {
                                array_push($conditions, sprintf($p_strFormat ,      implode(',',explode(',',$p_value))));
                            }
                            else
                            {
                                $p_strFormat = preg_replace('/in\s+\([^\)]*\)/', '$1= %d', $p_strFormat);
                                array_push($conditions, sprintf($p_strFormat ,       $p_value  ));
                            }
                        }
                    }
                    else
                    {
                        array_push($conditions, sprintf($p_strFormat ,       $p_value  ));
                    }

                }
                else if ( preg_match('/^.+\s+([=\>\<]|is |not |in |like |between )\s*$/', $p_strFormat ) )
                {
                    $conditions[] =  $p_strFormat . $p_value;
                }
                else
                {
                    $p_strFormat = preg_replace('/(\S+?\.|^)([^\.]+)$/','$1`$2`',trim($p_strFormat,'`'));
                    if ( (in_array(strtolower($p_value),array('now()','null'))) )
                    {
                        $conditions[] =  sprintf('%s = NULL',$p_strFormat);
                    }
                    else if ( null !== $p_value && (is_string($p_value) || is_int($p_value)))
                    {
                        $conditions[] =  sprintf('%s = \'%s\'',$p_strFormat,$p_value);
                    }
                }
            }

        }


    /**
     * 执行sql语句或sql数组语句
     * @param array sql语句 或 sql数组语句
     * @param string sql 编码类型
     * @return array 执行结果 sql影响的行数。
     */
    public static function executeSql($p_sqls, $p_encode="utf8"){
        $_sqls = (is_array($p_sqls)) ? $p_sqls : array($p_sqls);
        if (count($_sqls)>0 ) {
            $_data = array();
            $_mysqli = self::getCoon();

            foreach ($_sqls as $_sql) {
                self::debug($_sql);
                $_mysqli->query($_sql);
                array_push($_data, $_mysqli->affected_rows);
            }

            if(strlen($_mysqli->error)>0){
                // $_data = 2;
                // if (function_exists('file_put_log'))
                // {
                //     file_put_log(array($_mysqli->error,$_sql),'error-db');
                // }
                throw new Exception($_mysqli->error,E_ERROR);
            }
            else
            {
                if (function_exists('file_put_log'))
                {
                    file_put_log($p_sqls,'sql');
                }
            }
            self::debug('executeSql END');
            return is_array($p_sqls)?$_data:$_data[0];
        }
        return false;
    }

    /**
     * 执行sql查询语句
     * @param array sql语句或sql语句数组
     * @param string sql 编码类型
     * @return array 执行结果
     */
    public static function queryData($p_sqls, $p_encode="utf8") {
        $_sqls = (is_array($p_sqls)) ? $p_sqls : array($p_sqls);
        $_data = array();
        if (count($_sqls)>0 ) {
            $_mysqli = self::getCoon();
            foreach ($_sqls as $_sql) {
                self::debug($_sql);
                $_tmpData = array();
                $_resultSet = $_mysqli->query($_sql);
                if($_resultSet !== false){

                    $_fields = array();
                    foreach ($_resultSet->fetch_fields() as $_field){
                        $_fields[$_field->name] = $_field->type;
                    }

                    while ($_row = $_resultSet->fetch_assoc()) {

                        foreach ($_row as $_key => $_value) {
                            if (isset($_value))
                            {
                                if ($_fields[$_key] <= 3 || $_fields[$_key] == 8)
                                {
                                    $_row[$_key] = intval($_value);
                                } else if ($_fields[$_key] == 4 || $_fields[$_key] == 5)
                                {
                                    $_row[$_key] = floatval($_value);
                                }
                            }
                        }

                        array_push($_tmpData, $_row);
                    }
                    $_resultSet->close();
                }
                else
                {
                    if(strlen($_mysqli->error)>0){
                        // file_put_log(array($_mysqli->error,$_sql),'error');
                        throw new Exception($_mysqli->error,E_ERROR);
                    }
                }
                array_push($_data,$_tmpData);
                self::debug('queryData END');
            }
            if(strlen($_mysqli->error)>0){
            }
        }
        if (is_array($p_sqls))
        {
            return $_data;
        }
        else
        {
            if (count($_data)>0)
            {
                return $_data[0];
            }
        }
        return array();
    }

    //主要用于只查询单个值的sql，取出值重组成数组。如果查询多个值，则没有变化。
    public static function queryValues($sql)
    {
        $_data = static::queryData($sql);
        $_values = array();
        if (is_array($_data))
        {
            if (count($_data)>0 && count($_data[0]) == 1)
            {
                foreach ($_data as $_d) {
                    foreach ($_d as $key => $value) {
                        $_values[] = $value;
                        break;
                    }
                }
            }
            else
            {
                return $_data;
            }
        }
        return $_values;
    }


    //主要用于只查询单个值的sql，取出第一个结果的值。
    public static function queryValue($sql)
    {
        $_values = static::queryValues($sql);
        if (is_array($_values) && count($_values)>0)
        {
            return $_values[0];
        }
        else
        {
            return null;
        }
    }

    /**
     * 分析sql，取得其中涉及到的字段及值
     * select和delete语句，取所有筛选字段
     * update语句，只取更新字段（不取筛选字段）
     * insert语句，取更新字段。
     * @param  string $sql
     * @return array
     */
    public static function getKeyInfoOfSql($sql)
    {
        $result = array();
        $conditions = array();
        // AX_DEBUG('SQL：'.json_encode($sql));
        $_tList = array();
        preg_match('/(select.*?from\s+(\S+)|delete.*?from\s+(\S+)|update\s+(\S+)|insert\s+into\s+(\S+))/i',$sql,$tMatch);

        switch (count($tMatch)) {
            case 3:
                $action = 'select';
                break;
            case 4:
                $action = 'delete';
                break;
            case 5:
                $action = 'update';
                break;
            case 6:
                $action = 'insert';
                break;

            default:
                $action = 'unknown';
                break;
        }
        $_tList[''] = count($tMatch)>0?trim(implode('',array_slice($tMatch,2)),'\'`'):'';

        if ( $action == 'insert')
        {// insert 语句需要特殊处理
            preg_match_all('/\(.*?\)/', $sql , $groups ,PREG_SET_ORDER);
            if (count($groups)>=2)
            {
                foreach ($groups as $index=>$group) {
                    preg_match_all('/(?:(?<!\\\)\')(?:.(?!(?<!\\\)\'))*.?\'|(?:(?<!\\\)`)(?:.(?!(?<!\\\)`))*.?`/is',$group[0],$matches,PREG_PATTERN_ORDER);
                    if (isset($matches[0]))
                    {
                        $values = $matches[0];
                        foreach ($values as &$value) {
                            $value = trim($value,'()\'` .');
                        }
                        // AX_DEBUG($values);
                        if ($index==0)
                        {
                            $keys = $values;
                        }
                        else
                        {
                            if (count($keys) == count($values))
                            {
                                for ($i=0; $i < count($values) ; $i++) {
                                    $conditions[] = array(
                                                'table'  => $_tList['']
                                                ,'action'=> $action
                                                ,'key'   => $keys[$i]
                                                ,'eq'    => '='
                                                ,'value' => $values[$i]
                                            );
                                }
                            }
                        }
                    }
                }
            }
        }
        else
        {
            if ($action == 'select')
            {
                preg_match_all('/order\s+by\s+([a-zA-Z0-9]+?\.|)(\S+)\s*/is', $sql , $matches ,PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $_t     = trim($match[1],'()\'` .');
                    $_key   = trim($match[2],'()\'` ');
                    if (!is_null($_t))
                    {
                        if (!isset($_tList[$_t]))
                        {
                            preg_match('/(\S+)\s+'.$_t.'\s+/',$sql,$tMatch);
                            $_tList[$_t] = trim($tMatch[1],'\'`');
                        }
                        $_tableName = $_tList[$_t];
                        $result['order']    = array(
                                    'table' =>$_tableName
                                    ,'action'=> $action
                                    ,'key'  =>$_key
                                );
                    }
                }
            }


            if ( $action == 'update')
            {//update 语句，忽略查询字段。
                $sql = preg_replace('/\swhere\s.*/i','',$sql);
            }
            /*
            (?:(?<!\\\)\')(?:.(?!(?<!\\\)\'))*.?\'
            ?: 表示本括号不参与捕获（即不保存结果）
            (?<!\\\)\' 非左匹配（?<!本身不占字符），即匹配左侧不是反斜杠的单引号
            .(?!(?<!\\\)\') 非右匹配，即匹配任意字符（但该字符右侧不是(左侧不是反斜杠的单引号)）
            /is  i是忽略大小写  s是.匹配换行
             */
            preg_match_all('/(\S*\.|)(\S+)\s*([=\>\<]| is | not | in | like | between )+\s*([^\'`\s]\S*|(?:(?<!\\\)\')(?:.(?!(?<!\\\)\'))*.?\'|(?:(?<!\\\)`)(?:.(?!(?<!\\\)`))*.?`)/is', $sql , $matches ,PREG_SET_ORDER);
            foreach ($matches as $match) {
                $_t     = trim($match[1],'()\'` .');
                $_key   = trim($match[2],'()\'` ');
                $_eq    = trim($match[3]);
                $_value = trim($match[4],'() ;');
                if (!is_null($_t))
                {
                    if (strpos($_value,'.')!==false && strpos($_value,'\'')!==0)
                    {//此处忽略了 t2.id = t1.userID这样的情况。
                        continue;
                    }
                    if (!isset($_tList[$_t]))
                    {
                        preg_match('/(\S+)\s+'.$_t.'\s+/',$sql,$tMatch);
                        $_tList[$_t] = trim($tMatch[1],'\'`');
                    }
                }
                $_tableName = $_tList[$_t];

                if (strtolower($_eq) == 'in')
                {
                    $_value = explode(',',$_value);
                }
                else
                {
                    $_value = array($_value);
                }
                foreach ($_value as $_val) {
                    $_val = trim($_val,'\'` ');
                    $conditions[] = array(
                                'table' =>$_tableName
                                ,'action'=> $action
                                ,'key'  =>$_key
                                ,'eq'   =>$_eq
                                ,'value'=>$_val
                            );
                }
            }

        }


        $result['tables'] = $_tList;
        $result['action'] = $action;
        $result['conditions'] = $conditions;



        return $result;
    }

    /** 取得上一次插入数据的id */
    public static function getLastInsertId()
    {
        $_mysqli = self::getCoon();
        return $_mysqli->insert_id;
    }

    /** 是否使用自动提交，如果否，则为使用事务功能，需要在业务逻辑完成后，手动提交确认。 */
    public static function autocommit($isAuto=true)
    {
        $_mysqli = self::getCoon();
        $_mysqli->autocommit($isAuto);//如果设为false，即开启事务，需要在确认之后，手动提交。
    }

    /** 提交事务中的操作 */
    public static function commit()
    {
        $_mysqli = self::getCoon();
        $_mysqli->commit();
    }

    /** 回滚事务中的操作 */
    public static function rollback()
    {
        $_mysqli = self::getCoon();
        $_mysqli->rollback();
    }

}
