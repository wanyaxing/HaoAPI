#HaoFrame#


##初始化代码##
	*开工

	#本地建项目文件夹部署sftp

	#下载服务器目录

	#清空api目录后复制框架基础文件到api目录
	cd api
	svn co https://wanyaxing@github.com/wanyaxing/AXAPI/trunk/AXAPI/ .
	svn update

	#新建并配置config.php 部署数据库账号密码、部署各混淆代码
	#新建并配置webroot/apitest/conf/apitest_config.js  里的混淆代码 tmpArr.push('secret=apites894987la9sij');

	#上传文件到ftp


	#服务器里提交到项目svn

	#svn 忽略日志文件，但保留文件夹。
	cd api
	svn add logs
	svn commit -m "Adding 'logs'"
	svn propset svn:ignore '*' logs
	svn ci -m 'Adding "logs" and ignoring its contents.'



	＃提交所有文件
	find .  | xargs svn add --force *.*
	svn ci . -m '提交api框架'


	API地址：
	http://api-example.haoxitech.com/apitest/apitest.php
	账号密码：12345679/12345679


##接口范例：user表##

	根据实际需求，更改mhc/sqls/user.sql，补完user表的设计

	使用ssh登录服务器，进入api/mhc/目录，使用create_mhc_with_table_name.php文件根据数据库中的表生成对应的mhc代码文件：
		cd /data/web/haoframe/api/mhc
		/usr/local/php-5.5.10/bin/php create_mhc_with_table_name.php -t user -name 用户

	编辑对应的Controller文件，实现接口逻辑。

	编辑对应的api/webroot/apitest/conf下对应的apitest_config.xxx.js文件，公开接口文档。

	前往http://api.xxx.com/apitest/ 进行接口调试。
