<?php

namespace Web\Controller;

use \Workerman\Protocols\Http;

class Auth extends Base
{
	/**
	 * 登录页面
	 */
	public function login()
	{
		$this->render('login.html');
	}

	/**
	 * 登录提交处理
	 */
	public function doLogin()
	{
		$username = trim(strval($_POST['username']));
		$password = strval($_POST['password']);
		if($this->checkLogin($username, $password)){
			$this->redirect('/');
		}else{
			$this->redirect('/login/');
		}
	}

    /**
     * 验证登录信息
     * @param  string $username 用户名
     * @param  string $password 密码
     * @return boolean
     */
    protected function checkLogin($username, $password)
    {
        if(\Web\Config\Auth::$username == $username && \Web\Config\Auth::$encrypted == md5(md5($password).\Web\Config\Auth::$salt)){
            $skey = \Web\Config\Auth::$prefix . $this->sessionId;
            $this->gset($skey, $username);
            $this->gexpire($skey, \Web\Config\Auth::$expire);
            return true;
        }else{
            return false;
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $skey = \Web\Config\Auth::$prefix.$this->sessionId;
        $this->gexpire($skey, 1);
        $this->gunset($skey);
        $this->redirect('/');
    }
}