<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
	//----------table:banner----------//

	public function getBanner()
	{
		$m = M();
		$res = $m->query('select * from banner');
		if($res){echo json_encode($res);};
	}

	//----------table:account----------//

	public function getSession()
	{
		$code = I('code');
		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=".C('appid')."&secret=".C('secret')."&js_code=".$code."&grant_type=authorization_code";
		$res = file_get_contents($url);
		$json_res = json_decode($res, true);
		$openid = $json_res['openid'];
		$session_key = $json_res['session_key'];
		$user_session = md5($openid.$session_key);
		echo $user_session;
		$m = M();
		$count = $m->query('select count(*) n from account where wx_openid =\''.$openid.'\'');
		$data['wx_openid'] = $openid;
		$data['session_key'] = $session_key;
		$data['user_session'] = $user_session;
		if($count[0]['n']==0)
		{
			M('account')->add($data);
		} 
		elseif ($count[0]['n']==1)
		{
			M('account')->save($data);
		}
	}

	public function getNickName()
	{
		$session = I('session');
		$m = M();
		$data = $m->query('select * from account where user_session =\''.$session.'\'');
		if ($data){echo $data[0]['nickname'];};
	}

	public function setNickName()
	{
		$nickName = I('nickName');
		$session = I('session');
		$m = M();
		$data = $m->query('select * from account where user_session =\''.$session.'\'');
		$data[0]['nickName'] = $nickName;
		$condition['user_session'] = $session;
		$res=M('account')->where($condition)->save($data[0]);
		if ($res){echo 'success';};
	}

	public function getBalance()
	{
		$session = I('session');
		$m = M();
		$balance = $m->query('select balance from account where user_session =\''.$session.'\'');
		echo json_encode($balance);
	}

	public function chargeBalance()
	{
		$chargeAmount = I('chargeAmount');
		$session = I('session');
		$m = M();
		$data = $m->query('select * from account where user_session =\''.$session.'\'');
		$data[0]['balance'] = (float)$data[0]['balance'] + (float)$chargeAmount;
		$condition['user_session'] = $session;
		$res=M('account')->where($condition)->save($data[0]);
		if ($res){echo 'success';};
	}

	public function purchaseItem()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$user = $m->query('select * from account where user_session =\''.$session.'\'')[0];
		$item = $m->query('select * from products where product_id =\''.$id.'\'')[0];
		if((float)$user['balance']>=(float)$item['price'])
		{
			$data['wx_openid'] = $user['wx_openid'];
			$data['balance'] = (float)$user['balance']-(float)$item['price'];
			$res=M('account')->save($data);
			if ($res){echo 20000;}
			$order['wx_openid'] = $user['wx_openid'];
			$order['product_id'] = $id;
			$order['count'] = 1;
			$order['price'] = $item['price'];
			$order['order_id'] = time().str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
			$res=M('order')->add($order);
		}
		else if ((float)$user['balance']<(float)$item['price']){echo 20001;}
	}

	//----------table:products----------//

	public function getProductsBy()
	{
		$type = I('type');
		$id = I('id');
		$m = M();
		if($type){$res = $m->query('select * from products where type =\''.$type.'\'');}
		else if($id){$res = $m->query('select * from products where product_id =\''.$id.'\'');}
		echo json_encode($res);
	}

	//----------table:cart----------//

	public function addToCart()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$rec = $m->query('select * from cart where wx_openid =\''.$openid.'\' and product_id=\''.$id.'\'');
		if(!$rec)
		{
			$data['wx_openid'] = $openid;
			$data['product_id'] = $id;
			$data['count'] = 1;
			$res=M('cart')->add($data);
			if($res){echo 'success';};
		} 
		else
		{
			$condition['wx_openid'] = $openid;
			$condition['product_id'] = $id;
			$data['count'] = (int)$rec[0]['count'] + 1;
			$res=M('cart')->where($condition)->save($data);
			if($res){echo 'success';};
		}
	}

	public function getCart()
	{
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$data = $m->query('select c.product_id,c.count,c.add_time,c.selected,p.* from cart c inner join products p where c.wx_openid =\''.$openid.'\' and c.product_id = p.product_id order by add_time desc');
		echo json_encode($data);
	}

	public function totalPrice()
	{
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$price = $m->query('select sum(p.price*c.count) price from cart c inner join products p where c.wx_openid =\''.$openid.'\' and c.selected = 1 and c.product_id = p.product_id');
		echo json_encode($price);
	}

	public function setCount()
	{
		$id = I('id');
		$count = I('count');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$rec = $m->query('select * from cart where wx_openid =\''.$openid.'\' and product_id=\''.$id.'\'');
		if($rec){
			$condition['wx_openid'] = $openid;
			$condition['product_id'] = $id;
			$data['count'] = $count;
			$res=M('cart')->where($condition)->save($data);
			if($res){echo 'success';};
		}
	}

	public function deleteProduct()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$condition['wx_openid'] = $openid;
		$condition['product_id'] = $id;
		$res=M('cart')->where($condition)->delete();
		if($res){echo 'success';};
	}

	public function changeSelect()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		if($id!='true' && $id!='false')
		{
			$rec = $m->query('select * from cart where wx_openid =\''.$openid.'\' and product_id=\''.$id.'\'');
			$condition['wx_openid'] = $openid;
			$condition['product_id'] = $id;
			if($rec)
			{
				if($rec[0]['selected'])
				{
					$data['selected'] = 0;
					$res=M('cart')->where($condition)->save($data);
					if($res){echo 'success';};
				}
				else
				{
					$data['selected'] = 1;
					$res=M('cart')->where($condition)->save($data);
					if($res){echo 'success';};
				}
			}
		}
		else
		{
			$condition['wx_openid'] = $openid;
			if($id=='true')
			{
				$data['selected'] = 1;
				$res=M('cart')->where($condition)->save($data);
				if($res){echo 'success';};
			}
			else
			{
				$data['selected'] = 0;
				$res=M('cart')->where($condition)->save($data);
				if($res){echo 'success';};
			}
		}
	}

	public function purchaseSelected()
	{
		$session = I('session');
		$m = M();
		$user = $m->query('select * from account where user_session =\''.$session.'\'')[0];
		$selectedItems = $m->query('select * from cart where wx_openid =\''.$user['wx_openid'].'\' and selected = true');
		$total_price = $m->query('select sum(p.price*c.count) price from cart c inner join products p where c.wx_openid =\''.$user['wx_openid'].'\' and c.selected = 1 and c.product_id = p.product_id')[0]['price'];
		if(!count($selectedItems)){echo 20002;}
		else if((float)$user['balance']>=(float)$total_price)
		{
			$data['wx_openid'] = $user['wx_openid'];
			$data['balance'] = (float)$user['balance']-(float)$total_price;
			$res=M('account')->save($data);
			if ($res){echo 20000;}
			$order_id = time().str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
			for($i=0;$i<count($selectedItems);$i++)
			{
				$order[$i]['wx_openid'] = $user['wx_openid'];
				$order[$i]['product_id'] = $selectedItems[$i]['product_id'];
				$order[$i]['count'] = $selectedItems[$i]['count'];
				$order[$i]['price'] = $m->query('select p.price*c.count price from cart c inner join products p where c.wx_openid =\''.$user['wx_openid'].'\' and c.product_id = p.product_id and c.product_id=\''.$selectedItems[$i]['product_id'].'\'')[0]['price'];
				$order[$i]['order_id'] = $order_id;
				$addres[$i]=M('order')->add($order[$i]);
				$delres[$i]=M('cart')->where($selectedItems[$i])->delete();
			}
		}
		else if ((float)$user['balance']<(float)$total_price){echo 20001;}
	}

	//----------table:order----------//

	public function getOrder()
	{
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$res = $m->query('select o.id,o.product_id,o.count,o.price total_price,o.purchase_time,o.order_id,o.statu,p.title,p.desc,p.price,p.picurl from `order` o inner join products p where o.wx_openid =\''.$openid.'\' and o.deleted = false and o.product_id = p.product_id order by id desc');
		echo json_encode($res);
	}

	public function deleteOrder()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$condition['wx_openid'] = $openid;
		$condition['id'] = $id;
		$data['deleted'] = 1;
		$res=M('order')->where($condition)->save($data);
		if($res){echo 'success';}
	}

	public function getOrderBy()
	{
		$id = I('id');
		$session = I('session');
		$m = M();
		$openid = $m->query('select wx_openid from account where user_session =\''.$session.'\'')[0]['wx_openid'];
		$res = $m->query('select o.product_id,o.count,o.price total_price,o.purchase_time,o.order_id,o.statu,p.title,p.desc,p.price,p.picurl from `order` o inner join products p where o.wx_openid =\''.$openid.'\' and o.id =\''.$id.'\' and o.product_id = p.product_id');
		echo json_encode($res);
	}

	//----------original----------//

	public function index()
    {
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }

}