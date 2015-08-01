var currentUrl;
var xhrTestingApi = null;

Date.prototype.format = function(format)
{
    var o =
    {
    "M+" : this.getMonth()+1, //month
    "d+" : this.getDate(), //day
    "h+" : this.getHours(), //hour
    "m+" : this.getMinutes(), //minute
    "s+" : this.getSeconds(), //second
    "q+" : Math.floor((this.getMonth()+3)/3), //quarter
    "S" : this.getMilliseconds() //millisecond
    }

    if(/(y+)/.test(format))
    {
        format = format.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
    }

    for(var k in o)
    {
        if(new RegExp("("+ k +")").test(format))
        {
            format = format.replace(RegExp.$1, RegExp.$1.length==1 ? o[k] : ("00"+ o[k]).substr((""+ o[k]).length));
        }
    }
    return format;
}
/**
 * 自定义pretty-json展开方案，Local结尾的key不展开
 */
function expandAll(node)
{
  // console.log(node);
  for (var i in node.childs)
  {
    var child = node.childs[i];
    if(child instanceof PrettyJSON.view.Node)
    {
      if (!child.path.match(/.*Local$/))
      {
        child.show();
        expandAll(child);
      }
    }
  }
}

function searchApiKey(_this,_isSelect)
{
    if (_isSelect)
    {
      _this.select();
    }
    var _keyType = $(_this).val();
    if (_keyType=='')
    {
      return;
    }
    $(_this).addClass("btn-primary").siblings().removeClass("btn-primary");
    $(this).attr('keytype',_keyType);
    setTimeout(function(){
      if($(this).attr('keytype') == $(_this).val())
      {
        filterApiList($(_this).val());
      }
    },500);
}

function changeKeyType(_this)
{
    $(_this).addClass("btn-primary").siblings().removeClass("btn-primary");
    var _keyType = $(_this).html();
    filterApiList(_keyType);
}

function filterApiList(_keyType)
{
    _keyType = _keyType.toLowerCase();
    if (_keyType=='所有')
    {
      $('#list_api_btns').children().show();
    }
    else
    {
      $('#list_api_btns').children().each(function(){
        var htmlText = $(this).html().replace(/<.*?>/g,'').toLowerCase();
        if (htmlText.indexOf(_keyType)>=0)
        {
          $(this).show(0);
        }
        else
        {
          $(this).hide(0);
        }
      });
    }
}

function getHeaders()
{
  var _headers = {};
  $('form').find('[form-type=header]').each(function(){
    var _key = $(this).val();
    if (_key!='')
    {
      _headers[_key] = $(this).parent().siblings().find("input").val();
    }
  });
  return _headers;
}
function getValues()
{
  var _getValues = [];
  $('form').find('[form-type=field]').each(function(){
    var _key = $(this).val();
    if (_key!='' && _key.indexOf('[]')<0)
    {
      var _val = $(this).parent().siblings().find("input").val();
      _getValues.push(_key+'='+encodeURIComponent(_val));
    }
  });
  return _getValues;
}
function getPosts()
{
  var _data = {};
  $('form').find('[form-type=field]').each(function(){
    var _key = $(this).val();
    if (_key!='' && _key.indexOf('[]')<0)
    {
      var _val = $(this).parent().siblings().find("input").val();
      _data[_key] = _val;
    }
  });
  return _data;
}

function reFormGroup(_formType,_formRequest)
{
  var _formGroup = null;
  $('form').find('[form-type='+_formType+']').each(function(){
    if ($(this).val()==_formRequest['key'])
    {
      _formGroup = $(this).closest(".form-group");
    }
  });
  if (_formGroup == null)
  {
    var _firstNode = $('form').find('[form-type='+_formType+']').first().closest(".form-group");
    $('form').find('[form-type='+_formType+']').last().closest(".form-group").after(_firstNode.clone(true));
    _formGroup = $('form').find('[form-type='+_formType+']').last().closest(".form-group");
  }

  _formGroup.attr('is-required',_formRequest['required']?'true':'false').trigger('mouseenter').trigger('mouseleave').attr('title',_formRequest['title']+"<br/>"+_formRequest['desc']).attr('data-html',"true").tooltip().show();
  var _inputs = _formGroup.find('input');
  _inputs.eq(0).val(_formRequest['key']);
  if (_formRequest['type']=='file')
  {
    _inputs.eq(1).replaceWith('<input type="file"'+(_formType=='field'?' name="'+_formRequest['key']+'"':'')+(_formRequest['key'].indexOf('[]')>0?' multiple="multiple"':'')+'/>')
  }
  else if (_formRequest['type']=='md5')
  {
    _inputs.eq(1).val(hex_md5(_formRequest['test-value'])).change(function(){
      $(this).val(hex_md5($(this).val() ));
    });
  }
  else
  {
    if (_formRequest['test-value']=='')
    {
      if (_formRequest['type']=='datetime')
      {
        _formRequest['test-value'] = (new Date().format('yyyy-MM-dd hh:mm:ss'));
      }
      else if (_formRequest['type']=='date')
      {
        _formRequest['test-value'] = (new Date().format('yyyy-MM-dd'));
      }
    }
    _inputs.eq(1).val(_formRequest['test-value']);
  }
  if (_formType=='field')
  {
    _inputs.eq(1).attr('name',_formRequest['key']);
  }
  if (_formRequest['click'])
  {
    _inputs.eq(1).siblings('.input-group-addon').show().unbind().bind('click',_formRequest['click']);
  }
  else
  {
    _inputs.eq(1).closest('.input-group').removeClass('input-group');
  }
  $('#div_headerfield').scrollTop(99999);
}

function changePlan (p_plan) {
    p_plan = decodeURI(p_plan);
    var planList = p_plan.split('|');
    console.log(planList);
    switch(planList[0]){
        case '#api':
            var i = parseInt(planList[1]);
            if (i>=0)
            {
              if (apiList[i])
              {
                $('#btn_api_title_'+i).trigger('click');
              }
            }
            break;

        case 'help':
            break;
    }
}

function setCurrentUrl(_href)
{
  self.location = _href;
  currentUrl = self.location.href;
}

function reFormApi(i)
{
  var _api = apiList[i];
  document.getElementsByTagName('title')[0].innerHTML = _api['title']+'　　/　　API:测试工具';
  setCurrentUrl('#api'+'|'+i+'|'+apiList[i]['title']);
  if (_api['action'].indexOf('http')<0)
  {
    _api['action'] = window.location.protocol +'//'+ window.location.host + (_api['action'].indexOf('/')==0?'':window.location.pathname.replace(/\/[^\/]*$/g,'')+'/') + _api['action'];
    _api['action'] = _api['action'].replace(/\/[^\/]+\/\.\.\//g,'/');
  }
  $('#link_api_url').val(_api['action']);

  if (_api['method']=='get')
  {
    $('#switch_method button').eq(0).trigger('click');
  }
  else if (_api['method']=='post')
  {
    $('#switch_method button').eq(1).trigger('click');
  }
  $('form').find('[form-type=field]').each(function(i){
    if (i>0)
    {
      $(this).closest(".form-group").remove();
    }
  });

  for (var j in _api['request'])
  {
    if (_api['request'][j]['required'] || _api['request'][j]['test-value']!='')
    {
      reFormGroup('field',_api['request'][j]);
    }
  }
  // $('form').find('.input-group-addon').trigger('click');
}

function reFormGroupApi(i,j)
{
  reFormGroup('field', apiList[i]['request'][j]);
}

function reFormHeader()
{
  for (var j in headerList)
  {
    reFormGroup('header',headerList[j]);
  }
}

$(function(){
    var _listNode = $('#list_api_btns');
    var _keyTypes = {'所有':1};
    for (var i in apiList)
    {
      var _api = apiList[i];
      var _keyString = '';
      _keyString = '';
      if (_api['desc']!='')
      {
        _keyString = _api['desc'].replace('\n','<br/>').replace(/[\n\r]/g,'<br/>')+'<br/><br/>';
      }
      _keyString += 'url    : '+_api['action']+'<br/>';
        if (_api['request'].length>0)
        {
          _keyString += '<table class="table table-striped">';
          _keyString += '<tr><th>字段名</th><th>必须</th><th>格式</th><th>字段描述</th><th>测试值</th></tr>';
          for(var j in _api['request'])
          {
            _keyString+='<tr onclick="reFormGroupApi('+i+','+j+');"><td>'+_api['request'][j]['key']+'</td><td>'+(_api['request'][j]['required']?'是':'否')+'</td><td>'+_api['request'][j]['type']+'</td><td><span>'+_api['request'][j]['title']+'</span><span style="color:red;">'+ _api['request'][j]['desc']+'</span></td><td>'+_api['request'][j]['test-value']+'</td></tr>'
          }
          _keyString += '</table>';
        }
      var _panelString ='<div class="panel panel-default">\
          <div class="panel-heading">\
            <h4 class="panel-title">\
              <a id="btn_api_title_'+i+'" href="#api'+'|'+i+'|'+encodeURI(apiList[i]['title'])+'" data-toggle="collapse" data-parent="#list_api_btns" data-target="#collapseDiv'+i+'" onclick="reFormApi('+i+');">'+_api['title']+'</a>\
              <span class="span-method">'+_api['method']+'</span>\
            </h4>\
          </div>\
          <div id="collapseDiv'+i+'" class="panel-collapse collapse">\
            <div class="panel-body">'+_keyString+'</div>\
          </div>\
        </div>';
      _listNode.append(_panelString);
      if (_api['title'].match(/[：:]/g))
      {
        var _keyType = _api['title'].replace(/([：:]).*/g,'$1');
        _keyTypes[_keyType]=1;
      }
    }
    _listNode.collapse();

    reFormHeader();

    for(var _keyType in _keyTypes)
    {
      $('#switch_examples').append('<button type="button" class="btn btn-default" onclick="changeKeyType(this);">'+_keyType+'</button>');
    }
    $('#switch_examples').append('<input id="input_search" type="text" class="btn btn-default" onkeyup="searchApiKey(this);" onclick="searchApiKey(this,1);" placeholder="search"/>');

    $('#switch_examples').children().eq(0).trigger('click');

    $('#btn_add_header').click(function(){
      var _firstNode = $('form').find('[form-type=header]').first().closest(".form-group");
      $('form').find('[form-type=header]').last().closest(".form-group").after(_firstNode.clone(true));
      $('form').find('[form-type=header]').last().closest(".form-group").show();
    });
    $('#btn_add_field').click(function(){
      var _firstNode = $('form').find('[form-type=field]').first().closest(".form-group");
      $('form').find('[form-type=field]').last().closest(".form-group").after(_firstNode.clone(true));
      $('form').find('[form-type=field]').last().closest(".form-group").show();
    });
    $('.form-group').bind({
      mouseenter:function(){
        if ($(this).attr('is-required') && $(this).attr('is-required')=='true')
        {

        }
        else
        {
          if ($(this).find('.btn_del').length==0)
          {
            $(this).find('[form-type]').before('<div class="btn_del" > x </div>').prev().click(function(){
              $(this).closest(".form-group").trigger('mouseleave').remove();
            });
          }
          $(this).find('.btn_del').show();
        }
      }
      ,mouseleave:function(){
        if ($(this).attr('is-required'))
        {

        }
        else
        {
          $(this).find('.btn_del').hide();
        }
      }
    });
    $('form').find('[form-type=field]').bind({change:function(){
        $(this).parent().siblings().find('input').attr('name',$(this).val());
    }});

    $('#switch_method button').click(function(){
      $(this).addClass("btn-primary").siblings().removeClass("btn-primary");
    });

    $('#textarea_results')[0].onchange = function()
    {
      var json,data;
      data = $(this).val();
      try{ json = JSON.parse(data); }
      catch(e){
          $('#div_json_view').html(data);
          $('#div_frames>ul>li').eq(1).trigger("click");
          return;
      }

      var node = new PrettyJSON.view.Node({
          el:$('#div_json_view'),
          data: json,
          dateFormat:"DD/MM/YYYY - HH24:MI:SS"
      });
      console.log(node);
      // node.expandAll();
      expandAll(node);
    }

    $('#btn_test_url').click(function(){

        if ($('#checkbox_is_autosign').prop('checked'))
        {
          $('form').find('.input-group-addon').trigger('click');//每次都自动重算
        }

        if (xhrTestingApi){xhrTestingApi.abort();}

        var _headers = getHeaders();

        var _link = $('#link_api_url').val();

        if (_link=='')
        {
          alert('API URL NO FOUND');
          $('#link_api_url').focus();
          return false;
        }

        var _method = $('#switch_method .btn-primary').html().toLowerCase();

        var _data=null;
        if (_method=='get')
        {
          var _getValues = getValues();
          _link = _link + (_link.indexOf('?')>0?'&':'?') + _getValues.join('&');
        }
        else if (_method=='post')
        {
             // _data = getPosts();
             _data = new FormData($('form')[0]);
        }

        $('#textarea_results').val('waiting....');
        $('#div_json_view').html('waiting....');
        // console.log(_getValues)
        // console.log(_data)
        xhrTestingApi = $.ajax({

            type: _method,

            url: _link,

            headers: _headers,

            data:_data,

            dataType:"text",

            error: function(XHR,textStatus,errorThrown) {
                console.log(XHR,textStatus,errorThrown);
                $('#textarea_results').val(XHR.responseText);
                alert ("XHR="+XHR+"\ntextStatus="+textStatus+"\nerrorThrown=" + errorThrown);
            },

            success: function(data,textStatus) {
                  xhrTestingApi = null;
                  $('#textarea_results').val(data);
                  $('#textarea_results').trigger("change");
                  $('#div_frames>ul>li').eq(0).trigger("click");
            },

            //Options to tell jQuery not to process data or worry about content-type.
            cache: (_method=='get'),
            contentType: false,
            processData: false
        });

    });
    // //url 监控
    setInterval(function(){
        var _currentUrl = self.location.href;
        if (currentUrl != _currentUrl) {
            console.log(currentUrl,_currentUrl)
            currentUrl = _currentUrl;
            var _plan = currentUrl.replace(/^.*#/g,'#');
            changePlan(_plan);
        };
    }, 100);
});
