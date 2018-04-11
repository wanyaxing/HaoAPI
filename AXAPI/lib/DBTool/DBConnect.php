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

		public function getCoon()
		{
			if (is_null($this->conn))
			{
				$this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE,defined('DB_PORT')?DB_PORT:'3306');
				if (!is_object($this->conn))
				{
					// var_export($this->conn);exit;
					throw new Exception($this->conn, 1);

				}
				$this->conn->set_charset(defined('DB_CHARSET')?DB_CHARSET:'utf8');
			}
			return $this->conn ;
		}

		//关闭数据库
		public function close()
		{
			if (!is_null($this->conn))
			{
				mysqli_close($this->conn);
			}
			$this->conn = null;
		}

		// 析构函数，关闭数据库
		public  function __destruct()
		{
			mysqli_close($this->conn);
			// print("\nmysqli_close($this->conn)");//debug
		}
	}
?>
