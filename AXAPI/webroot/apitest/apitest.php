<?php
    // by wanyaxing@gmail.com
    // version: 151124.1
	  date_default_timezone_set("Asia/shanghai");

    if (count($_GET)>0 || count($_POST)>0)
    {
      header('Content-Type:text/javascript; charset=utf-8');
      print(json_encode(array('$_GET'=>$_GET,'$_POST'=>$_POST,'$_FILES'=>$_FILES,'getallheaders()'=>getallheaders(),'$_SERVER'=>$_SERVER)));
      exit;
    }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title> API：测试工具 </title>
	<link href="lib/bootstrap/bootstrap.css" rel="stylesheet">
  <link href="lib/pretty-json/pretty-json.css" rel="stylesheet">
  <!-- <link href="lib.css?v=150106.4" rel="stylesheet"> -->
	<link href="css/apitest.css" rel="stylesheet">
</head>
<body>
	<div id="main">
  <div id="div_switchgroup">
    <div class="btn-group navbar-btn" id="switch_examples">
    </div>
  </div>
  <div>
    <div  class="col-sm-4">
      <div id="div_apilist">
    		<div id="list_api_btns" class="panel-group" >
        </div>
      </div>
		</div>
		<div id="div_form" class="container-fluid main-content col-sm-4">
			<form role="form" class="form-horizontal">
				 <div>
				    <a class="navbar-brand" href="javascript:;">API URL</a>
				    <div class="btn-group navbar-btn pull-right" id="switch_method">
  					  <button type="button" class="btn btn-default">get</button>
  					  <button type="button" class="btn btn-default btn-primary">post</button>
  					</div>
				</div>
				 <div class="form-group">
					 <div class="col-sm-12">
				    <input type="text" class="form-control" id="link_api_url" placeholder="Enter the url" value="">
				    </div>
				  </div>
          <div id="div_headerfield">
  				  <div class="form-group">
  				  	<a class="navbar-brand" href="javascript:;">Headers</a><button type="button" class="btn btn-default navbar-btn pull-right" id="btn_add_header">+</button>
  				  </div>
            <div>
    				  <div class="form-group" style="display:none;">
    				      <div class="col-sm-4">
    					      <input class="form-control" form-type="header" placeholder="header key" value="">
    				      </div>
    				      <div class="col-sm-8">
                    <div class="input-group">
      					      <input class="form-control"  type="text" placeholder="Enter the value" value="">
                      <div class="input-group-addon" style="display: none;">reload</div>
                    </div>
    				      </div>
    				  </div>
            </div>

  				  <div class="form-group">
  				  	<a class="navbar-brand" href="javascript:;">Payload</a><button type="button" class="btn btn-default navbar-btn pull-right" id="btn_add_field">+</button>
  				  </div>
            <div>
    				  <div class="form-group" style="display:none;">
    				      <div class="col-sm-4">
    					      <input class="form-control" form-type="field"   placeholder="field key" value="">
    				      </div>
    				      <div class="col-sm-8">
    					      <input class="form-control" type="text" placeholder="Enter the value" value="">
    				      </div>
              </div>
  				  </div>
          </div>
				  <div>
            <button type="button" class="btn btn-success pull-right" id="btn_test_url">test</button>
            <div class="pull-right" style="line-height: 50px;"><label><input type="checkbox" id="checkbox_is_autosign" checked>auto reload</label></div>
				  </div>
			</form>
	    </div>
	    <div class="col-sm-4">
			<div id="div_frames" class="row">
					<ul class="nav nav-tabs">
					  <li class="active"><a id="btn_userresume" href="#div_json_view" data-toggle="tab" is-user-page = true><i class="icon-left-indent"></i>view</a></li>
					  <li><a href="#result_txt" data-toggle="tab"><i class="icon-edit"></i>text</a></li>
					</ul>
					<div class="tab-content">
					  <div class="tab-pane active" id="div_json_view"></div>
					  <div class="tab-pane" id="result_txt"><textarea id="textarea_results"></textarea></div>
					</div>
			</div>
	    </div>
  </div>
	</div>
</body>
<script src="lib/jquery/jquery.min.js"></script>
<script src="lib/bootstrap/bootstrap.js"></script>
<script src="lib/md5/md5.js"></script>
<script src="lib/pinyin.js"></script>
<script type="text/javascript" src="lib/pretty-json/underscore-min.js" ></script>
<script type="text/javascript" src="lib/pretty-json/backbone-min.js" ></script>
<script type="text/javascript" src="lib/pretty-json/pretty-json-min.js" ></script>
<script type="text/javascript" src="js/apitest.js" ></script>
<?php
  if (file_exists('apitest-config.js'))
  {
    printf('<script type="text/javascript" src="%s" ></script>','apitest-config.js');
  }
  else if (file_exists(__dir__.'/conf/apitest_config.js'))
  {
    printf('<script type="text/javascript" src="%s" ></script>','conf/apitest_config.js');
  }
  else
  {
    print('<script type="text/javascript">alert("错误，无法引入apitest_config.js文件，请检查。（初始化apitest时，需要将apitest_config-example.js改为apitest_config.js哦。");</script>');
  }
  foreach(  (array)glob(__dir__ . "/apitest-config.*.js" ) as $_jobFile )
  {
    print("\n");
    printf('<script type="text/javascript" src="%s" ></script>',basename($_jobFile));
  }
  foreach(  (array)glob(__dir__ . "/conf/apitest_config.*.js" ) as $_jobFile )
  {
    print("\n");
    printf('<script type="text/javascript" src="conf/%s" ></script>',basename($_jobFile));
  }
  $configJsonFileList = array();
  foreach(  (array)glob(__dir__ . "/conf/apitest_config.*.json" ) as $_jobFile )
  {
    $configJsonFileList[] = 'conf/'.basename($_jobFile);
  }
  if (count($configJsonFileList)>0)
  {
    printf('<script type="text/javascript">var configJsonFileList = %s ;</script>', json_encode($configJsonFileList));
  }
?>

</html>
