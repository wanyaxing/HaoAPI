var SECRET_HAX_BROWSER   = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_PC        = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_ANDROID   = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_IOS       = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_WINDOWS   = 'secret=RESET_WHEN_NEW_PROJECT';
var USER_COOKIE_RANDCODE = 'RESET_WHEN_NEW_PROJECT';

var headerList =[
  {
    "key":'Clientinfo'
    ,"type":'string'
    ,"title":'应用信息'
    ,"desc":''
    ,"required":true
    ,"test-value":"teacher"
    ,"click":null
  }
  ,{
    "key":'Clientversion'
    ,"type":'string'
    ,"title":'版本号'
    ,"desc":''
    ,"required":true
    ,"test-value":"1.2"
    ,"click":null
  }
  ,{
    "key":'Devicetype'
    ,"type":'string'
    ,"title":'设备类型'
    ,"desc":'1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备'
    ,"required":true
    ,"test-value":"1"
    ,"click":null
  }
  ,{
    "key":'Devicetoken'
    ,"type":'string'
    ,"title":'Devicetoken'
    ,"desc":'友盟获得的设备token,或 百度推送SDK中获得的buserid'
    ,"required":true
    ,"test-value":""
    ,"click":null
  }
  ,{
    "key":'Requesttime'
    ,"type":'string'
    ,"title":'Requesttime'
    ,"desc":'请求时的时间戳，单位：秒'
    ,"required":true
    ,"test-value":""
    ,"click":function(){
      $(this).siblings("input").val(parseInt(((new Date()).getTime())/1000));
    }
  }
  // ,{
  //   "key":'BuserID'
  //   ,"type":'string'
  //   ,"title":'BuserID'
  //   ,"desc":'百度推送SDK中获得的buserid'
  //   ,"required":true
  //   ,"test-value":""
  //   ,"click":null
  // }
  // ,{
  //   "key":'Channelid'
  //   ,"type":'string'
  //   ,"title":'Channelid'
  //   ,"desc":'百度推送SDK中获得'
  //   ,"required":true
  //   ,"test-value":""
  //   ,"click":null
  // }
  ,{
    "key":'Userid'
    ,"type":'int'
    ,"title":'Userid'
    ,"desc":'当前用户ID，登录后可获得。'
    ,"required":true
    ,"test-value":"0"
    ,"click":null
  }
  ,{
    "key":'Logintime'
    ,"type":'string'
    ,"title":'Logintime'
    ,"desc":'登录时间，时间戳，单位：秒，数据来自服务器'
    ,"required":true
    ,"test-value":""
    ,"click":function(){
      $(this).siblings("input").val(parseInt(((new Date()).getTime())/1000));
    }
  }
  ,{
    "key":'Checkcode'
    ,"type":'string'
    ,"title":'Checkcode'
    ,"desc":'Userid和Logintime组合加密后的产物，用于进行用户信息加密。数据来自服务器'
    ,"required":true
    ,"test-value":""
    ,"click":function(){
      var _headers = getHeaders();
      $(this).siblings("input").val(hex_md5(_headers['Userid']+hex_md5(_headers['Logintime']+USER_COOKIE_RANDCODE)));
    }
  }
  ,{
    "key":'Signature'
    ,"type":'string'
    ,"title":'接口加密校验'
    ,"desc":'取头信息里Clientversion,Devicetype,Requesttime,Devicetoken,Userid,Logintime,Checkcode,Clientinfo,Isdebug  和 表单数据 \n每个都使用key=value（空则空字符串）格式组合成字符串然后放入同一个数组 \n 再放入请求地址（去除http://和末尾/?#之后的字符）后\n 并放入私钥字符串后自然排序 \n 连接为字符串后进行MD5加密，获得Signature \n 将Signature也放入头信息，进行传输。'
    ,"required":true
    ,"test-value":""
    ,"click":function(){
      var tmpArr = [];

      var _headers = getHeaders();

      var _headerKeys = ['Clientversion','Devicetype','Requesttime','Devicetoken','Userid','Logintime','Checkcode'];
      for (var i in _headerKeys)
      {
        if (_headers[_headerKeys[i]]!==null)
        {
          tmpArr.push(_headerKeys[i]+'='+_headers[_headerKeys[i]]);
        }
      }

      var isVersion1 = false;
      $('form').find('[form-type=field]').each(function(){
        var _key = $(this).val();
        if (_key!='' && $(this).parent().siblings().find("input[type=text]").length>0)
        {
          var _val = $(this).parent().siblings().find("input").val();
          tmpArr.push(_key+'='+_val);
          if (_key=='r')
          {
            isVersion1 = true;
          }
        }
      });

      var _link = $('#link_api_url').val();

      if (_link.indexOf('?')>0)
      {
        var _keyValuesStr = _link.replace(/(.*)?\?(.*)(#.*|$)/g,'$2');
        var _keyValues = _keyValuesStr.split('&');
        for (var i in _keyValues)
        {
          tmpArr.push(_keyValues[i]);
        }
      }

      if (isVersion1==false)
      {
        var _headerKeys2 = ['Clientinfo','Isdebug'];
        for (var i in _headerKeys2)
        {
          if (_headers[_headerKeys2[i]]!==null)
          {
            tmpArr.push(_headerKeys2[i]+'='+_headers[_headerKeys2[i]]);
          }
        }
        tmpArr.push('link='+_link.replace(/^http.*?:\/\/(.*?)(\/*[\?#].*$|[\?#].*$|\/*$)/g,'$1'));
      }

      switch(_headers['Devicetype'])
      {
        case '1':
          tmpArr.push(SECRET_HAX_BROWSER);
          break;
        case '2':
          tmpArr.push(SECRET_HAX_PC);
          break;
        case '3':
          tmpArr.push(SECRET_HAX_ANDROID);
          break;
        case '4':
          tmpArr.push(SECRET_HAX_IOS);
          break;
        case '5':
          tmpArr.push(SECRET_HAX_WINDOWS);
          break;
      }
      tmpArr = tmpArr.sort();
      var tmpArrString = tmpArr.join('');
      var tmpArrMd5 = hex_md5( tmpArrString );
      $(this).siblings("input").val(tmpArrMd5);
      console.log(tmpArr,tmpArrString,tmpArrMd5)
    }
  }
  ,{
    "key":'Isdebug'//参数key值
    ,"type":'string'//参数key值类型
    ,"title":'是否输出调试信息'//参数标题
    ,"desc":''//参数描述
    ,"required":true
    ,"test-value":"0"
    ,"click":function(e){
        if (!e.isTrigger)
        {
          var isdebug = $(this).siblings("input").val();
          if (isdebug=='1')
          {
            $(this).siblings("input").val(0);
            $(this).html('DEBUG OFF');
          }
          else
          {
            $(this).siblings("input").val(1);
            $(this).html('DEBUG ON');
          }
        }
    }
  }
];
var apiList = [
      {
        "title":'example:test'
        ,"desc":''
        ,"action":'index.php'
        ,"method":"post"
        ,'request':[
           { 'key':'name',       'type':'string',  'title':'name',       'desc':'',    'required':false,  'test-value':'wanyaxing'}
          ,{ 'key':'password',   'type':'md5',     'title':'password',   'desc':'',    'required':false,  'test-value':'123456'}
          ,{ 'key':'avatar',     'type':'file',    'title':'avatar',     'desc':'',    'required':false,  'test-value':''}
          ,{ 'key':'photos[]',   'type':'file',    'title':'avatar',     'desc':'',    'required':false,  'test-value':''}
          ,{ 'key':'age',        'type':'int',     'title':'age',        'desc':'',    'required':false,  'test-value':'29'}
          ,{ 'key':'content',    'type':'string',  'title':'content',    'desc':'',    'required':false,  'test-value':'see more detail , https://github.com/wanyaxing/apitest.php'
          }
        ]
      }
    ];
