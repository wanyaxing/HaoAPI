<?php
/**
 * 通用的表操作类，可以用来执行或查询SQL语句
 * @package DBTool
 * @author axing
 * @since 1.0
 * @version 1.0
 */
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
		private function __construct()
		{}
		private function __clone()
		{}
		public static function getCoon()
		{
			static $conn = NULL;
			if($conn == NULL)
			{
				$conn = new DBConnect();
			}
			return $conn->getCoon();
		}
		public static function debug($_str)
		{
			if (defined('IS_SQL_PRINT') && IS_SQL_PRINT)
			{
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
			if (isset($p_str))
			{
			    $_str = str_replace(array('<','>','"',"'"), array('&lt;','&gt;','&quot;','&#39;'), $p_str);
			    $_str = str_replace('\'', '\'\'', $p_str);
			    // $_str = str_replace("_","\_",$_str);
			    // $_str = str_replace("%","\%",$_str);
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
					$p_value = preg_replace_callback('/(^|\s|\()([^\.\(\s]+)(\s*?[!=\>\<]|\s+?(is|not|in|like|between))/', function($matches){return $matches[1].$GLOBALS['t1tmpfordbtool'].'.'.$matches[2].$matches[3];}, $p_value);
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
				else if ( preg_match('/^.+\s+([=\>\<]|is|not|in|like)\s*$/', $p_strFormat ) )
				{
					$conditions[] =  $p_strFormat . $p_value;
				}
				else if ( null !== $p_value )
				{
					$conditions[] =  sprintf('%s = \'%s\'',$p_strFormat,$p_value);
				}
			}

		}


    /**
     * 执行sql语句或sql数组语句
     * @param array sql语句 或 sql数组语句
     * @param string sql 编码类型
     * @return array 执行结果
     */
	public static function executeSql($p_sqls, $p_encode="utf8"){
	    $_sqls = (is_array($p_sqls)) ? $p_sqls : array($p_sqls);
	    $_data = array();
	    if (count($_sqls)>0 ) {
	        $_mysqli = self::getCoon();

	        foreach ($_sqls as $_sql) {
	        	self::debug($_sql);
	            $_mysqli->query($_sql);
	            array_push($_data, $_mysqli->affected_rows);
	        }

	        if(strlen($_mysqli->error)>0){
	            // $_data = 2;
	            throw new Exception($_mysqli->error);
	        }
	    }
	    return $_data;
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
			    try {
			        $_resultSet = $_mysqli->query($_sql);
			        if($_resultSet !== false){

			            $_fields = array();
			            foreach ($_resultSet->fetch_fields() as $_field){
			                $_fields[$_field->name] = $_field->type;
			            }

			            while ($_row = $_resultSet->fetch_assoc()) {

			                foreach ($_row as $_key => $_value) {
			                    if ($_fields[$_key] < 4 && isset($_value)) {
			                        $_row[$_key] = intval($_value);
			                    } else if ($_fields[$_key] == 4 && isset($_value)) {
			                        $_row[$_key] = floatval($_value);
			                    }
			                }

			                array_push($_tmpData, $_row);
			            }
			            $_resultSet->close();
			        }
			    } catch (Exception $e) {
			    }
			    array_push($_data,$_tmpData);
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

}
