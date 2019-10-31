<?php

namespace Web\Controller;

class Users extends Base
{
	public function index()
	{
		$this->render('users.html');
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
					->from('pw_spider_authors')->single();
		$data['count'] = $count;

		$rows = $this->db()
					->select('id,author_id,author,sp_author,add_time,state')
					->from('pw_spider_authors')
					->setPaging($limit)->page($page)
					->order('id DESC')->query();
		if(is_array($rows) && !empty($rows)){
			foreach ($rows as &$row) {
				$row['add_time'] = date('Y-m-d H:i', $row['add_time']);
			}
		}
		$data['data'] = $rows;
		$this->json($data);
	}

	public function save()
	{
		$id = intval($_POST['id']);
		$author = trim(strval($_POST['author']));
		$sp_author = trim(strval($_POST['sp_author']));
		$row = $this->db()
					->select('uid,username')
					->from('pw_members')
					->where('username', $author)
					->row();
		if(!isset($row['uid'])){
			$this->json(['code' => 99, 'msg' => '发布用户不存在']);
		}
		
	    $data = [
	        'author_id' => $row['uid'],
	        'author' => $row['username'],
	        'sp_author' => $sp_author,
	        'state' => 1
	    ];
	    if($id > 0){
	    	$rs = $this->db()->update('pw_spider_authors')->setCols($data)->where('id', $id)->query();
	    }else{
	    	$data['add_time'] = time();
	    	$rs = $this->db()->insert('pw_spider_authors')->cols($data)->query();
	    }


	    if($rs){
			$this->json(['code' => 0, 'msg' => '保存成功']);
	    }else{
			$this->json(['code' => 89, 'msg' => '保存失败']);
	    }
	}
}



