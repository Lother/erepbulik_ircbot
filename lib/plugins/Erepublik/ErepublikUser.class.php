<?php

class ErepublikUser
{
	private static $share = null;
	private $db = null;
	private $db_name = "";
	private $db_acc = "";
	private $db_pass = "";
	private $qh = null;
	private $mem = null;
	public static function getInstance(){
		if(static::$share === null){
			static::$share = new ErepublikUser();
		}
		return static::$share;
	}
	public function ErepublikUser(){
		$dsn = "mysql:host=mysql;dbname={$this->db_name}";
		$this->db = new PDO($dsn,$this->db_acc, $this->db_pass);
		$this->mem  = new Memcached();
		$this->mem->addServer('memcached',11211);
		$this->qh = CacheQueueApi::getInstance()->connect();
	}

	public function getUserDataByIRCid($ircid){
		#echo "getUserDataByIRCid($ircid)\n";
		$ircid = strtolower($ircid);
		$name = $this->getUserNameByIRCid($ircid);
		if($name!=false){
			return $this->getUserDataByName($name);
		}
		return false;
		
	}

	public function getUserDataByName($name){
		#echo "getUserDataByName($name)\n";
		$name = strtolower($name);
		$id = $this->getUserIdByName($name);
		if($id !=false){
			return $this->getUserDataById($id);
		}
		return false;
	}

	public function getUserNameByIRCid($ircid){
		#echo "getUserNameByIRCid($ircid)\n";
		$strCacheKey = 'erepublik_ircid_'.$ircid;
		$data = $this->mem->get($strCacheKey);
		if($data === false){
			$sql = 'SELECT * FROM binding where ircid = :ircid';
			$rs=$this->db->prepare($sql);
			$rs->bindValue(':ircid',$ircid, PDO::PARAM_STR);
			$rs->execute();
			$result_arr = $rs->fetch();
			if(is_array($result_arr)){
				$data =  $result_arr['name'];
			}
			$this->mem->set($strCacheKey, $data, $intTTL = strtotime('+1 year'));
		}
		return $data;
	}

	public function getUserIdByName($name){
		#echo "getUserIdByName($name)\n";
		$id = $this->getUserIdByNameWithSQL($name);
		if($id===false){
			$id = $this->getUserIdByNameWithHTML($name);
			if($id !== false){
				$this->upsertUserIdAndName($id,$name);
			}
		}
		return $id;
	}

	public function getUserIdByNameWithHTML($name){
		#echo "getUserIdByNameWithHTML($name)\n";
		$s = curl_init();
		curl_setopt($s,CURLOPT_URL,'https://www.erepublik.com/en/main/search/?q='.urlencode($name)); 
		curl_setopt($s,CURLOPT_RETURNTRANSFER,true); 
		$resp = curl_exec($s); 
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$load_result = $doc->loadHTML($resp);
		$xpath = new DOMXPath($doc);
		$raw = trim($xpath->query("//div[@class='entity']//a//@href")->item(0)->nodeValue);
		if(preg_match('@([0-9]+)$@',$raw,$matches)==1){
			return $matches[1];
		}else{
			return false;
		}
	}

	public function getUserIdByNameWithSQL($name){
		#echo "getUserIdByNameWithSQL($name)\n";
		$strCacheKey = 'erepublik_name_'.$name;
		$data = $this->mem->get($strCacheKey);
		if($data === false){
			$sql = 'SELECT * FROM id_cache where name = :name';
			$rs=$this->db->prepare($sql);
			$rs->bindValue(':name',$name, PDO::PARAM_STR);
			$rs->execute();
			$result_arr = $rs->fetch();
			if(is_array($result_arr)){
				$data =  $result_arr['id'];
			}
			$this->mem->set($strCacheKey, $data, $intTTL = strtotime('+1 year'));
		}
		$QueueKey = 'ErepublikQ_user_'.date('H');
		$this->qh->enqueue($QueueKey,['name'=>$name]);
		return $data;
	}

	public function upsertUserIdAndName($id,$name){
		$sql = 'insert into id_cache (id,name) values (:id,:name) on duplicate key update name=:name , update_time=now()';
		$rs=$this->db->prepare($sql);
		$rs->bindValue(':id',$id, PDO::PARAM_INT);
		$rs->bindValue(':name',$name, PDO::PARAM_STR);
		$rs->execute();
		$strCacheKey = 'erepublik_name_'.$name;
		$this->mem->set($strCacheKey, $id, $intTTL = strtotime('+1 year'));
	}

	public function upsertIRCidAndName($ircid,$name){
		$sql = 'insert binding  (ircid,name) values (:ircid,:name) on duplicate key update name=:name , update_time=now()';
		$rs=$this->db->prepare($sql);
		$rs->bindValue(':ircid',$ircid, PDO::PARAM_INT);
		$rs->bindValue(':name',$name, PDO::PARAM_STR);
		$rs->execute();
		$strCacheKey = 'erepublik_ircid_'.$ircid;
		$this->mem->set($strCacheKey, $name, $intTTL = strtotime('+1 year'));
	}

	public function getUserDataById($user_id){
		#echo "getUserDataById($user_id)\n";
		$strCacheKey = 'erepublik_user_'.$user_id;
		$data = $this->mem->get($strCacheKey);
		if($data === false){
			$data = $this->getUserDataByIdWithCurl($user_id);
			$this->mem->set($strCacheKey, $data, $intTTL = strtotime('+1 year'));
		}
		$QueueKey = 'ErepublikQ_user_'.date('H');
		$this->qh->enqueue($QueueKey,['id'=>$user_id]);
		return $data;
	}

	public function getUserDataByIdWithCurl($user_id){
		#echo "getUserDataByIdWithCurl($user_id)\n";
		$s = curl_init();
		curl_setopt($s,CURLOPT_URL,'https://www.erepublik.com/en/citizen/profile/'.$user_id); 
		curl_setopt($s,CURLOPT_RETURNTRANSFER,true); 
		$resp = curl_exec($s); 
		$info = curl_getinfo ($s);
		if($info['http_code']!==200){
			return false;
		}
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$load_result = $doc->loadHTML($resp);
		$xpath = new DOMXPath($doc);
		$user=[];
		$user['id'] = $user_id;
		$user['name'] = trim($xpath->query("//img[@class='citizen_avatar']/@alt")->item(0)->nodeValue);

		$user['citizenship'] = trim($xpath->query("//div[@class='citizen_info']/a/img/@alt")->item(1)->nodeValue);
		$user['location'] = trim($xpath->query("//div[@class='citizen_info']/a/@title")->item(1)->nodeValue);
		$user['status'] = ($xpath->query("//div[@class='citizen_state']/div[@class='is']/span")->length > 0) ?'死亡人口' :''; 
		$user['birth'] = strtotime($xpath->query("//div[@class='citizen_second']//p")->item(1)->nodeValue);
		$user['level'] = intVal($xpath->query("//strong[@class='citizen_level']")->item(0)->nodeValue);
		$raw = $xpath->query("//strong[@class='citizen_level']/@title")->item(0)->nodeValue;
		$user['exp'] = call_user_func(function () use ($raw){
			$raw = preg_replace('@,| @','',$raw);
			preg_match('@([0-9]+)/([0-9]+)@',$raw,$matches);
			return intVal($matches[1]);
		});
		$user['1st_friend'] = trim($xpath->query("//div[@class='citizen_activity']/ul/li/a/img/@alt")->item(0)->nodeValue);

		$raw = $xpath->query("//img[@class='citizen_avatar']/@style")->item(0)->nodeValue;
		$user['avatar'] = call_user_func(function () use ($raw){
			preg_match('@url\(([^)]+)\)@',$raw,$matches);
			if(strlen($matches[1])>0){
				$url = "https://" . $matches[1];
			}else{
				$url = "Avatar not found";
			}
			return $url;
		});

		$raw = $xpath->query("//span[@class='military_box_info mb_bottom']")->item(0)->nodeValue; 
		$user['div'] = 'D' . intVal(preg_replace('@Division@','',$raw));

		$raw = $xpath->query("//span[@class='military_box_info mb_bottom']")->item(1)->nodeValue; 
		$user['tank']['strength'] = floatVal(preg_replace('@,@','',$raw));
		$raw = $xpath->query("//span[@class='rank_name_holder']//a")->item(0)->nodeValue; 
		$user['tank']['rank_level'] = call_user_func(function () use ($raw)
		{
			$LegendStar = [
				"I"		=> 1,	"II"	=> 2,	"III"	=> 3,	"IV"	=> 4,	"V"	=> 5,
				"VI"	=> 6,	"VII"	=> 7,	"VIII"	=> 8,	"IX"	=> 9,	"X"	=> 10,
			];
			$rank = [
				"Recruit"	=> 1,	"Private"		=> 2,	"Corporal"		=> 6,
				"Sergeant"	=> 10,	"Lieutenant"	=> 14,	"Captain"		=> 18,
				"Major"		=> 22,	"Commander"		=> 26,	"Lt Colonel"	=> 30,
				"Colonel"	=> 34,	"General"		=> 38,	"Field Marshal"	=> 42,
				"Supreme Marshal"	=> 46,	"National Force"	=> 50,
				"World Class Force"	=> 54,	"Legendary Force"	=> 58,
				"God of War"		=> 62,	"Titan"				=> 66,
				"Legends of"		=> 70,
			];
			$rank_text = preg_replace('@ [IVX]*[*]*$@','',$raw); 
			if(preg_match('@^Legends of@',$rank_text)){
				preg_match('@^.* ([IXV]+)$@',$raw,$matches);
				$add_level = $matches[1];
				return intval($rank['Legends of'] + $LegendStar[$add_level] - 1);
			}else{
				preg_match('@^.* ([*]+)$@',$raw,$matches);
				$add_level = $matches[1];
				return intval($rank[$rank_text] + strlen($add_level));
			}
		});
		$raw = $xpath->query("//span[@class='rank_numbers']")->item(0)->nodeValue; 
		list($user['tank']['point'],$user['tank']['next']) = call_user_func(function () use ($raw){
			$raw = preg_replace('@,@','',$raw);
			$raw = explode('/',$raw);
			return [intVal($raw[0]),intVal($raw[1])-intVal($raw[0])];
		});

		$raw = $xpath->query("//span[@class='military_box_info mb_bottom']")->item(2)->nodeValue; 
		$user['air']['strength'] = floatVal(preg_replace('@,@','',$raw));
		$raw = $xpath->query("//span[@class='rank_name_holder']//a")->item(1)->nodeValue; 
		$user['air']['rank_level'] = call_user_func(function() use ($raw)
		{
			$rank = [
				"Airman"			=> 1,	"Airman 1st Class"		=> 2,	"Senior Airman"		=> 8,
				"Staff Sergeant"	=> 14,	"Aviator"				=> 20,	"Flight Lieutenant" => 26,
				"Squadron Leader"	=> 32,	"Chief Master Sergeant" => 38,	"Wing Commander"	=> 44,
				"Group Captain"		=> 50,
			];
			$rank_text = preg_replace('@ [*]*$@','',$raw); 
			preg_match('@^.* ([*]+)$@',$raw,$matches);
			$add_level = $matches[1];
			return intval($rank[$rank_text] + strlen($add_level));
		});
		$raw = $xpath->query("//span[@class='rank_numbers']")->item(1)->nodeValue; 
		list($user['air']['point'],$user['air']['next']) = call_user_func(function () use ($raw){
			$raw = preg_replace('@,@','',$raw);
			$raw = explode('/',$raw);
			return [intVal($raw[0]),intVal($raw[1])-intVal($raw[0])];
		});
		$raw = $xpath->query("//div[@class='citizen_military']//div[@class='stat']//small//strong")->item(0)->nodeValue; 
		list($user['tp']['point'],$user['tp']['next']) = call_user_func(function () use ($raw){
			$raw = preg_replace('@,@','',$raw);
			$raw = explode('/',$raw);
			return [intVal($raw[0]),intVal($raw[1])-intVal($raw[0])];
		});

		$raw = $xpath->query("//ul[contains(@class,'achiev')]//li"); 
		$user['medals'] = call_user_func(function() use ($raw,$xpath)
		{
			$arr = [];
			for($i=0;$i<$raw->length;$i++){
				$count	= intVal($xpath->query(".//div[@class='counter']",$raw->item($i))->item(0)->nodeValue);
				if($count > 0){
					$name	= trim($xpath->query(".//div[contains(@class,'hinter')]//strong",$raw->item($i))->item(0)->nodeValue);
					$arr[$name] = $count;
				}
			}
			return $arr;
		});
		return $user;
  }
}
