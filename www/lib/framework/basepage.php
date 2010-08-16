<?php

require_once("config.php");
require_once(WWW_DIR."/lib/users.php");
require_once(SMARTY_DIR.'Smarty.class.php');

class BasePage 
{
	public $title = '';
	public $content = '';
	public $head = '';
	public $body = '';
	public $meta_keywords = '';
	public $meta_title = '';
	public $meta_description = '';    
	public $page_template = ''; 
	public $smarty = '';
	public $userdata = array();
	public $serverurl = '';
		
	function BasePage()
	{			
		@session_start();
	
		if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase'))
		{
            foreach($_GET as $k => $v) $_GET[$k] = stripslashes($v);
            foreach($_POST as $k => $v) $_POST[$k] = stripslashes($v);
            foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes($v);
        }	
		
		$this->smarty = new Smarty();
		
		$this->smarty->template_dir = WWW_DIR.'templates/';
		$this->smarty->compile_dir  = SMARTY_DIR.'templates_c/';
		$this->smarty->config_dir   = SMARTY_DIR.'configs/';
		$this->smarty->cache_dir    = SMARTY_DIR.'cache/';				

		$this->smarty->assign('page',$this);
		if (isset($_SERVER["SERVER_NAME"]))
		{
			$this->serverurl = (isset($_SERVER["HTTPS"]) ? "https://" : "http://").$_SERVER["SERVER_NAME"].($_SERVER["SERVER_PORT"] != "80" ? ":".$_SERVER["SERVER_PORT"] : "").WWW_TOP.'/';
			$this->smarty->assign('serverroot', $this->serverurl);
		}

		$users = new Users();
		if ($users->isLoggedIn())
		{
			$this->userdata = $users->getById($users->currentUserId());
			$this->smarty->assign('userdata',$this->userdata);	
			$this->smarty->assign('loggedin',"true");
			if (isset($_COOKIE['sabnzbd_'.$users->currentUserId().'__apikey']) && $_COOKIE['sabnzbd_'.$users->currentUserId().'__apikey'] != "")
				$this->smarty->assign('sabintegrated',"true");
			if ($this->userdata["role"] == Users::ROLE_ADMIN)
				$this->smarty->assign('isadmin',"true");	
		}
		else
		{
			$this->smarty->assign('isadmin',"false");	
			$this->smarty->assign('loggedin',"false");	
		}
	}    
	
	public function addToHead($headcontent) 
	{			
		$this->head = $this->head."\n".$headcontent;
	}	
	
	public function addToBody($attr) 
	{			
		$this->body = $this->body." ".$attr;
	}		
	
	public function render() 
	{
		$this->smarty->display($this->page_template);
	}
	
	public function isPostBack()
	{
		return (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST");	
	}
	
	public function show404()
	{
		header("HTTP/1.1 404 Not Found");
		die();
	}
	
	public function show403($from_admin = false)
	{
		$redirect_path = ($from_admin) ? str_replace('/admin', '', WWW_TOP) : WWW_TOP;
		header("Location: $redirect_path/login");
		die();
	}
}
?>
