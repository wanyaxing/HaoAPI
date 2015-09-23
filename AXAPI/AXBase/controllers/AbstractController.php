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
        if (count($_POST)>0)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'错误，此处不接受POST数据。');
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
        if ($_isReverse && strpos($p_order,' ')===false)
        {
            $p_order .=' desc';
        }


        if ($p_pageIndex===null)
        {
            $p_pageIndex = W2HttpRequest::getRequestInt('page',null,false,true,1);
        }

        if ($p_pageSize===null)
        {
            $p_pageSize = W2HttpRequest::getRequestInt('size',null,false,true,DEFAULT_PAGE_SIZE);
        }

        if ($p_countThis===-1)
        {
            $p_countThis = W2HttpRequest::getRequestBool('iscountall')?1:-1;
        }

        $_clsHandler = static::getHandlerName();
        $resultList = $_clsHandler::loadModelList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);

        return $isDetail ? (count($resultList)>0?$resultList[0]:null) : $resultList;
    }

    protected static function aList($p_where=null,$p_order=null,$p_pageIndex=null,$p_pageSize=null,$p_countThis=-1,$isDetail = false)
    {
        if (count($_POST)>0)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'错误，此处不接受POST数据。');
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
        if ($_isReverse && strpos($p_order,' ')===false)
        {
            $p_order .=' desc';
        }


        if ($p_pageIndex===null)
        {
            $p_pageIndex = W2HttpRequest::getRequestInt('page',null,false,true,1);
        }

        if ($p_pageSize===null)
        {
            $p_pageSize = W2HttpRequest::getRequestInt('size',null,false,true,DEFAULT_PAGE_SIZE);
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
        if (is_array($tmpResult) && array_key_exists('errorCode',$tmpResult))
        {
            return $tmpResult;
        }

        $pageMax = ($p_countThis>0 && $p_pageSize>0)?(intval(($p_countThis-1)/$p_pageSize)+1):-1;
        $p_pageIndex = ($p_pageIndex<0 && $pageMax>0)?($pageMax + $p_pageIndex + 1):$p_pageIndex;

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',$tmpResult,$isDetail ? null : array('page'=>$p_pageIndex,'size'=>$p_pageSize,'pageMax'=>$pageMax,'countTotal'=>$p_countThis));
    }

    protected static function detail()
    {
        $p_where = array();
        $p_where['id'] = W2HttpRequest::getRequestInt('id',null,false,false);

        return static::aList($p_where,$p_order='id',$p_pageIndex=1,$p_pageSize=1,$p_countThis=-1,$isDetail = true);

    }

    //保存
    protected static function save($tmpModel,$isAdd=false)
    {
        if (count($_POST) == 0)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'错误，此处不接受GET数据。');
        }
        $_clsHandler = static::getHandlerName();

        if (count(array_keys($tmpModel->propertiesModified()))==0)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_CHANGE,'您没有作任何修改哦。');
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
            return Utility::getArrayForResults(RUNTIME_CODE_OK,'',$savedModel);
        }else
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_DB,'数据库异常',$savedModel);
        }
    }
}
