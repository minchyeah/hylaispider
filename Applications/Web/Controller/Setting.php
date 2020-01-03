<?php

namespace Web\Controller;

class Setting extends Base
{
	public function index()
	{
		$this->set('sp_domain', '');
		$this->set('sp_page', '');
		$this->set('domain', '');
		$this->set('badworld', '');
		$last_end_time = '';
		$settings = $this->db()
						->select('skey,svalue')
						->from('pw_spider_settings')
						->where('skey', 'sp_domain')
						->orWhere('skey', 'sp_page')
						->orWhere('skey', 'domain')
						->orWhere('skey', 'start_time')
						->orWhere('skey', 'end_time')
						->orWhere('skey', 'badworld')
						->query();
		if(is_array($settings)){
			foreach ($settings as $set) {
				$this->set($set['skey'], $set['svalue']);
				if($set['skey'] == 'end_time'){
					$last_end_time = $set['svalue'];
				}
			}
		}
		$this->set('start_time', $last_end_time);
		$this->set('end_time', date('Y-m-d H:i:s'));
		$this->render('setting.html');
	}

	public function save()
	{
		$sp_domain = trim(strval($_POST['sp_domain']));
		$sp_rs = $this->dosave('sp_domain', $sp_domain);

		$sp_page = intval($_POST['sp_page']);
		$spp_rs = $this->dosave('sp_page', $sp_page);

		$domain = trim(strval($_POST['domain']));
		$rs = $this->dosave('domain', $domain);

		$badworld = trim(strval($_POST['badworld']));
		$brs = $this->dosave('badworld', $badworld);

		$start_time = trim(strval($_POST['start_time']));
		$srs = $this->dosave('start_time', $start_time);

		$end_time = trim(strval($_POST['end_time']));
		$ers = $this->dosave('end_time', $end_time);

	    if($sp_rs && $spp_rs && $rs && $brs && $srs && $ers){
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

    protected function gd()
    {
        return \GlobalData\Client::getInstance(\Config\GlobalData::$address . ':' . \Config\GlobalData::$port);
    }

	public function addqueue($domain)
	{
		$this->gd()->mintid = 0;
		$this->gd()->maxtid = 0;
		$this->queue()->reset();
		$this->queue()->add($domain, ['url_type'=>'list']);
	}

	public function stop()
	{
		$this->gd()->mintid = 0;
		$this->gd()->maxtid = 0;
		$this->queue()->clean();
		$this->queue()->reset();
	}
}