<?php

namespace Web\Controller;

class Collections extends Base
{
	public function index()
	{
		$this->render('collections.html');
	}

	public function data()
	{
		if (isset($_GET['page']) && intval($_GET['page'])>0) {
			$page = intval($_GET['page']);
		}else{
			$page = 1;
		}
		if (isset($_GET['limit']) && intval($_GET['limit'])>0) {
			$limit = intval($_GET['limit']);
		}else{
			$limit = 15;
		}
		// init response data
		$data = ['code' => 0, 'msg' => ''];

		$count = $this->db()->select('COUNT(id)')
					->from('pw_spider')->single();
		$data['count'] = $count;

		$rows = $this->db()
					->select('id,tid,url,author,subject,post_time,spide_time,state,new_tid,new_author,new_post_time,new_url,new_state')
					->from('pw_spider')
					->setPaging($limit)->page($page)
					->order('id DESC')->query();
		if(is_array($rows) && !empty($rows)){
			foreach ($rows as &$row) {
				$row['post_time'] = date('Y-m-d H:i', $row['post_time']);
				$row['spide_time'] = date('Y-m-d H:i', $row['spide_time']);
				$row['new_post_time'] = date('Y-m-d H:i', $row['new_post_time']);
			}
		}
		$data['data'] = $rows;
		echo json_encode($data);
	}
}