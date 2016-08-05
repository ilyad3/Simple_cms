<?php
/**
* 
*/
class simple_functions
{
	private $db,$login,$id,$pass,$db_host,$db_name,$db_user,$db_pass;

	public $site_url = "letsstudy.ru";

	function __construct($db_host,$db_name,$db_user,$db_pass)
	{
		if (!$this->db) {
			$con = @ new mysqli($db_host, $db_user, $db_pass, $db_name);
			if (!$con->connect_error) {
				$this->db = true;
				$con->set_charset("utf8");
				$this->con = $con;
				return true;
			}else{
				return false;
			}
		}
		session_start();
	}

	private function simple_select($where = null,$from,$find)
	{
		if ($find == NULL) {
			return "001";
		}else{
			if ($where != NULL) {
				$where = "WHERE ".$where;
			}
			$sql = "SELECT ".$find." FROM `".$from."` ".$where."";
			$sql_query = $this->con->query($sql);
			$result_row = $sql_query->fetch_array(MYSQLI_ASSOC);
			return $result_row;
		}
	}

	private function simple_insert($from,$insert,$values)
	{
		if ($insert == NULL) {
			echo "001";
		}else{
			$insert_sql = "INSERT INTO ".$from." (".$insert.") VALUES (".$values.")";
			$insert_query = $this->con->query($insert_sql);
		}	
	}

	private function simple_update($from,$set,$where = null)
	{
		if ($where != NULL) {
			$where = "WHERE ".$where;
		}
		$update_sql = "UPDATE ".$from." SET ".$set." ".$where."";
		$update_query = $this->con->query($update_sql);
	}

	private function simple_delete($from,$where)
	{
		if ($where != NULL) {
			$where = "WHERE ".$where;
		}
		$update_sql = "DELETE FROM `".$from."` ".$where."";
		$update_query = $this->con->query($update_sql);
	}

	public function auth($login,$pass)
	{
		if ($_SESSION['no_login_in_bd_count'] != 3) {
			if ($_SESSION['bad_login_pass_count'] != 5) {
				$pre_check_login = $this->simple_select("login='$login' AND system_status<>0","users","id,pass");
				if ($pre_check_login['id'] < 1) {
					echo "004";
					$_SESSION['no_login_in_bd_count']++;
				}else{
					if ($pre_check_login['pass'] == md5(md5(md5($pass)))) {
						echo "006";
						$_SESSION['simple_session_key'] = md5(md5($login).md5($pass));
						$session = $_SESSION['simple_session_key'];
						$auth_query = $this->simple_update("users","session='$session'","login='$login'");
					}else{
						echo "005";
						$_SESSION['bad_login_pass_count']++;
					}
				}
			}else{
				echo "003";
			}
		}else{
			echo "002";
		}
	}

	public function registration($login,$pass,$email)
	{
		$pre_check_login = $this->simple_select("login='$login' AND system_status=0","users","id");
		if ($pre_check_login['id'] < 1) {
			$delete_query = $this->simple_delete("users","login='$login' AND system_status=0");
			$pre_check_email = $this->simple_select("email='$email'","users","id");
			if ($pre_check_email['id'] < 1) {
				$password = md5(md5(md5($pass)));
				$register_query = $this->simple_insert("users","login,pass,email,system_status,register_code","'$login','$password','$email','0','not_need_recovery_code'");
				$extended_register = 1;
				if ($extended_register == 1) {
					$check_code = substr(md5(md5($login).md5($email)), 0, 6);
					mail($email, "Подтверждение регистрации на сайте $sitename", "Для подтверждения регистрации перейдите по данной ссылке: http://$this->site_url/index?do=check_register_code&code=$check_code");
					$code_query = $this->simple_update("users","register_code='$check_code'","login='$login'");
					echo "010";
				}else{
					$_SESSION['simple_session_key'] = md5(md5($login).md5($pass).date("His"));
					$session = $_SESSION['simple_session_key'];
					$register_query = $this->simple_update("users","system_status='1', session='$session'","login='$login'");
					echo "009";
				}
			}else{
				echo "008";
			}
		}else{
			echo "007";
		}
	}

	public function check_register_code($code)
	{
		$pre_check_code = $this->simple_select("register_code='$code'","users","id,login,pass");
		if ($pre_check_code['id'] < 1) {
			echo "011";
		}else{
			$id = $pre_check_code['id'];
			$_SESSION['simple_session_key'] = md5(md5($pre_check_code['login']).md5($pre_check_code['pass']).date("His"));
			$session = $_SESSION['simple_session_key'];
			$register_code_query = $this->simple_update("users","register_code='succes_code_register', system_status='1', session='$session'","id='$id'");
			echo "012";
		}
	}

	public function recovery_pass($login)
	{
		$pre_check_login = $this->simple_select("login='$login'","users","email,id");
		if ($pre_check_login['id'] < 1) {
			echo "013";
		}else{
			$recovery_code = substr(md5(date("His").md5($login).md5($pre_check_login['id'])), 0, 5);
			$register_query = $this->simple_update("users","pass_recovery_code='$recovery_code'","login='$login'");
			mail($pre_check_login['email'], "восстановление доступа к аккаунту на сайте $sitename", "Для восстановления доступа воспользуйтесь следующей ссылкой http://$this->site_url/index?do=check_recovery_code&code=$recovery_code&login=$login");
			echo "014";
		}
	}

	public function check_recovery_code($code,$login)
	{
		$pre_check_recovery_code = $this->simple_select("pass_recovery_code='$code'","users","login,email");
		if ($pre_check_recovery_code['login'] != $login) {
			echo "015";
		}else{
			$pass = substr(date("H:i:s"), 0, 6);
			$password = md5(md5(md5($pass)));
			$_SESSION['simple_session_key'] = md5(md5($login).md5($pass).date("His"));
			$session = $_SESSION['simple_session_key'];
			$mail = $pre_check_recovery_code['email'];
			mail($mail, "оповещение о смене пароля на сайте $sitename", "Данным письмо оповещаем о том,что был изменён пароль на сайте: http://$this->site_url для вашего аккаунта: $login Ваш временный пароль: $pass Внимание, постарайтесь как можно сменить временный пароль!");
			$pass_recovery_query = $this->simple_update("users","pass='$password', session='$session', pass_recovery_code='not_need_recovery_code'","login='$login'");
			echo "016";
		}
	}

	public function show_news()
	{
		if ($_SESSION['simple_session_key'] == NULL) {
			$news_law = "0";
		}else{
			$session = $_SESSION['simple_session_key'];
			$check_law = $this->simple_select("session='$session'","users","system_status");
			if ($check_law['system_status'] < 1) {
				$news_law = "0";
			}else{
				$news_law = $check_law['system_status'];
			}
		}
		$news_row = $this->simple_select("news_law<='$news_law' AND news_status='1'","news","*");
	}

	function __destruct()
	{
		mysqli_close($this->con);
		$this->db = false;
	}
}
?>
