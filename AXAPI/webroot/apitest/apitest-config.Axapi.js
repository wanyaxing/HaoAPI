
apiList[apiList.length] = {
        'title':'接口工具:查看日志（限管理员）'
        ,'desc':''
        ,'time':'2015-09-26 21:29:16'
        ,'action':'/axapi/LoadLogList'
        ,'method':'get'
        ,'request':[
           { 'key':'page'                  ,'type':'int'        ,'required': true ,'test-value':'1'                        ,'title':'分页，第一页为1，第二页为2，最后一页为-1' ,'desc':'' }
          ,{ 'key':'size'                  ,'type':'int'        ,'required': true ,'test-value':'10'                       ,'title':'分页大小' ,'desc':'' }
          ,{ 'key':'type'                 ,'type':'string'      ,'required':true ,'test-value':'error'                         ,'title':'日志类型' ,'desc':'限以下值（access: 访问日志, error:错误日志）' }
          ,{ 'key':'datetime'             ,'type':'datetime'      ,'required':false ,'test-value':'error'                         ,'title':'指定日志所在日期（默认当日）' ,'desc':'' }
        ]
      };

