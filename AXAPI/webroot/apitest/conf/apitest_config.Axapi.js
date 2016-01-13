apiList.push({
  "title":"接口工具:Say Hello"
  ,"desc":""
  ,"time":""
  ,"action":"/axapi/SayHello"
  ,"method":"get"
  ,"request":[
     { "key":"name",       "type":"string",  "title":"name",       "desc":"",    "required":false,  "test-value":"wanyaxing"}
    ,{ "key":"password",   "type":"md5",     "title":"password",   "desc":"",    "required":false,  "test-value":"123456"}
    ,{ "key":"avatar",     "type":"file",    "title":"avatar",     "desc":"",    "required":false,  "test-value":""}
    ,{ "key":"photos[]",   "type":"file",    "title":"avatar",     "desc":"",    "required":false,  "test-value":""}
    ,{ "key":"age",        "type":"int",     "title":"age",        "desc":"",    "required":false,  "test-value":"29"}
    ,{ "key":"content",    "type":"string",  "title":"content",    "desc":"",    "required":false,  "test-value":"see more detail , https://github.com/wanyaxing/apitest.php"}
  ]
});

apiList.push({
  "title":"接口工具:测试首页数据"
  ,"desc":""
  ,"time":""
  ,"action":"/axapi/get_home_table_for_test"
  ,"method":"get"
  ,"request":[
      { "key":"sleep",        "type":"int",     "title":"延迟时间（单位：秒）",        "desc":"page",    "required":false,  "test-value":"0"}
  ]
});

apiList.push({
  "title":"接口工具:查看日志（限管理员）"
  ,"desc":""
  ,"time":""
  ,"action":"/axapi/LoadLogList"
  ,"method":"get"
  ,"request":[
     { "key":"page"                  ,"type":"int"        ,"required": true ,"test-value":"1"                        ,"title":"分页，第一页为1，第二页为2，最后一页为-1" ,"desc":"" }
    ,{ "key":"size"                  ,"type":"int"        ,"required": true ,"test-value":"10"                       ,"title":"分页大小" ,"desc":"" }
    ,{ "key":"type"                 ,"type":"string"      ,"required":true ,"test-value":"error"                         ,"title":"日志类型" ,"desc":"限以下值（access: 访问日志, error:错误日志）" }
    ,{ "key":"datetime"             ,"type":"datetime"      ,"required":false ,"test-value":""                         ,"title":"指定日志所在日期（默认当日）" ,"desc":"" }
  ]
});


apiList.push({
  "title":"接口工具:MHC代码文件快速生成（限管理员）"
  ,"desc":""
  ,"time":""
  ,"action":"/axapi/create_mhc_with_table_name"
  ,"method":"post"
  ,"request":[
     { "key":"-t"                  ,"type":"string"        ,"required": true ,"test-value":""                        ,"title":"表名" ,"desc":"严格大小写" }
    ,{ "key":"-name"               ,"type":"string"        ,"required": false ,"test-value":""                       ,"title":"接口分类（中文）如：用户、设备、留言" ,"desc":" 也可不填，可以取表COMMENT" }
    ,{ "key":"-pri"               ,"type":"string"        ,"required": false ,"test-value":""                       ,"title":"默认取PRI且auto_increment的字段。若取不到，则可以在此处填一个字段，否则就是空了哦" ,"desc":" 也可不填，可以取表COMMENT" }
    ,{ "key":"-rm"                 ,"type":"string"      ,"required":false ,"test-value":""                         ,"title":"是否删除代码文件" ,"desc":"yes / no" }
    ,{ "key":"-update"             ,"type":"string"      ,"required":false ,"test-value":""                         ,"title":"是否更新代码文件" ,"desc":"yes / no" }
  ]
});

apiList.push({
  "title":"接口工具:HaoConnect代码文件快速生成（限管理员）"
  ,"desc":""
  ,"time":""
  ,"action":"/axapi/update_codes_of_hao_connect"
  ,"method":"post"
  ,"request":[
  ]
});
