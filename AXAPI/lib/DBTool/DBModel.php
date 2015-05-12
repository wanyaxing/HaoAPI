<?php
/**
 * 通用的表模型操作类，可以用来处理各种表数据
 * @package DBTool
 * @author axing
 * @since 1.0
 * @version 1.0
 */
// v150317 fix 别名，（和！的情况
// v150304 fix delele 不支持别名
// v150228 fix count 支持别名
// v150210 fix delete 不支持别名
// v150207 增强joinList
// v150128 show full columns
// v150123 show full columns
// v020  fix：insert with null
// v019  fix：insert with single quotes
// v018.6  fix：update with null value
// v018.5  fix：limit
// v018.4  fix：__call
// v018   获得最大页码数
// v017   支持分页为负数
// v016   统一count体验

// define('LOG_LEVEL','debug');//设定输出日志的级别
// define('LOG_PATH','/tmp/log');//设定输出日志的目录
// define('IS_SQL_PRINT',true);//设定是否输出日志

require_once 'DBTool.php';
	/**
	 * 模型类
	 * @author zhu,axing
	 */
class DBModel{
		//数据库中的表名
		public $tableName;
		// 别名
		public $t1;
		//进程缓存
	    private static $cache = array();

		//数据库操作类Database的对象
		// public $db;
		//表结构(字段信息)
		public $fieldList=array();

		//查询条件
		private $where='';
		private $limit='';
		private $field='*';
		private $order='';
		private $group='';
		private $join='';
		private $joinList = array();

		//查询的同时是否预算总数
		private $isCountAll=false;

		//查询分页时，是否预估是否存在下一页
		private $isCheckNextPage = false;
		private $limitPageIndex  = Null;
		private $limitPageSize   = Null;
		private $isNextPageExist = null;

		//是否使用缓存
		private $isUseCache=true;

		//符合条件的查询总数
		private $countNum = null;
		//是否已selected
		private $isSelectQueryRun = false;

		/**
		 * 构造方法
		 * @param $tableName string  数据库表名 (或者 表名 空格 别名) 默认别名为t1
		 */
		public function __construct($tableName='',$conn=null)
		{
			if (strpos($tableName,' ')!==false)
			{
				list ($tableName,$t1) = explode(' ',$tableName,2);
			}
			else
			{
				$t1 = 't1';
			}
			$this->tableName=$tableName;
			$this->t1=$t1;
			// if (isset($conn))
			// {
			// 	$this->db = $conn;
			// }
			// else
			// {
			// 	$this->db = DBTool::getCoon();
			// }
			//获取字段信息
			// $this->getMeta();
		}

	    /**
	     * 初始化方法
	     * @param $tableName string  数据库表名
	     */
	    public static function instance($tableName='') {
	    	$p_cls = __class__ ;
	    	$_o = new $p_cls($tableName);
	        return $_o;
	    }

		//清空前置情况
		public function init()
		{
			$this->where            =  ''     ;
			$this->limit            =  ''     ;
			$this->field            =  '*'    ;
			$this->order            =  ''     ;
			$this->group            =  ''     ;
			$this->join             =  ''     ;
			$this->joinList         =  array();
			$this->isCountAll       =  false  ;
			$this->isCheckNextPage  =  false  ;
			$this->limitPageIndex   =  null   ;
			$this->limitPageSize    =  null   ;
			$this->isNextPageExist  =  null   ;
			$this->countNum 		=  null  ;
			$this->isSelectQueryRun =  false  ;
			return $this;
		}

		//清空前置情况
		// public function new()
		// {
		// 	return $this->init();
		// }

		//获取字段信息
		public function getMeta()
		{
			if (count($this->fieldList)==0)
			{
				$sql='show full columns from ' . $this->tableName;

			    $_data = DBTool::queryData($sql);
			    //先取主键字段
			    foreach ($_data as $_d) {
			    	if ($_d['Key']=='PRI')
			    	{
						$this->fieldList[] = $_d["Field"];
			    	}
			    }
			    //再取其他字段
			    foreach ($_data as $_d) {
			    	if ($_d['Key']!='PRI')
			    	{
						$this->fieldList[] = $_d["Field"];
			    	}
			    }
			}
			return $this->fieldList;
		}


		//设置字段
		public function field($field){
			$fields = is_array($field)?$field:explode(',',$field);
			if ($this->t1 != null)
			{
				foreach ($fields as &$field) {
					if (!preg_match('/^[^\.\s]+\.[^\.\s]+/', $field ) )
					{
						$field = $this->t1 .'.'.$field;
					}
				}
			}
			$this->field= implode(',',$fields);
			return $this;
		}

		//条件限定
		//支持 array('id>0') , array('id > %d'=>0) ,array('id > '=>0);
		public function where($where)
		{
			if(empty($where))
			{
				$this->where='';
				return $this;
			}
			if ( !is_array($where) )
			{
				$where = array($where);
			}

			$arr=array();
			foreach($where as $key=>$value)
			{
				if (strtolower($key) == 'joinlist')
				{
					if (!is_array($value))
					{
						throw new Exception("value of joinlist should be array();", 1);
					}
					foreach ($value as $_joinWhere) {
						if (is_array($_joinWhere) && count($_joinWhere)==2 && is_array($_joinWhere[1]))
						{
							$this->joinWhere($_joinWhere[0],$_joinWhere[1]);
						}
						else
						{
							throw new Exception("value of joinwhere should be array(table2,p_where);", 1);
						}
					}
				}
				else
				{
					DBTool::conditions_push($arr,$key,$value,$this->t1);
				}
			}

			$condition='';
			if(count($arr)>0){
				$condition=' where ' . join(' and ',$arr);
			}

			$this->where=$condition;

			return $this;

		}

		//限制条件
		//注意，如果引入join 多个表，在where等地方也要指明t0还是t2哦
		//格式为 t0 + join + tbl2  + on
		//#t0 left join tbl_user tu on tu.userID = t0.userID
		public function join($join='')
		{
			if ($join!='' && $join[0]!=' ')
			{
				$join = ' '.$join;
			}
			$this->join = $join;
			return $this;
		}

		/**
		 * 插入多条join查询条件
		 * @param  string $p_tableJoined    表名
		 * @param  array  $p_onWhere        关联条件 (注意，查询条件使用 key=t1.key 这样的方式，或 t2.key = table1.key，一定要约束好t1.)
		 * @return [type]                   [description]
		 */
		public function joinWhere($p_tableJoined,$p_onWhere=array())
		{
			if (strpos($p_tableJoined,' ')!==false)
			{
				list ($p_tableJoined,$t2) = explode(' ',$p_tableJoined,2);
			}
			else
			{
				$t2 = 't'.(count($this->joinList)+2);
			}
			$arr=array();
			foreach($p_onWhere as $key=>$value)
			{
				DBTool::conditions_push($arr,$key,$value,$t2);
			}
			$join = ' join '.$p_tableJoined.' '.$t2.' on '.implode(' and ',$arr);
			$this->joinList[] = $join;
			return $this;
		}

		//限制条件//分页从1开始，第一页就是1.
		public function limit($limit,$size=null)
		{
			if (isset($limit))
			{
				if (!empty($size) && $limit!=0)
				{
					$this->limitPageIndex = $limit;
					$this->limitPageSize = $size;
					$this->limit = DBTool::conditions_getLimit($limit,$size);
				}
				else
				{
					if (strpos($limit,'limit')!==false)
					{
						$this->limit = $limit;
					}
					else
					{
						$this->limit = ' limit ' . $limit;
					}
				}
			}
			return $this;
		}

		//排序方式
		public function order($order)
		{
			if ($this->t1 != null)
			{
				if ($order!=null && !preg_match('/^[^\.\s]+\.[^\.\s]+/', $order ) )
				{
					$order = $this->t1 .'.'.$order;
				}
			}
			if (isset($order) && $order!=null)
			{
				$this->order = ' order by ' .$order;
			}
			return $this;
		}

		//分组
		public function group($group)
		{
			if ($this->t1 != null)
			{
				if (!preg_match('/^[^\.\s]+\.[^\.\s]+/', $group ) )
				{
					$group = $this->t1 .'.'.$group;
				}
			}
			if (isset($group) && $group!=null)
			{
				$this->group = ' group by ' . $group;
			}
			return $this;
		}

		//是否查询总数
		public function isCountAll($isOrNot=true)
		{
			$this->isCountAll = $isOrNot;
			return $this;
		}


		//是否预估存在下一页
		public function isCheckNextPage($isOrNot=true)
		{
			$this->isCheckNextPage = $isOrNot;
			return $this;
		}


		//是否使用缓存
		public function isUseCache($isOrNot=true)
		{
			$this->isUseCache = $isOrNot;
			return $this;
		}


		//查询数据
		public function select()
		{
			if ($this->limitPageIndex != 0 && $this->limitPageSize>0)
			{
				$this->limitPageIndex = $this->realPageIndex($this->limitPageIndex , $this->limitPageSize);
				$this->limit($this->limitPageIndex , $this->limitPageSize);;
				if ($this->isCheckNextPage)
				{
					$this->limit = preg_replace_callback(
								        '/limit\s+(\d+)[\s,]+(\d+)/',
								        function ($matches) {
	                                        return 'limit '.$matches[1].','.(intval($matches[2])+1);
								        },
								        $this->limit
								    );
				}
				else
				{
					$this->isCheckNextPage(false);
				}
			}

			$sql= 'SELECT '
					. (($this->isCountAll)?'SQL_CALC_FOUND_ROWS ':'')
					.  $this->field
					.' FROM '
						. $this->tableName
						. ' ' .$this->t1
						. $this->join
						. implode(' ',$this->joinList)
						. $this->where
						. $this->group
						. $this->order
						. $this->limit;

			// var_dump($sql);
			// var_dump($this);exit;
			$list = null;
			$cacheKey = md5($sql);
			if ($this->isUseCache)
			{
				if (array_key_exists($cacheKey, self::$cache))
				{
					// echo 'cache';
					$list = self::$cache[md5($sql)];
				}
			}
			if (!isset($list))
			{
				$list = DBTool::queryData($sql);
				$this->isSelectQueryRun = true;
				self::$cache[md5($sql)] = $list;
			}
			if ($this->isCheckNextPage  && $this->limitPageIndex>0 && $this->limitPageSize>0 )
			{
				if (count($list) > $this->limitPageSize)
				{
					array_pop($list);
					$this->isNextPageExist = true;
				}
				else
				{
					$this->isNextPageExist = false;
				}
				$this->isCheckNextPage(false);
			}
			return $list;
		}

		//查询数据
		public function selectSingle()
		{
			$_data = $this->limit(1)->select();
			if (is_array($_data) && count($_data)>0)
			{
				return $_data[0];
			}
			else
			{
				return null;
			}
		}

		//高级搜索
		/**
		 * 高级搜索，支持模糊查询，支持多重匹配，支持权重分配
		 * @param  [string] $keyword    搜索关键字，可用空格隔开
		 * @param  [type] $fieldArray 列表，数组或单字符串，检索的目标字段。数组时，以先后顺序默认权重；列表时，则以{'字段':10,'字段2':5}来设定字段和对应权重。权重极为分数，总分越高权重越大。
		 * @return [type]             [description]
		 */
		public function search($keyword,$fieldArray)
		{
			// var_export($keyword);exit();
		    if (isset($keyword) && $keyword!=='')
	        {
	            $q = explode(" ",$keyword);//枚举关键字
	            $f = array(); //查询的字段name=产品名,description=产品描述
	            $s = array(); //权重,name字段匹配积分4分，description字段匹配积2分，最后按积分排序
	            if (!is_array($fieldArray))
	            {
	            	$fieldArray = array($fieldArray);
	            }
	            $_isList = ($fieldArray !== array_values($fieldArray));
	            if ($_isList)
	            {
		            foreach ($fieldArray as $key => $value) {
		            	array_push($f,$key);
		            	array_push($s,$value);
		            }
	            }
	            else
	            {
		            foreach ($fieldArray as $key => $value) {
		            	array_push($f,$value);
		            	array_push($s,10-$key);
		            }
	            }

	            $clause = array();
	            $score = array();
	            for($i=0;$i<count($q);$i++){
	                $_c = array();
	                for($j=0;$j<count($f);$j++){
	                    array_push($_c, " (".$f[$j]." LIKE '%".$q[$i]."%') ");
	                    $score[] = " IF(LOCATE('".$q[$i]."', ".$f[$j]."), ".$s[$j].", 0) ";
	                }
	                array_push($clause ,'(' . implode(' or ', $_c) . ')');
	            }

	            if ($this->where=='')
	            {
	            	$this->where =  ' where ' . implode(" and ",$clause);
	            }
	            else
	            {
	            	$this->where .= ' and ' . '('.implode(" and ",$clause).')';
	            }

	            if ($this->field=='')
	            {
	            	$this->field = '('.implode('+',$score).') AS scoreplusinmodel';
	            }
	            else
	            {
	            	$this->field .= ' ,('.implode('+',$score).') AS scoreplusinmodel';
	            }

	            $this->order = 'ORDER BY scoreplusinmodel DESC';

	            // var_dump($this);exit;
	            return $this->select();
	        }
		}

		//查询符合条件的总条数
		public function count($field='*')
		{
			if ($this->countNum ===null)
			{
				if ($this->isCountAll && $this->isSelectQueryRun)
				{
				    $_sqlCount = 'SELECT FOUND_ROWS() as countNum';
				    $this->isSelectQueryRun = false;
				}
				else
				{
					$_sqlCount = 'select count('.$field.') as countNum '
								 .' FROM '
									. $this->tableName
									. ' ' .$this->t1
									. $this->join
									. implode(' ',$this->joinList)
									. $this->where
									. $this->group;
				}
			    $_dataCount = DBTool::queryData($_sqlCount);
			    $this->countNum = intval($_dataCount[0]['countNum']);
			}
			return $this->countNum ;
		}

		//查询符合条件的总条数
		public function countAll()
		{
			return $this->count();
		}

		//是否存在下一页
		public function checkNextPage()
		{
			return $this->isNextPageExist;
		}

		//获得最大页码数
		public function realPageMax($pageSize)
		{
			$pageIndexMax = null;
			if ($pageSize>0)
			{
				$count = $this->count();
				$pageIndexMax = (intval(($count-1)/$pageSize)+1); //总页数
			}
			return $pageIndex;
		}

		//获得真实的pageindex,如传入-1，则返回符合条件的最后一页的页码
		public function realPageIndex($pageIndex,$pageSize)
		{
			if ($pageIndex < 0 && $pageSize>0)
			{
				$pageIndexMax = $this->realPageMax();
				$pageIndex += $pageIndexMax+1; //分页从1开始，第一页就是1.
			}
			return $pageIndex;
		}

		// 聚合函数 魔法变量
		public function __call($method,$param)
		{
			$methods=array('avg', 'max', 'min','sum');
			if (in_array(strtolower($method),$methods))
			{
				$sql="select $method({$param[0]}) as num from {$this->tableName} {$this->t1} {$this->where}";
			    $_dataCount = DBTool::queryData($sql);
			    return $_dataCount[0]['num'];
			}
		}

		//插入
		public function insert($data)
		{

			//拼接SQL语句
			$fieldList=array();
			$valueList=array();
			foreach($data as $key=>$value){
				if (is_int($key) && strpos($value ,'=')!==false)//key是数字，则就当value是xx=xx了
				{
					list ($keyTmp, $valueTmp) = explode ('=', $value, 2);
					$keyTmp = trim($keyTmp);
					$valueTmp = trim($valueTmp);
					$valueTmp = trim($valueTmp,'\'');
				}
				else if ($value===null)
				{
					continue;
				}
				else
				{
					$keyTmp = trim($key);
					$valueTmp = $value;
				}
				//做字段安全过滤
				if(!in_array($keyTmp,$this->getMeta())){
					continue;
				}
				$fieldList[] =  $keyTmp;
				if ($valueTmp=='now()')
				{
					$valueList[] =  DBTool::wrap2Sql($valueTmp,false);//不带引号
				}
				else
				{
					$valueList[] =  DBTool::wrap2Sql($valueTmp,true);//两边带引号
				}
			}

			if (count($fieldList)==0)
			{
				return false;
			}
			else
			{
				//准备执行的SQL语句
			 	$sql= sprintf('INSERT INTO %s (%s) VALUES (%s)',$this->tableName,implode(',', $fieldList),implode(',', $valueList));;

				return	DBTool::executeSql($sql);

			}

		}


		/**
		 * 执行修改，必须指定where才能执行。
		 * @param $data array 需要更新的数据
		 * @param $where array 条件，关联数组
		 */
		public function update($data,$where=null)
		{

			if (isset($where)){
				$this->where($where);
			}
			else if (empty($this->where))
			{
				print("DBModel.php: NO update data without where, if you want to do this, pls use updateAll.");exit();
				return null;
			}
			$arr = array();
			foreach($data as $key=>$value)
			{
				//注意 这里没做安全过滤哦
				if (is_int($key))//key是数字，则就当value是xx=xx了
				{
					DBTool::conditions_push($arr,$key,$value,$this->t1);
				}
				else if (strpos($key,' ')!==false)//key是xx＝
				{
					DBTool::conditions_push($arr,$key,$value,$this->t1);
				}
				else if ($value === null)
				{
					continue;
				}
				else
				{
					// 做字段安全过滤
					if(!in_array($key,$this->getMeta())){
						continue;
					}
					$arr[] =  sprintf('%s = \'%s\'',$key,DBTool::wrap2Sql($value));
				}
			}
			if (count($arr)>0)
			{
				$str= join(' , ', $arr);
				$sql='update '
					         .$this->tableName
					         . ' ' .$this->t1
					         .' set ' . $str
					         . $this->where
					         . $this->limit;
				return	DBTool::executeSql($sql);
			}
			else
			{
				return false;
			}
		}

		/**
		 * 针对全表执行修改，慎用。
		 * @param $data array 需要更新的数据
		 * @param $where array 条件，关联数组
		 */
		public function updateAll($data)
		{
			return $this->update($data,'');
		}

		//删除符合条件的数据。必须指定where才能执行。
		public function delete($where=null)
		{
			if (isset($where)){
				$this->where($where);
			}
			else if (empty($this->where))
			{
				throw new Exception('DBModel.php: NO update data without where, if you want to do this, pls use updateAll.', 1);
			}
			//mysql里，delete 不支持 别名
			$_where = preg_replace_callback('/(^|\s|\()([^\.\s\(]+\.)([^\.\(\s]+)(\s*?[!=\>\<]|\s+?(is|not|in|like|between))/', function($matches){return $matches[1].$matches[3].$matches[4];}, $this->where);

			$sql='DELETE FROM '
				         . $this->tableName
				         . $_where
				         . $this->limit;
			return	DBTool::executeSql($sql);
		}

		//删除全表，慎用。
		public function deleteAll()
		{
			return $this->delete('');
		}


		/* if (!empty(@$_GET['test']))
		{
			// 测试

			// $conn = DbSingleton::get();
			// $modelObj= new Model('1anxin.tbl_source',$conn);
			// $modelObj= new Model('tbl_positionSource',$conn);
			// $datas =  $modelObj->getdata();
			// $datas =  $modelObj->select();
			// $datass=array('sourceName'=>'ppp','sourceCount'=>9999999);
			// $data['positionSourceName']
			// $data['positionSourceCount']
			// $datas =  $modelObj->insert($datass);
			// $datass=array('sourceCount'=>99999699);
			// $where = array('sourceID'=>47,'sourceName'=>'wwwww');
			// $datas =  $modelObj->update($datass,$where);
			// $datas =  $modelObj->count();
			// $datas =  $modelObj->sum(sourceID);
			// echo '<pre>';

			// print_r($datas);
			// var_dump($datas);
			// exit;
		} */
}
