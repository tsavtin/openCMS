<?php

class shoppingcart {
	var $expdate = 20; // day
	function addtocart($type,$productid,$quantity,$opt=null){
		global $_COOKIE;
		if($opt){
			$productid = "$productid<||>$opt";
		}
		$key = "$type"."[$productid]";
		list ($org_quantity, $time) = explode(',', $_COOKIE[$type][$productid]);
		if($org_quantity > 0){
			$value = $org_quantity + $quantity;
		} else {
			$value = $quantity;
			$time = time();
		}
		if($value == 0){
			$this->set_item($type,$productid,$value);
		} else {
			$_COOKIE[$type][$productid] = $value.','.$time;
			setcookie($key, $_COOKIE[$type][$productid], time()+$this->expdate*60*60*24);
		}
	}
	function del_item ($type,$productid,$opt=null){
		if($opt){
			$productid = "$productid<||>$opt";
		}
		$this->set_item($type,$productid,'');
	}
	function set_item ($type,$productid,$quantity){
		$key = "$type"."[$productid]";
		if($quantity > 0){
			global $_COOKIE;
			list ($quantity, $time) = explode(',', $_COOKIE[$type][$productid]);
			if(!$_COOKIE[$type][$productid]){
				$time = time();
			}
			$_COOKIE[$type][$productid] = $quantity.','.$time;
			setcookie($key,$quantity.','.$time,0);
		} else {
			unset($_COOKIE[$type][$productid]);
			setcookie($key,'',0);
		}
		
	}
	function list_item ($type){
		$ar = array();
		foreach ($_COOKIE[$type] as $key => $value) {
			$opt = 0;
			if(preg_match('/\<\|\|\>/is', $key)){
				list($id, $opt) = explode('<||>', $key);
				//$id = $id.'.'.$opt;
			} else {
				$id = $key;
			}
			//$id = $key;
			list($quantity, $time) = explode(',', $value);
			$ar[$key] = array(
				'opt' => $opt,
				'quantity' => $quantity,
				'time' => $time,
				'key' => $id
			);
		}
		return $ar;
	}
	function reset ($type){
		global $_COOKIE;
		if(is_array($_COOKIE[$type])){
			foreach($_COOKIE[$type] as $akey => $value){
				$key = "$type"."[$akey]";
				setcookie($key,'0',1);
				unset($_COOKIE[$type][$akey]);
			}
		}
	}
	function get_quantity ($type,$productid,$opt=null){
		global $_COOKIE;
		if($opt){
			$productid = "$productid<||>$opt";
		}
		list ($quantity, $time) = explode(',', $_COOKIE[$type][$productid]);
		return $quantity;
	}
	function total_item($type){
		global $_COOKIE;
		$total = 0;
		if(is_array($_COOKIE[$type])){
			if($type == 'wish'){
				return count($_COOKIE[$type]);
			}
			foreach($_COOKIE[$type] as $akey => $value){
				if($value > 0){
					$total = $total + $_COOKIE[$type][$akey];
				}
			}
		}
		return $total;
	}
}

?>