<?php

namespace Web\Controller;

class Setting extends Base
{
	public function index()
	{
		$this->render('setting.html');
	}

	public function save()
	{
		$domain = trim(strval($_POST['domain']));
		$row = $this->db()->select('skey,svalue')->from('pw_spider_settings')->where('skey', 'domain')->row();
		if(!isset($row['skey'])){
			$this->db()->insert('pw_spider_settings')->cols(['skey'=>'domain', 'svalue'=>$domain])->query();
		}else{
			$this->db()->update('pw_spider_settings')->set('svalue', $domain)->where('skey', 'domain')->query();
		}
		$this->json([]);
	}

	public function addqueue()
	{
		$this->queue()->add('https://list.zhonghuasuan.com/', ['url_type'=>'list']);
	}

	public function stop()
	{
		$this->queue()->add('https://list.zhonghuasuan.com/', ['url_type'=>'list']);
	}
}