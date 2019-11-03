<?php

namespace Web\Controller;

class Setting extends Base
{
	public function index()
	{
		$this->set('sp_domain', '');
		$this->set('domain', '');
		$this->set('start_time', '');
		$this->set('end_time', '');
		$settings = $this->db()
						->select('skey,svalue')
						->from('pw_spider_settings')
						->where('skey', 'sp_domain')
						->orWhere('skey', 'domain')
						->orWhere('skey', 'start_time')
						->orWhere('skey', 'end_time')
						->query();
		if(is_array($settings)){
			foreach ($settings as $set) {
				$this->set($set['skey'], $set['svalue']);
				if($set['skey'] == 'end_time'){
					$this->set('start_time', $set['svalue']);
				}
			}
		}
		$this->set('end_time', date('Y-m-d H:i:s'));
		$this->render('setting.html');
	}

	public function save()
	{
		$sp_domain = trim(strval($_POST['sp_domain']));
		$sp_rs = $this->dosave('sp_domain', $sp_domain);

		$domain = trim(strval($_POST['domain']));
		$rs = $this->dosave('domain', $domain);

		$start_time = trim(strval($_POST['start_time']));
		$srs = $this->dosave('start_time', $start_time);

		$end_time = trim(strval($_POST['end_time']));
		$ers = $this->dosave('end_time', $end_time);

	    if($sp_rs && $rs && $srs && $ers){
	    	$this->addqueue($sp_domain);
			$this->json(['code' => 0, 'msg' => '保存成功']);
	    }else{
			$this->json(['code' => 89, 'msg' => '保存失败']);
	    }
	}

	private function dosave($key, $value)
	{
		$row = $this->db()->select('skey,svalue')
					->from('pw_spider_settings')
					->where('skey', $key)
					->row();
		if(!isset($row['skey'])){
			$rs = $this->db()
					->insert('pw_spider_settings')
					->cols(['skey'=>$key, 'svalue'=>$value])
					->query();
		}else{
			$rs = $this->db()
					->update('pw_spider_settings')
					->set('svalue', $value, false)
					->where('skey', $key)
					->query();
		}
		return $rs;
	}

	public function addqueue($domain)
	{
		$this->queue()->clean();
		$this->queue()->add($domain, ['url_type'=>'list']);
	}

	public function stop()
	{
		$this->queue()->clean();
	}
}