apiList.push({
        'title':'七牛:取得一个授权凭证用于直传文件给七牛 (推荐：步骤1）'
        ,'desc':'返回数据中，如果文件已经存在isFileExistInQiniu，则取urlPreview作为七牛存储地址（无须上传文件），否则，取uploadToken上传file文件到uploadServer服务器（步骤2）'
        ,'time':''
        ,'action':'/qiniu/getUploadTokenForQiniu'
        ,'method':'post'
        ,'request':[
           { 'key':'md5'                      ,'type':'string'     ,'required':true ,'test-value':'59143ecd25337d808c93d62d6b97bc80'                         ,'title':'文件的MD5值' ,'desc':'' }
          ,{ 'key':'filesize'                 ,'type':'int  '     ,'required':true ,'test-value':'39312'                         ,'title':'文件大小' ,'desc':'' }
          ,{ 'key':'filetype'                 ,'type':'string'     ,'required':true ,'test-value':'jpg'                         ,'title':'文件后缀如jpg,png等' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'七牛:直传文件到七牛服务器 (推荐：步骤2）'
        ,'desc':'如果上传成功，则返回数据中urlPreview可作为七牛网址使用'
        ,'time':''
        ,'action':'http://upload.qiniu.com'
        ,'method':'post'
        ,'request':[
           { 'key':'token'                     ,'type':'string'     ,'required': true ,'test-value':'PrsvpFPpworWXkrfB3nXdZu1et3GFcFPUKmvizRM:gBxG_Dygt9ne6X4sqc0rwVlPksg=:eyJzY29wZSI6InRlYW1qIiwiZGVhZGxpbmUiOjE0MjI2MTE0NTYsInJldHVybkJvZHkiOiJ7XG4gICAgXCJ1cmxEb3dubG9hZFwiOiBcImh0dHA6XC9cLzd1MnM3MS5jb20xLnowLmdsYi5jbG91ZGRuLmNvbVwvNTkxNDNlY2QyNTMzN2Q4MDhjOTNkNjJkNmI5N2JjODBfMzkzMTIuanBnXCIsXG4gICAgXCJ1cmxQcmV2aWV3XCI6IFwiaHR0cDpcL1wvN3UyczcxLmNvbTEuejAuZ2xiLmNsb3VkZG4uY29tXC81OTE0M2VjZDI1MzM3ZDgwOGM5M2Q2MmQ2Yjk3YmM4MF8zOTMxMi5qcGdcIixcbiAgICBcIm5hbWVcIjogJChmbmFtZSksXG4gICAgXCJzaXplXCI6ICQoZnNpemUpLFxuICAgIFwidHlwZVwiOiAkKG1pbWVUeXBlKSxcbiAgICBcImhhc2hcIjogJChldGFnKSxcbiAgICBcIndcIjogJChpbWFnZUluZm8ud2lkdGgpLFxuICAgIFwiaFwiOiAkKGltYWdlSW5mby5oZWlnaHQpLFxuICAgIFwiY29sb3JcIjogJChleGlmLkNvbG9yU3BhY2UudmFsKVxufSIsInNhdmVLZXkiOiI1OTE0M2VjZDI1MzM3ZDgwOGM5M2Q2MmQ2Yjk3YmM4MF8zOTMxMi5qcGcifQ=='              ,'title':'来自服务器端的uploadToken数据' ,'desc':'' }
          ,{ 'key':'file'                    ,'type':'file'        ,'required': true ,'test-value':''                        ,'title':'本地文件（单个）' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'七牛:上传本地单个文件经服务器中转到七牛'
        ,'desc':'需要服务器进行中转，慢且浪费流量，不推荐，仅供参考'
        ,'time':''
        ,'action':'/qiniu/uploadSingleFile'
        ,'method':'post'
        ,'request':[
           { 'key':'file'                    ,'type':'file'        ,'required': true ,'test-value':''                        ,'title':'本地文件（单个）' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'七牛:上传本地多个文件经服务器中转到七牛'
        ,'desc':'需要服务器进行中转，慢且浪费流量，不推荐，仅供参考'
        ,'time':''
        ,'action':'/qiniu/uploadMultipleFiles'
        ,'method':'post'
        ,'request':[
           { 'key':'files[]'                    ,'type':'file'        ,'required': true ,'test-value':''                        ,'title':'本地文件（单个）' ,'desc':'' }
        ]
      });

apiList.push({
        'title':'七牛:直接抓取网络资源到七牛'
        ,'desc':'同步，所以不要用来抓取太大的文件。'
        ,'time':''
        ,'action':'/qiniu/fetch_url_to_qiniu'
        ,'method':'post'
        ,'request':[
            { 'key':'url'                     ,'type':'string'     ,'required': true ,'test-value':'http://www.baidu.com/img/bd_logo1.png'              ,'title':'目标资源文件网址' ,'desc':'' }
           ,{ 'key':'filename'                ,'type':'string'     ,'required': false ,'test-value':''              ,'title':'保存到文件名，可以不传' ,'desc':'' }
        ]
      });

// apiList.push({
//         'title':'七牛:压缩七牛文件成包'
//         ,'desc':''
//         ,'time':''
//         ,'action':'/qiniu/mkzip'
//         ,'method':'post'
//         ,'request':[

//         ]
//       });

// apiList.push({
//         'title':'七牛:测试些东西'
//         ,'desc':''
//         ,'time':''
//         ,'action':'/qiniu/testSomething'
//         ,'method':'post'
//         ,'request':[
//         ]
//       });

