CREATE TABLE `smsVerify` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `telephone` char(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `userID` int(11) unsigned DEFAULT NULL,
  `verifyCode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '验证码',
  `useFor` tinyint(4) unsigned DEFAULT NULL COMMENT '验证码用途1：注册用 2：登陆用 3：找回密码用',
  `createTime` datetime NOT NULL COMMENT '创建时间',
  `verifyTime` datetime DEFAULT NULL COMMENT '验证时间',
  PRIMARY KEY (`id`),
  KEY `userID` (`userID`),
  CONSTRAINT `smsVerify_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='验证码';
