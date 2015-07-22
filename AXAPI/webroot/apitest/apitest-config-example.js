var SECRET_HAX_BROWSER   = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_PC        = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_ANDROID   = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_IOS       = 'secret=RESET_WHEN_NEW_PROJECT';
var SECRET_HAX_WINDOWS   = 'secret=RESET_WHEN_NEW_PROJECT';
var USER_COOKIE_RANDCODE = 'RESET_WHEN_NEW_PROJECT';

var headerList =[
  {
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
    ,"test-value":"3"
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
    ,"desc":'取头信息里Clientversion,Devicetype,Requesttime,Devicetoken,Userid,Logintime,Checkcode  和 表单数据 \n每个都使用key=value（空则空字符串）格式组合成字符串然后放入同一个数组 \n 并放入私钥字符串后自然排序 \n 连接为字符串后进行MD5加密，获得Signature \n 将Signature也放入头信息，进行传输。'
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

      $('form').find('[form-type=field]').each(function(){
        var _key = $(this).val();
        if (_key!='' && $(this).parent().siblings().find("input[type=text]").length>0)
        {
          var _val = $(this).parent().siblings().find("input").val();
          tmpArr.push(_key+'='+_val);
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
    "key":'Is_sql_printX'//参数key值
    ,"type":'string'//参数key值类型
    ,"title":'Is_sql_printx'//参数标题
    ,"desc":''//参数描述
    ,"required":true
    ,"test-value":"change the key in left with IS_SQL_PRINT to print log"
    ,"click":null
  }
];
var apiList = [
      {
        "title":'example:test'
        ,"desc":''
        ,"action":'index.php'
        ,"method":"post"
        ,"request":[
          {
            "key":'name'
            ,"type":'string'
            ,"title":'name'
            ,"desc":''
            ,"required":true
            ,"test-value":"wanyaxing"
          }
          ,{
            "key":'password'
            ,"type":'md5'
            ,"title":'password'
            ,"desc":''
            ,"required":true
            ,"test-value":"123456"
          }
          ,{
            "key":'avatar'
            ,"type":'file'
            ,"title":'avatar'
            ,"desc":''
            ,"required":true
            ,"test-value":""
          }
          ,{
            "key":'photos[]'
            ,"type":'file'
            ,"title":'avatar'
            ,"desc":''
            ,"required":true
            ,"test-value":""
          }
          ,{
            "key":'age'
            ,"type":'int'
            ,"title":'age'
            ,"desc":''
            ,"required":true
            ,"test-value":"29"
          }
          ,{
            "key":'content'
            ,"type":'string'
            ,"title":'content'
            ,"desc":''
            ,"required":true
            ,"test-value":"see more detail , https://github.com/wanyaxing/apitest.php"
          }
        ]
      }
    ];
