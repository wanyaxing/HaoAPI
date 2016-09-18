

apiList.push({
        'title':'环信:我的环信账号'
        ,'desc':'此处只提供账号和密码，不作用户是否存在的验证，如果登录失败，请调用重置环信账号的接口后，重新登录。'
        ,'time':''
        ,'action':'/easemob/get_my_auth_info'
        ,'method':'get'
        ,'request':[
          ]
      });

apiList.push({
        'title':'环信:重置我的环信账号密码'
        ,'desc':'如果用户不存在环信账号，则创建；如果已存在，则重置密码。密码是根据用户最后一次修改用户账号（不是环信账号）密码的时间推算的，所以用户账号修改密码后，建议调用此环信账号的重置接口。'
        ,'time':''
        ,'action':'/easemob/reset_my_auth_info'
        ,'method':'post'
        ,'request':[
          ]
      });

apiList.push({
        'title':'环信:发送消息给用户'
        ,'desc':''
        ,'time':''
        ,'action':'/easemob/push_message'
        ,'method':'post'
        ,'request':[
           { 'key':'type'                  ,'type':'int'        ,'required': true  ,'time':'','test-value':'1'                             ,'title':'1单人' ,'desc':'' }
          ,{ 'key':'content'               ,'type':'string'     ,'required': true  ,'test-value':'content push test'                       ,'title':'内容' ,'desc':'' }
          ,{ 'key':'t'                     ,'type':'id'         ,'required': false ,'test-value':''                                        ,'title':'自定义类型 客户端用t取' ,'desc':'' }
          ,{ 'key':'v'                     ,'type':'string'     ,'required': false ,'test-value':''                                        ,'title':'自定义值 客户端用v取' ,'desc':'' }
          ,{ 'key':'userid'                ,'type':'id'         ,'required': false ,'test-value':'5'                                       ,'title':'用户ID' ,'desc':'' }
          ,{ 'key':'telephone'             ,'type':'string'    ,'required': false ,'test-value':''                                         ,'title':'用户手机号' ,'desc':'' }
        ]
      });
