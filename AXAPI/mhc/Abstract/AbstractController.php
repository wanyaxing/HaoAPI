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
    public static function getAuthIfUserCanDoIt($pUserID,$pAction,$pTargetModel=null)
    {
        $auth = null;
        switch ($pAction)
        {
            case 'add':
                break;
            case 'update':
                if (!is_object($pTargetModel))
                {
                    $auth = 'empty';//空无效的
                }
                break;
            case 'detail':
                if (!is_object($pTargetModel))
                {
                    $auth = 'empty';//空无效的
                }
                break;
            case 'list':
                break;
        }
        if (is_null($auth))
        {
            $_user = Utility::getUserByID($pUserID);
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
                    if (is_object($pTargetModel) && method_exists($pTargetModel,'getUserID') && $pTargetModel->getUserID() == $pUserID)
                    {
                        $auth = 'self';//作者
                    }
                    else if (is_array($pTargetModel))
                    {
                        $_selfCount = 0 ;
                        foreach ($pTargetModel as $_i => $_tmpModel) {
                            if (method_exists($_tmpModel,'getUserID') && $_tmpModel->getUserID() == $pUserID)
                            {
                                $_selfCount++;
                            }
                            else
                            {
                                break;
                            }
                        }
                        if ($_selfCount == count($pTargetModel))
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

    protected static function loadList($pWhere=null,$pOrder=null,$pPageIndex=null,$pPageSize=null,&$pCountThis=-1,$isDetail = false)
    {

        $_clsHandler = static::getHandlerName();
        $resultList = $_clsHandler::loadModelList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);

        return $isDetail ? (count($resultList)>0?$resultList[0]:null) : $resultList;
    }

    protected static function aList($pWhere=null,$pOrder=null,$pPageIndex=null,$pPageSize=null,$pCountThis=-1,$isDetail = false)
    {
        //默认list方法里会检测仅GET表单下执行，如果要忽略该检测，请指定对应的Controller::$IGNORE_METHOD_CHECK = true;
        if ($_SERVER['REQUEST_METHOD'] != 'GET' && (!isset(static::$IGNORE_METHOD_CHECK) || !static::$IGNORE_METHOD_CHECK ) )
        {
            return HaoResult::init(ERROR_CODE::$ONLY_GET_ALLOW);
        }
        if ($pWhere===null)
        {
            $pWhere = array();
            $pWhere['id in (%s)'] = W2HttpRequest::getRequestArrayString('ids');
        }

        if ($pOrder===null)
        {
            $pOrder = 'id';
        }

        $_isReverse = W2HttpRequest::getRequestBool('isreverse',true);
        if ($_isReverse && $pOrder!=null && strpos($pOrder,' ')===false)
        {
            $pOrder .=' desc';
        }


        if ($pPageIndex===null)
        {
            $pPageIndex = W2HttpRequest::getRequestInt('page',null,false,true,1);
        }

        if ($pPageSize===null)
        {
            $pPageSize = W2HttpRequest::getRequestInt('size',null,true,0,DEFAULT_PAGE_SIZE);
        }

        if ($pPageIndex<0)
        {
            $pCountThis = 0;
        }
        else if ($pCountThis===-1)
        {
            $pCountThis = W2HttpRequest::getRequestBool('iscountall')?1:-1;
        }


        $tmpResult = static::loadList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis,$isDetail);
        if (is_object($tmpResult) && get_class($tmpResult)=='HaoResult')
        {
            return $tmpResult;
        }

        $pageMax = ($pCountThis>0 && $pPageSize>0)?(intval(($pCountThis-1)/$pPageSize)+1):-1;
        $pPageIndex = ($pPageIndex<0 && $pageMax>0)?($pageMax + $pPageIndex + 1):$pPageIndex;

        return HaoResult::init(ERROR_CODE::$OK,$tmpResult,$isDetail ? null : array('page'=>$pPageIndex,'size'=>$pPageSize,'pageMax'=>$pageMax,'countTotal'=>$pCountThis));
    }

    protected static function detail()
    {
        $handlerlName = static::$handlerlName;
        if (!class_exists($handlerlName))
        {
            return HaoResult::init(ERROR_CODE::$NO_TBALE_FOUND);
        }
        $unsetKey = W2HttpRequest::getUnsetRequest('id', $pAllowBlank = false);
        if ( $unsetKey  !== null)
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array('errorContent'=>'部分参数未提交数据: '.$unsetKey));
        }
        $tableIdName = $handlerlName::getTabelIdName();
        $pWhere = array();
        $pWhere[$tableIdName] = W2HttpRequest::getRequestInt('id',null,false,false);

        return static::aList($pWhere,$tableIdName,$pPageIndex=1,$pPageSize=1,$pCountThis=-1,$isDetail = true);

    }


    public static function actionDetail()
    {
        $_REQUEST['page']=1;
        $_REQUEST['size']=1;
        $tmpResult =  static::actionList();
        if (is_array($tmpResult->results) && isset($tmpResult->results[0]))
        {
            $tmpResult->results = $tmpResult->results[0];
        }
        else
        {
            return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
        }
        return $tmpResult;
    }
    /**
     * 保存
     * @param  AbstractModel  $tmpModel 元素被修改的Model对象
     * @param  boolean $isAdd           是否新增
     * @return HaoResult
     */
    protected static function save($tmpModel,$isAdd=false)
    {
        //默认save方法里会检测仅POST表单下执行，如果要忽略该检测，请指定对应的Controller::$IGNORE_METHOD_CHECK = true;
        if ($_SERVER['REQUEST_METHOD'] != 'POST' && (!isset(static::$IGNORE_METHOD_CHECK) || !static::$IGNORE_METHOD_CHECK ) )
        {
            return HaoResult::init(ERROR_CODE::$ONLY_POST_ALLOW);
        }
        $_clsHandler = static::getHandlerName();

        if (count(array_keys($tmpModel->propertiesModified()))==0)
        {
            return HaoResult::init(ERROR_CODE::$NO_CHANGE_FOUND);
        }

        if (method_exists($tmpModel,'setCreateTime') &&  $tmpModel->propertyValue('createTime')==null )
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

    /** 删除数据 */
    public static function actionDelete()
    {
        $handler = static::$handlerlName;
        $detailModel = $handler::loadModelById(W2HttpRequest::getRequestInt('id'));
        if (is_object($detailModel))
        {
            if (in_array(static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'delete',$detailModel),['admin','self']))
            {
                return HaoResult::init(ERROR_CODE::$OK,$detailModel,$handler::deleteModel($detailModel));
            }
            return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }
        return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
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


    /** 删除数据 */
    public static function actionUnValid()
    {
        $handler = static::$handlerlName;
        $detailModel = $handler::loadModelById(W2HttpRequest::getRequestInt('id'));
        if (is_object($detailModel) && $detailModel->propertyValue('isValid')==1)
        {
            if (in_array(static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'delete',$detailModel),['admin','self']))
            {
                $detailModel->setIsValid(0);
                return static::save($detailModel);
            }
            return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }
        return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
    }


    /*重新排序*/
    public static function actionResetRankValueOfItem()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null) != 'admin')
        {
            return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }

        $handler = static::$handlerlName;

        if (!in_array('rankValue',$handler::getTableDataKeys()))
        {
            return HaoResult::init(ERROR_CODE::$UNKNOWN_API_ACTION);
        }

        $itemID = W2HttpRequest::getRequestInt('item_id');
        $itemModel = $handler::loadModelById($itemID);
        if (!is_object($itemModel))
        {
            return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
        }
        // return HaoResult::init(ERROR_CODE::$DATA_EMPTY);//debug
        $prevItemID = W2HttpRequest::getRequestInt('prev_item_id');//上一个（其rankValue值应该更大）
        $nextItemID = W2HttpRequest::getRequestInt('next_item_id');//下一个（其rankValue值应该较小）

        // return HaoResult::init(ERROR_CODE::$OK,W2Math::getMiddleBetweenNumbers($prevItemID,$nextItemID));//debug
        $newRankValue = $handler::getRankValueBetweenItems($prevItemID,$nextItemID);
        $itemModel->setRankValue($newRankValue);

        return static::save($itemModel);
    }
}
