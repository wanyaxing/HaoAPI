<?php
/**
 * 数据库连接封装类，返回数据库连接，支持代码结束后自动断开链接
 * @package DBTool
 * @author axing
 * @since 1.0
 * @version 1.0
 */
	class DBConnect
	{
		private $conn = NULL;

		//连接数据库
		public function __construct()
		{

			$this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE,defined('DB_PORT')?strlen(DB_PORT):'3306',MYSQL_CLIENT_INTERACTIVE);
			$this->conn->set_charset("utf8");
		}

		public function getCoon()
		{
			return $this->conn ;
		}

		//关闭数据库
		public function close()
		{
			mysqli_close($this->conn);
		}

		// 析构函数，关闭数据库
		public  function __destruct()
		{
			mysqli_close($this->conn);
			// print("\nmysqli_close($this->conn)");//debug
		}
	}
?>
