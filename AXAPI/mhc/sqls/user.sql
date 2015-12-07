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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户';



CREATE TABLE `unionLogin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `unionToken` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '联合登录的唯一识别码',
  `unionType` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '登录方式：2QQ 3微博 4微信',
  `userID` int(11) unsigned DEFAULT NULL COMMENT '用户ID',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `modifyTime` datetime NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`),
  FOREIGN KEY (`userID`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方登录';
