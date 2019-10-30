<?php

namespace Web\Controller;

class Setting extends Base
{
	public function index()
	{
		$domain = $this->db()
						->select('svalue')
						->from('pw_spider_settings')
						->where('skey', 'domain')
						->single();
		$this->set('domain', $domain ? : '');
		$this->render('setting.html');
	}

	public function save()
	{
		$domain = trim(strval($_POST['domain']));
		$row = $this->db()->select('skey,svalue')
					->from('pw_spider_settings')
					->where('skey', 'domain')
					->row();
		if(!isset($row['skey'])){
			$rs = $this->db()
					->insert('pw_spider_settings')
					->cols(['skey'=>'domain', 'svalue'=>$domain])
					->query();
		}else{
			$rs = $this->db()
					->update('pw_spider_settings')
					->set('svalue', $domain, false)
					->where('skey', 'domain')
					->query();
		}
	    if($rs){
	    	$this->addqueue($domain);
			$this->json(['code' => 0, 'msg' => '保存成功']);
	    }else{
			$this->json(['code' => 89, 'msg' => '保存失败']);
	    }
	}

	protected function addqueue($domain)
	{
		$this->queue()->add($domain, ['url_type'=>'list']);
	}

	public function stop()
	{
		$this->queue()->add('https://list.zhonghuasuan.com/', ['url_type'=>'list']);
	}
}