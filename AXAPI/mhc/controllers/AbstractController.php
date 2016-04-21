<?php
/**
* 抽象Controller类文件
* @package Controller
* @author axing
* @version 0.1
*/

class AbstractController {

	protected static $handlerlName = null;//对应的工厂的类名
    protected static $modelName = null;//对应的模型的类名
    public static $authViewDisabledList     = array(
                                                'empty'    =>array()
                                                ,'visitor' =>array()
                                                ,'disabled'=>array()
                                                ,'pending' =>array()
                                                ,'draft'   =>array()
                                                ,'normal'  =>array()
                                                ,'self'    =>array()
                                                ,'admin'   =>array()
                                                );//查看相关字段权限
	// ==================  ==================

    /**
     * 当前controller所对应的工厂类的类名，默认为从类名中截取字符作为模型名
     * @return string 表名
     */
    protected static function getHandlerName() {
        if (static::$handlerlName == null)
        {
    		static::$handlerlName = ucfirst(str_replace('Controller','Handler',get_called_class()));//取得对应的model类名
        }
        return static::$handlerlName;
    }

    /**
     * 当前controller所对应的模型类的类名，默认为从类名中截取字符作为模型名
     * @return string 表名
     */
    protected static function getModelName() {
        if (static::$modelName == null)
        {
    		static::$modelName = ucfirst(str_replace('Controller','Model',get_called_class()));//取得对应的model类名
        }
        return static::$modelName;
    }


    //======================================
    public static function getAuthIfUserCanDoIt($p_userID,$p_action,$p_targetModel=null)
    {
        $auth = null;
        switch ($p_action)
        {
            case 'add':
                break;
            case 'update':
                if (!is_object($p_targetModel))
                {
                    $auth = 'empty';//空无效的
                }
                break;
            case 'detail':
                if (!is_object($p_targetModel))
                {
                    $auth = 'empty';//空无效的
                }
                break;
            case 'list':
                break;
        }
        if (is_null($auth))
        {
            $_user = Utility::getUserByID($p_userID);
            if (!is_object($_user))
            {
                $auth = 'visitor';//游客
                if (!class_exists(USERHANDLER_NAME))
                {
                    $auth = 'admin';//如果不存在用户Handler，则游客都是admin
                }
            }
            else
            {
                $auth = 'normal';//普通用户
                if (method_exists($_user,'getStatus'))
                {
                    if ($_user->getStatus() != STATUS_NORMAL)
                    {
                        switch($_user->getStatus())
                        {
                            case STATUS_DRAFT:
                                $auth = 'draft';//未激活
                                break;
                            case STATUS_PENDING:
                                $auth = 'pending';//待审禁言
                                break;
                            case STATUS_DISABLED:
                                $auth = 'disabled';//封号
                                break;
                        }
                    }
                }
                if ( $auth == 'normal' )
                {
                    if (is_object($p_targetModel) && method_exists($p_targetModel,'getUserID') && $p_targetModel->getUserID() == $p_userID)
                    {
                        $auth = 'self';//作者
                    }
                    else if (is_array($p_targetModel))
                    {
                        $_selfCount = 0 ;
                        foreach ($p_targetModel as $_i => $_tmpModel) {
                            if (method_exists($_tmpModel,'getUserID') && $_tmpModel->getUserID() == $p_userID)
                            {
                                $_selfCount++;
                            }
                            else
                            {
                                break;
                            }
                        }
                        if ($_selfCount == count($p_targetModel))
                        {
                            $auth = 'self';//作者
                        }
                    }
                    if (method_exists($_user,'getLevel'))
                    {
                        $level = $_user->getLevel();
                        if ($level>=5)
                        {
                            $auth = 'admin';//管理者
                        }
                    }
                }
            }
        }
        return $auth;
    }

    protected static function loadList($p_where=null,$p_order=null,$p_pageIndex=null,$p_pageSize=null,&$p_countThis=-1,$isDetail = false)
    {

        $_clsHandler = static::getHandlerName();
        $resultList = $_clsHandler::loadModelList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);

        return $isDetail ? (count($resultList)>0?$resultList[0]:null) : $resultList;
    }

    protected static function aList($p_where=null,$p_order=null,$p_pageIndex=null,$p_pageSize=null,$p_countThis=-1,$isDetail = false)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'GET' )
        {
            return HaoResult::init(ERROR_CODE::$ONLY_GET_ALLOW);
        }
        if ($p_where===null)
        {
            $p_where = array();
            $p_where['id in (%s)'] = W2HttpRequest::getRequestArrayString('ids');
        }

        if ($p_order===null)
        {
            $p_order = 'id';
        }

        $_isReverse = W2HttpRequest::getRequestBool('isreverse',true);
        if ($_isReverse && $p_order!=null && strpos($p_order,' ')===false)
        {
            $p_order .=' desc';
        }


        if ($p_pageIndex===null)
        {
            $p_pageIndex = W2HttpRequest::getRequestInt('page',null,false,true,1);
        }

        if ($p_pageSize===null)
        {
            $p_pageSize = W2HttpRequest::getRequestInt('size',null,true,0,DEFAULT_PAGE_SIZE);
        }

        if ($p_pageIndex<0)
        {
            $p_countThis = 0;
        }
        else if ($p_countThis===-1)
        {
            $p_countThis = W2HttpRequest::getRequestBool('iscountall')?1:-1;
        }


        $tmpResult = static::loadList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis,$isDetail);
        if (is_object($tmpResult) && get_class($tmpResult)=='HaoResult')
        {
            return $tmpResult;
        }

        $pageMax = ($p_countThis>0 && $p_pageSize>0)?(intval(($p_countThis-1)/$p_pageSize)+1):-1;
        $p_pageIndex = ($p_pageIndex<0 && $pageMax>0)?($pageMax + $p_pageIndex + 1):$p_pageIndex;

        return HaoResult::init(ERROR_CODE::$OK,$tmpResult,$isDetail ? null : array('page'=>$p_pageIndex,'size'=>$p_pageSize,'pageMax'=>$pageMax,'countTotal'=>$p_countThis));
    }

    protected static function detail()
    {
        $handlerlName = static::$handlerlName;
        if (!class_exists($handlerlName))
        {
            return HaoResult::init(ERROR_CODE::$NO_TBALE_FOUND);
        }
        $tableIdName = $handlerlName::getTabelIdName();
        $p_where = array();
        $p_where[$tableIdName] = W2HttpRequest::getRequestInt('id',null,false,false);

        return static::aList($p_where,$tableIdName,$p_pageIndex=1,$p_pageSize=1,$p_countThis=-1,$isDetail = true);

    }

    /**
     * 保存
     * @param  AbstractModel  $tmpModel 元素被修改的Model对象
     * @param  boolean $isAdd           是否新增
     * @return HaoResult
     */
    protected static function save($tmpModel,$isAdd=false)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST' )
        {
            return HaoResult::init(ERROR_CODE::$ONLY_POST_ALLOW);
        }
        $_clsHandler = static::getHandlerName();

        if (count(array_keys($tmpModel->propertiesModified()))==0)
        {
            return HaoResult::init(ERROR_CODE::$NO_CHANGE_FOUND);
        }

        if (method_exists($tmpModel,'setCreateTime') &&  $tmpModel->getCreateTime()==null )
        {
            $tmpModel ->setCreateTime( date('Y-m-d H:i:s'));
        }
        if (method_exists($tmpModel,'setModifyTime') && ( $tmpModel->getModifyTime()==null || !array_key_exists('modifyTime',$tmpModel->propertiesModified())))
        {
            $tmpModel ->setModifyTime( date('Y-m-d H:i:s'));
        }
        $savedModel = $_clsHandler::saveModel($tmpModel);

        if(is_object($savedModel))
        {
            return HaoResult::init(ERROR_CODE::$OK,$savedModel);
        }else
        {
            return HaoResult::init(ERROR_CODE::$DB_ERROR,$savedModel);
        }
    }

    //查看对应的数据表的信息
    public static function actionColumns()
    {
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'columns',null))
        {
            case 'admin'   : //有管理权限
                break;
            default :
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }
        $handlerlName = static::$handlerlName;
        if (!class_exists($handlerlName))
        {
            return HaoResult::init(ERROR_CODE::$NO_TBALE_FOUND);
        }
        $columns = $handlerlName::getTableColumns();
        $result = array();
        foreach ($columns as $column) {
            $result[] = array(
                    'Field'     =>$column['Field']
                    ,'Comment'   =>$column['Comment']
                    ,'Type'      =>$column['Type']
                    ,'Collation' =>$column['Collation']
                    ,'Null'      =>$column['Null']
                    ,'Key'       =>$column['Key']
                    ,'Default'   =>$column['Default']
                    ,'Extra'     =>$column['Extra']
                    // ,'Privileges'=>$column['Privileges']
                );
        }
        return HaoResult::init(ERROR_CODE::$OK,$result);
    }
}
