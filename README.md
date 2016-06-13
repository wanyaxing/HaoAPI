--------------------------------------------------------

<center>             HaoAPI开工             </center>

--------------------------------------------------------

##在本地电脑里进行代码环境配置##

* 在本地建项目文件夹部署sftp

* 下载服务器目录到本地文件夹

* 在本地电脑里，清空api同等级目录HaoConnect目录后复制基础文件到该目录
```
cd HaoConnect
svn co https://wanyaxing@github.com/wanyaxing/HaoConnect/trunk/HaoConnect/ .
svn update
```

* 在本地电脑里，清空api目录后复制框架基础文件到api目录
```
cd api
svn co https://wanyaxing@github.com/wanyaxing/AXAPI/trunk/AXAPI/ .
svn update
```

* 新建并配置config.php 部署数据库账号密码、部署各混淆代码

* 新建并配置webroot/apitest/conf/apitest_config.js  里的混淆代码 tmpArr.push('secret=apites894987la9sij');

* 上传本地的文件到sftp服务器。


* 在服务器里，提交文件到项目svn。
```
// svn 忽略日志文件，但保留文件夹。
cd api
svn add logs
svn commit -m "Adding 'logs'"
svn propset svn:ignore '*' logs
svn ci -m 'Adding "logs" and ignoring its contents.'


// 提交所有文件
find .  | xargs svn add --force *.*
svn ci . -m '提交api框架'
```


* 可以访问API文档了：
[http://api-example.haoxitech.com/apitest](http://api-example.haoxitech.com/apitest)
    * 账号密码：12345679/12345679


##其他注意事项

--------------------建表-----------------------
* 连上数据库建些基本表
```mysql
CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `telephone` varchar(11) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户手机号',
  `username` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '用户名',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `password` char(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `level` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '0: 未激活用户 1：普通用户 5：普通管理员  9：超级管理员',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '0: 已删除  1: 正常 2: 封号  3：禁言',
  `lastLoginTime` datetime DEFAULT NULL COMMENT '最后一次登录时间',
  `lastPasswordTime` datetime DEFAULT NULL COMMENT '最后一次密码修改时间',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `modifyTime` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `telephone` (`telephone`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';


CREATE TABLE `example` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userID` int(11) unsigned DEFAULT NULL COMMENT '用户ID',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `sex` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '性别：0未知 1男 2女',
  `vip` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT 'VIP等级 0: 普通用户 1：VIP',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图片',
  `photos` text COLLATE utf8mb4_unicode_ci COMMENT '照片墙（逗号隔开）',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '名称',
  `category` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类',
  `captchaCode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '验证码',
  `tags` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '标签',
  `serveStartTime` datetime NOT NULL COMMENT '服务开始时间',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '地址',
  `lng` double NOT NULL DEFAULT '0' COMMENT 'GPS经度',
  `lat` double NOT NULL DEFAULT '0' COMMENT 'GPS纬度',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '简介',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `modifyTime` datetime NOT NULL COMMENT '修改时间',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1' COMMENT '0: 删除  1: 正常 2: 草稿  3：待审',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='测试';
```
