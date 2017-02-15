
apiList.push({
        'title':'微信公众号:获取微信授权用的地址'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_url_for_wx_auth'
        ,'method':'post'
        ,'request':[
           { 'key':'redirect_uri'            ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
           ,{ 'key':'scope'                   ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
           ,{ 'key':'state'                   ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
        ]
      });



apiList.push({
        'title':'微信公众号:通过code换取网页授权access_token并根据token取得用户资料'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_wx_user_info_of_code'
        ,'method':'post'
        ,'request':[
           { 'key':'code'                   ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
        ]
      });


apiList.push({
        'title':'微信公众号:根据access_token拉取用户信息(需scope为 snsapi_userinfo)'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_wx_user_info_of_access_token'
        ,'method':'post'
        ,'request':[
           { 'key':'access_token'                   ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
          ,{ 'key':'openid'                   ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'微信公众号:取得公众号菜单设定'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_menu'
        ,'method':'get'
        ,'request':[
        ]
      });

apiList.push({
        'title':'微信公众号:重设公众号菜单'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/set_menu'
        ,'method':'post'
        ,'request':[
           { 'key':'menu[button][0][name]'                   ,'type':'string'     ,'required':false ,'test-value':'百度'                         ,'title':'' ,'desc':'' }
          ,{ 'key':'menu[button][0][type]'                   ,'type':'string'     ,'required':false ,'test-value':'view'                         ,'title':'' ,'desc':'' }
          ,{ 'key':'menu[button][0][url]'                   ,'type':'string'     ,'required':false ,'test-value':'http://baidu.com'                         ,'title':'' ,'desc':'' }
          ,{ 'key':'menu[button][1][name]'                   ,'type':'string'     ,'required':false ,'test-value':'QQ'                         ,'title':'' ,'desc':'' }
          ,{ 'key':'menu[button][1][type]'                   ,'type':'string'     ,'required':false ,'test-value':'view'                         ,'title':'' ,'desc':'' }
          ,{ 'key':'menu[button][1][url]'                   ,'type':'string'     ,'required':false ,'test-value':'http://qq.com'                         ,'title':'' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'微信公众号:取得js-sdk用的配置参数'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_signature_data_for_j_s'
        ,'method':'get'
        ,'request':[
            { 'key':'url'                       ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'js-sdk所在页的网址' ,'desc':'' }
            ,{ 'key':'timestamp'                     ,'type':'string'     ,'required':false ,'test-value':''                         ,'title':'时间戳' ,'desc':'' }
            ,{ 'key':'noncestr'                   ,'type':'string'     ,'required':false ,'test-value':''                         ,'title':'随机字符串' ,'desc':'' }
        ]
      });
apiList.push({
        'title':'微信公众号:根据openid取得用户属性（包括是否已关注）'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_wx_user_info_of_open_id'
        ,'method':'get'
        ,'request':[
            { 'key':'openid'                       ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'js-sdk所在页的网址' ,'desc':'' }
        ]
      });


apiList.push({
        'title':'微信公众号:取得永久素材列表'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/batchget_material'
        ,'method':'get'
        ,'request':[
            { 'key':'material_type'                       ,'type':'string'     ,'required':true ,'test-value':''                         ,'title':'素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）' ,'desc':'' }
            // ,{ 'key':'offset'                     ,'type':'int'     ,'required':false ,'test-value':'0'                         ,'title':'从全部素材的该偏移位置开始返回，0表示从第一个素材 返回' ,'desc':'' }
            ,{ 'key':'page'                     ,'type':'int'     ,'required':false ,'test-value':'0'                         ,'title':'分页（1表示第一页）' ,'desc':'' }
            ,{ 'key':'count'                   ,'type':'int'     ,'required':false ,'test-value':'20'                         ,'title':'返回素材的数量，取值在1到20之间' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'微信公众号:取得素材详情'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_material'
        ,'method':'get'
        ,'request':[
            { 'key':'mediaid'                     ,'type':'string'     ,'required':false ,'test-value':'TaSZ9A7i6ImHfNunyWoS82qRw_hbAvNlh6PARMh-d7k'                         ,'title':'通过素材管理接口上传多媒体文件，得到的id。' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'微信公众号:当前客服列表'
        ,'desc':''
        ,'time':''
        ,'action':'wechat/get_online_kf_list'
        ,'method':'get'
        ,'request':[
        ]
      });
