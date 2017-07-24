<?php
require_once 'lib/plugins/Erepublik/ErepublikUser.class.php';

class erep_user extends plugin {

	private $erep_acc = "";
	private $erep_pass = "";
	private $gapi_key = "";
	function parse_opt($opt,$str){
		$arr = preg_split('@ @', $str, NULL, PREG_SPLIT_NO_EMPTY);
		$arr2 = preg_split('@@', $opt, NULL, PREG_SPLIT_NO_EMPTY);
		$arr3=[];
		foreach($arr2 as $i=>$v){
			if($v==":"){
				$arr3[count($arr3)-1]['r']=true;
			}else{
				$arr3[]=['k'=>$v,'r'=>false];
			}
		}
		$opt=[];
		foreach($arr3 as $i=>$v){
			$opt[$v['k']]=[
				'has'=>true,
				'req'=>$v['r'],
			];
		}
		$out=[];
		for($i=0 ; $i<count($arr) ; $i++ ){
			if(preg_match('@^-(.)$@',$arr[$i],$matches)){
				$key = $matches[1];
				if($opt[$key]['has']){
					$out[$key] = true;
					if($opt[$key]['req']==true){
						$i++;
						$out[$key]=$arr[$i];
					}
				}else{
					return "invalid option: ".$arr[$i];
				}
			}elseif(preg_match('@^-(.)(.+)@',$arr[$i],$matches)){
				$key = $matches[1];
				if($opt[$key]['has']){
					$out[$key] = true;
					if($opt[$key]['req']==true){
						$out[$key]=$matches[2];
					}
				}else{
					return "invalid option: ".$arr[$i];
				}
			}else{
				$out['str'] .= " " . $arr[$i];
			}
		}
		$out['str'] = trim($out['str']);
		return $out;
	}

	function getUser($opt,$m){
		if(!isset($opt['i'])){
			if(strlen($opt['str'])>0){
				$user = ErepublikUser::getInstance()->getUserDataByName($opt['str']);
				if($user===false){
					return "unknown citizen name ". $opt['str'];
				}
			}else{
				$user = ErepublikUser::getInstance()->getUserDataByIRCid($m['fr']['na']);
				if($user===false){
					$user = ErepublikUser::getInstance()->getUserDataByName($m['fr']['na']);
					if($user===false){
						return "unknown citizen ircid ". $m['fr']['na'];
					}
				}
			}
		}else{
			$user = ErepublikUser::getInstance()->getUserDataById(intval($opt['i']));
			if($user===false){
				return "unknown citizen id ". intval($opt['i']);
			}
		}
		return $user;
	}

	function __construct(){}

	function __destruct(){}

	public function getPlugins()
	{
		$this->getPlugin('base');
	}

	public function trigger($type, $data){
	}

	public function message($m){
		
	}

	public function command($m){
		switch ($m['cmd']) 
		{
			case '@lp':
			case '@lookup':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$msg = sprintf("%s[%d]%s Lv%d(%dXP) 國籍 %s 位於 %s 力量%d 感知%d 軍階Lv%d AirLv%d %s 第一個朋友%s 天數%s\n"
							,$user['name']
							,$user['id']
							,$user['status']
							,$user['level']
							,$user['exp']
							,$user['citizenship']
							,$user['location']
							,$user['tank']['strength']
							,$user['air']['strength']
							,$user['tank']['rank_level']
							,$user['air']['rank_level']
							,$user['div']
							,$user['1st_friend']
							,floor((time()-$user['birth'])/86400)
						);
					}else{
						$msg = $user;
					}
				}else{
					 $msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@do':
			case '@donate':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$msg = sprintf("%s的捐贈頁面 https://www.erepublik.com/en/economy/donate-items/%d\n"
							,$user['name']
							,$user['id']
						);
					}else{
						$msg = $user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@link':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$msg = sprintf("%s的個人頁面 https://www.erepublik.com/en/citizen/profile/%d\n"
							,$user['name']
							,$user['id']
						);
					}else{
						$msg = $user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@ava':
			case '@avatar':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$msg = sprintf("%s的個人頭像 %s\n"
							,$user['name']
							,$user['avatar']
						);
					}else{
						$msg = $user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@medal':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$arrMedal=[];
						foreach($user['medals'] as $k=>$v){
							$arrMedal[] = "$k:$v";
						}
						
						$msg = sprintf("%s的獎牌們 %s\n"
							,$user['name']
							, join(', ',$arrMedal)
						);
					}else{
						$msg = $user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@ln':
			case '@reg':
			case '@bind':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					if(strlen($opt['str'])===0){
						$msg = $m['cmd'] . " to who?";
					}else{
						$user = $this->getUser($opt,NULL);
						if (is_array($user)){
							ErepublikUser::getInstance()->upsertIRCidAndName($m['fr']['na'],$user['name']);
							$msg = sprintf("%s 已連結到%s[%d]\n"
								,$m['fr']['na']
								,$user['name']
								,$user['id']
							);
						}else{
							$msg = $user;
						}
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@epic':
				$msgs = $this->getEpicBattle($cache = false);
				if(count($msgs)==0){
					$msgs[] = "not found epic battle";
				}
				foreach($msgs as $msg){
					$this->base->put($m['re'], 'PRIVMSG', $msg);
					usleep(1000000);
				}
				break;

			case '@bh':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('d:o:t:',$cmd);
				$help = "@bh -d 1-4A -o number[KMGT] [-t 60]";
				$msgs = [];
				if(isset($opt['o'])){
					if(preg_match('@^([0-9]+)([kmgt])$@',strtolower($opt['o']),$matches)){
						$symbol=[
							"t" => 1000000000000,
							"g" => 1000000000,
							"m" => 1000000,
							"k" => 1000
						];
						$damge = intval($matches[1]) * $symbol[$matches[2]];
					}else{
						$damge = intval($opt['o']);
					}
				}else{
					$this->base->put($m['re'], 'PRIVMSG', $help);
					break;
				}
				if(isset($opt['d']) && in_array($opt['d'],[1,2,3,4,'A'])){
					$div = $opt['d'];
				}else{
					$this->base->put($m['re'], 'PRIVMSG', $help);
					break;
				}
				if(isset($opt['t'])){
					$time = $opt['t'];
				}else{
					$time = 60;
				}
				$hash = substr(md5(time()),0,6);
				$this->getBattleHero(($div == 'A')?11:$div,$damge,$time,$m['re'],$hash);
				$msg = sprintf("[%s] Seach Over\n",$hash,$div,number_format($damge),$time);
				$this->base->put($m['re'], 'PRIVMSG', $msg);

				break;

			case '@battle':
				$battleId = trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				if(preg_match('@^[0-9]+$@',$battleId)){
					$msgs = $this->getBattle($battleId);
				}else{
					$msgs = ["invalid battle id : ".$battleId];
				}
				foreach($msgs as $msg){
					$this->base->put($m['re'], 'PRIVMSG', $msg);
				}

				break;

			case '@ow':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('i:',$cmd);
				if(is_array($opt)){
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$ow = ErepublikUser::getInstance()->getUserDataById(1772310);
						$lv_bonus = ($ow['level']<100) ? 1.0:1.1;
						$owinf = (((($ow['tank']['rank_level']-1)/20.0)+0.3) * (($ow['tank']['strength'] / 10.0 ) + 40)) * $lv_bonus ;
						$lv_bonus = ($user['level']<100) ? 1.0:1.1;
						$inf = (((($user['tank']['rank_level']-1)/20.0)+0.3) * (($user['tank']['strength'] / 10.0 ) + 40)) * $lv_bonus ;
						$msg = sprintf("%s(rank %d str %s)的空手影響力%s 約等於%s個老王(%s)\n"
							,$user['name']
							,$user['tank']['rank_level']
							,number_format($user['tank']['strength'])
							,number_format($inf)
							,number_format($inf/$owinf,5)
							,number_format($owinf)
						);
					}else{
						$msg = $user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@fc':
				$cmd =  trim(preg_split('@ @',$m['me']['pl'],2)[1]);
				$opt = $this->parse_opt('s:r:ebBNto:f:L:Ai:Rh',$cmd);
				if(is_array($opt)){
					if(isset($opt['h'])){
						$msg = "@fc [-s strength] [-r rank] [-e] [-b|-B] [-N|-t|-o damage|-f fights] [-L LegendsRank 1-20] [-A] [-R] [-i userNo|username]";
						$this->base->put($m['re'], 'PRIVMSG', $msg);
						$msg = "e:加上NE, -b:+50%, -B:+100%, -N:下一軍階, -t:下一TP章, -o:輸出傷害, -f:次數 -L:傳奇軍階 -A:Aircraft -R:Rank+10%";
						$this->base->put($m['re'], 'PRIVMSG', $msg);
						break;
					}
					$user = $this->getUser($opt,$m);
					if (is_array($user)){
						$booster = 1.0;
						$natural_enemy = 1.0;
						$mode = NULL;
						$damge = NULL;
						$fight = 1;
						$msg = NULL;
						$legends = 1.0;

						if(isset($opt['s'])){
							if(isset($opt['A'])){
								$user['air']['strength'] = intval($opt['s']);
							}else{
								$user['tank']['strength'] = intval($opt['s']);
							}
						}
						if(isset($opt['r'])){
							if(isset($opt['A'])){
								$user['air']['rank_level'] = intval($opt['r']);
							}else{
								$user['tank']['rank_level'] = intval($opt['r']);
							}
							if(isset($opt['N'])){
								$msg = "-r and -N should not be used in the same time";
							}
						}

						if(isset($opt['f'])+isset($opt['N'])+isset($opt['o'])+isset($opt['t']) > 1){
							$msg = '-f, -N ,-o and -t should not be used in the same time';
						}elseif(isset($opt['f'])){
							$mode ='f';
							$fight = $opt['f']*1;
						}elseif(isset($opt['o'])){
							$mode ='o';
							if(preg_match('@^([0-9]+)([kmgt])$@',strtolower($opt['o']),$matches)){
								$symbol=[
									"t" => 1000000000000,
									"g" => 1000000000,
									"m" => 1000000,
									"k" => 1000
								];
								$damge = intval($matches[1]) * $symbol[$matches[2]];
							}else{
								$damge = intval($opt['o']);
							}
						}elseif(isset($opt['N'])){
							$mode ='N';
						}elseif(isset($opt['t'])){
							$mode ='t';
						}
						
						if(isset($opt['b'])&&isset($opt['B'])){
							$msg ="-b & -B only exist one!";
						}elseif(isset($opt['b'])){
							$booster = 1.5;
						}elseif(isset($opt['B'])){
							$booster = 2.0; 
						}

						if(isset($opt['L'])){
							if(intval($opt['L'])<0||intval($opt['L'])>20){
								$msg = "legends error: 1 - 20";
							}
							$legends += intval($opt['L'])* 0.1;
						}

						if(isset($opt['e'])){
							$natural_enemy = 1.1;
						}

						if($msg===NULL){
							$inf = [1,1.2,1.4,1.6,1.8,2.0,2.2,3];
							$color = [10, 9, 3, 8, 7, 4, 5, 10];
							$str = (!isset($opt['A']))?$user['tank']['strength']:$user['air']['strength'];
							$rank = (!isset($opt['A']))?$user['tank']['rank_level']:$user['air']['rank_level'];
							$next = (!isset($opt['A']))?$user['tank']['next']:$user['air']['next'];
							$next_tp = (!isset($opt['t']))?$user['tp']['next']:$user['tp']['next'];
							$lv_bonus = ($user['level']<100) ? 1.0:1.1;
							foreach($inf as $i=>$v){
								$inf[$i] = (((($rank-1)/20.0)+0.3) * (($str / 10.0 ) + 40)) * $v * $natural_enemy * $legends * $lv_bonus * $booster ;
							}

							$strinf ="";
							if($mode === 'N'){
								$kit_bonus = (isset($opt['R']))?1.1:1.0;
								$strinf .="離升軍階" . ((isset($opt['R']))?" +10%":"");
								foreach($inf as $i=>$v){
									$strinf .=" \x3{$color[$i]}[Q$i]".number_format(  ceil(($next*10) / ($v*$kit_bonus)) );
								}
							}elseif($mode === 't'){
								$strinf .="離TP章";
								foreach($inf as $i=>$v){
									$strinf .=" \x3{$color[$i]}[Q$i]".number_format(  ceil(($next_tp) / $v) );
								}
							}elseif($mode === 'f'){
								$strinf .= " ". number_format($fight) ."次";
								foreach($inf as $i=>$v){
									$strinf .=" \x3{$color[$i]}[Q$i]".number_format( $v * $fight );
								}
							}elseif($mode === 'o'){
								$strinf .= "輸出".number_format($damge)." 需要";
								foreach($inf as $i=>$v){
									$strinf .=" \x3{$color[$i]}[Q$i]".number_format( ceil($damge / $v ) );
								}
							}else{
								foreach($inf as $i=>$v){
									$strinf .=" \x3{$color[$i]}[Q$i]".number_format($v);
								}
							}
							$msg = sprintf("%s(%srank %d %s %s%s%s%s%s%s)%s\n"
								,$user['name']
								,(!isset($opt['A']))?'M':'A'
								,(!isset($opt['A']))?$user['tank']['rank_level']:$user['air']['rank_level']
								,(!isset($opt['A']))?'str':'per'
								,number_format(floor((!isset($opt['A']))?$user['tank']['strength']:$user['air']['strength']))
								,($natural_enemy==1.0)?'':' +NE'
								,($user['level']<100)?'':' Lv100up'
								,($booster == 1.5)?' +50%':''
								,($booster == 2.0)?' +100%':''
								,(isset($opt['L']))?' Legends+'.($opt['L']*10).'%':''
								,$strinf
							);
						}
					}else{
						$msg=$user;
					}
				}else{
					$msg=$opt;
				}
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;

			case '@time':
				$address = urlencode(trim(preg_split('@ @',$m['me']['pl'],2)[1]));
				if(strlen($address)==0){
					$address = 'taipei';
				}
				$key = $this->gapi_key;
				$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$key";
				$ch=curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_HEADER,false);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				$temp=curl_exec($ch);
				$data = json_decode($temp,true);
				$geo = join(',',$data['results'][0]['geometry']['location']);
				$time = time();
				$url = "https://maps.googleapis.com/maps/api/timezone/json?location=$geo&timestamp=$time&key=$key";
				curl_setopt($ch,CURLOPT_URL,$url);
				$temp=curl_exec($ch);
				$data = json_decode($temp,true);
				$TZ = new DateTimeZone($data['timeZoneId']);
				$date = (new DateTime('now',$TZ))->format('Y-m-d H:i:s O');
				$msg = "\x02:: 時間 ::\x0f {$data['timeZoneId']} :: {$date}\n";
				$this->base->put($m['re'], 'PRIVMSG', $msg);
				break;
			case '@help':
				break;
		}
	}
	
	public function triggerNum($num, $data){
	}


	public function cronjob($m = null){
		$channelList = bot::getInstance()->getChannelList();
		$msgs = $this->getEpicBattle();
		foreach($channelList as $channel){
			foreach($msgs as $msg){
				$this->base->put($channel, 'PRIVMSG', $msg);
			}
		}
	}

	public function getEpicBattle($cache = true){
		$mem  = new Memcached();
		$mem->addServer('memcached',11211);

		$url = "https://www.erepublik.com/en/military/campaigns-new/";
		$data = $this->getUrlLoginData($url);
		$msg = [];
		foreach($data['battles'] as $battle){
			foreach($battle['div'] as $div){
				if($div['epic']==2){
					$strCacheKey = 'Erepublik_battle_epic_'.$battle['start'].'_'. $div['div']."_".$battle['id'];
					$flagLock = $mem->add($strCacheKey,'Locked',$intTTL = strtotime('+2 day'));
					if($cache===false){
						$flagLock=true;
					}
					if($flagLock===true){
						$pr = ($battle['inv']['id'] ==  $div['wall']['for'])? $div['wall']['dom']:100-$div['wall']['dom'];
						$msg[] = sprintf("%s Epic Battle! %s (%2d p.)(%6.2f%%)(%4d mini) <-T%-3d-> (%4d mini)(%6.2f%%)(%2d p.) %s at %s %s\n",
							($div['div']==11)?'AIR':'D'.$div['div'],
							$data['countries'][$battle['inv']['id']]['name'],
							$battle['inv']['points'],
							$pr,
							$div['dom_pts']['inv'],
							floor(($data['last_updated']-$battle['start'])/60),
							$div['dom_pts']['def'],
							100-$pr,
							$battle['def']['points'],
							$data['countries'][$battle['def']['id']]['name'],
							$battle['region']['name'],
							"https://www.erepublik.com/en/military/battlefield-new/".$battle['id']
						);
					}
				}
			}
		}
		return $msg;
	}

	public function getBattle($battleId){
		$url = "https://www.erepublik.com/en/military/nbp-stats/".$battleId;
		$dataBattle = $this->getUrlLoginData($url);

		if(!is_array($dataBattle)){
			return ["Not found battle $battleId \n"];
		}

		if(!isset($dataBattle['division'])){
			return ["Not found battle $battleId \n"];
		}

		$intTime = strtotime('now') - $dataBattle['division']['created_at'];
		$countryId = array_keys($dataBattle['division']);
		$countryName = ["81"=>'Republic-of-China-Taiwan',"65"=>'Serbia',"35"=>'Poland',"13"=>'Hungary',"24"=>'USA',"1"=>'Romania',"11"=>'France',"49"=>'Indonesia',"44"=>'Greece',"14"=>'China',"9"=>'Brazil',"42"=>'Bulgaria',"43"=>'Turkey',"63"=>'Croatia',"27"=>'Argentina',"79"=>'Republic-of-Macedonia-FYROM',"58"=>'Israel',"28"=>'Venezuela',"53"=>'Portugal',"67"=>'Philippines',"74"=>'Uruguay',"77"=>'Peru',"75"=>'Paraguay',"52"=>'Republic-of-Moldova',"41"=>'Russia',"59"=>'Thailand',"29"=>'United-Kingdom',"68"=>'Singapore',"30"=>'Switzerland',"38"=>'Sweden',"15"=>'Spain',"47"=>'South-Korea',"51"=>'South-Africa',"61"=>'Slovenia',"40"=>'Ukraine',"36"=>'Slovakia',"166"=>'United-Arab-Emirates',"37"=>'Norway',"73"=>'North-Korea',"70"=>'Estonia',"55"=>'Denmark',"82"=>'Cyprus',"78"=>'Colombia',"64"=>'Chile',"23"=>'Canada',"69"=>'Bosnia-Herzegovina',"76"=>'Bolivia',"32"=>'Belgium',"83"=>'Belarus',"33"=>'Austria',"39"=>'Finland',"12"=>'Germany',"48"=>'India',"84"=>'New-Zealand',"31"=>'Netherlands',"80"=>'Montenegro',"26"=>'Mexico',"66"=>'Malaysia',"72"=>'Lithuania',"71"=>'Latvia',"45"=>'Japan',"10"=>'Italy',"54"=>'Ireland',"56"=>'Iran',"50"=>'Australia',"34"=>'Czech-Republic',"165"=>'Egypt',"57"=>'Pakistan',"164"=>'Saudi-Arabia'];
		$invId = $countryId[0];
		$defId = $countryId[1];
		$msg = [];
		if($intTime<7200){
			$strTime = sprintf("T%3d:%02d",floor($intTime/60),$intTime%60);
		}else{
			$strTime = sprintf("T End");
		}
		$msg[] = sprintf(":: Battle (%s) \x1f%s\x0f :: \x1f%s\x0f (\x02%d\x0f) vs (\x02%d\x0f) \x1f%s\x0f\n",
			$battleId, $strTime, 
			$countryName[$invId], $dataBattle['division'][$invId]['total'],
			$dataBattle['division'][$defId]['total'], $countryName[$defId]);
		$msg[] = sprintf("\x0301,00:: Div.1 :: (%2d p.)(%6.2f%%)(%4d mini)<->(%4d mini)(%6.2f%%)(%2d p.) \x0f".
						 "\x0300,01:: Div.2 :: (%2d p.)(%6.2f%%)(%4d mini)<->(%4d mini)(%6.2f%%)(%2d p.) \x0f\n",
			$dataBattle['division'][$invId]['1']['points'],100-$dataBattle['division']['domination']['1'],$dataBattle['division'][$invId]['1']['domination'],
			$dataBattle['division'][$defId]['1']['domination'],$dataBattle['division']['domination']['1'],$dataBattle['division'][$defId]['1']['points'],
			$dataBattle['division'][$invId]['2']['points'],100-$dataBattle['division']['domination']['2'],$dataBattle['division'][$invId]['2']['domination'],
			$dataBattle['division'][$defId]['2']['domination'],$dataBattle['division']['domination']['2'],$dataBattle['division'][$defId]['2']['points']);
		$msg[] = sprintf("\x0300,01:: Div.3 :: (%2d p.)(%6.2f%%)(%4d mini)<->(%4d mini)(%6.2f%%)(%2d p.) \x0f".
						 "\x0301,00:: Div.4 :: (%2d p.)(%6.2f%%)(%4d mini)<->(%4d mini)(%6.2f%%)(%2d p.) \x0f\n",
			$dataBattle['division'][$invId]['3']['points'],100-$dataBattle['division']['domination']['3'],$dataBattle['division'][$invId]['3']['domination'],
			$dataBattle['division'][$defId]['3']['domination'],$dataBattle['division']['domination']['3'],$dataBattle['division'][$defId]['3']['points'],
			$dataBattle['division'][$invId]['4']['points'],100-$dataBattle['division']['domination']['4'],$dataBattle['division'][$invId]['4']['domination'],
			$dataBattle['division'][$defId]['4']['domination'],$dataBattle['division']['domination']['4'],$dataBattle['division'][$defId]['4']['points']);
		$msg[] = sprintf("\x0301,00::  AIR  :: (%2d p.)(%6.2f%%)(%4d mini)<->(%4d mini)(%6.2f%%)(%2d p.) \x0f".
						 ":: Link  :: https://www.erepublik.com/en/military/battlefield-new/%d\n", 
			$dataBattle['division'][$invId]['11']['points'],100-$dataBattle['division']['domination']['11'],$dataBattle['division'][$invId]['11']['domination'],
			$dataBattle['division'][$defId]['11']['domination'],$dataBattle['division']['domination']['11'],$dataBattle['division'][$defId]['11']['points'],
			$battleId
		);
		return $msg;
	}

	public function getBattleHero($div,$damage,$time,$target,$hash){
		$url = "https://www.erepublik.com/en/military/campaigns-new/";
		$data = $this->getUrlLoginData($url);
		$count =0;
		foreach($data['battles'] as $battle){
			$intTime = intval((strtotime('now') - $battle['start'])/60);
			if(isset($battle['div'][$div]) && $battle['div'][$div]['end']===null && $intTime > $time){
				$count++;
			}
		}
		$msg = sprintf("Fount %d D%s Battle, Time > %d and Not End, Start Check Damge < %s \n" ,$count,($div==11)?'A':$div,$time,number_format($damage));
		$this->base->put($target, 'PRIVMSG', sprintf("[%s] %s",$hash,$msg));

		foreach($data['battles'] as $battle){
			$intTime = intval((strtotime('now') - $battle['start'])/60);
			if(isset($battle['div'][$div]) && $battle['div'][$div]['end']===null && $intTime > $time){
				$url = "https://www.erepublik.com/en/military/nbp-stats/".$battle['id'];
				echo "search battle {$battle['id']}\n";
				$battleData = $this->getUrlLoginData($url);
				$countryId = array_keys($battleData['division']);
				$invId = $countryId[0];
				$defId = $countryId[1];
				$intCurrent = array_keys($battleData['stats']['current'])[0];
				$inv = [
					'name'=>$data['countries'][$invId]['name'],
					'user'=> [
						'data' => "NULL" ,
						'damage'=> 0,
					],
					'domination'=> $battleData['division'][$invId][$div]['domination'],
					'wall'=> 100-$battleData['division']['domination'][$div],
				];
				$def = [
					'name'=>$data['countries'][$defId]['name'],
					'user'=> [
						'data' => "NULL" ,
						'damage'=> 0,
					],
					'domination'=> $battleData['division'][$defId][$div]['domination'],
					'wall'=> $battleData['division']['domination'][$div],
				];
				$divData = $battleData['stats']['current'][$intCurrent][$div];
				if(is_array($divData)){
					if($divData[$invId]['top_damage'][0]['citizen_id']!==NULL){
						$user = ErepublikUser::getInstance()->getUserDataById($divData[$invId]['top_damage'][0]['citizen_id']);
						$inv['user'] = [
							'data' => "",//sprintf("[%s(%d)]" ,$user['name'],$user['id']),
							'damage'=> $divData[$invId]['top_damage'][0]['damage'],
						];
					}
					if($divData[$defId]['top_damage'][0]['citizen_id']!==NULL){
						$user = ErepublikUser::getInstance()->getUserDataById($divData[$defId]['top_damage'][0]['citizen_id']);
						$def['user'] = [
							'data' => "",//sprintf("[%s(%d)]" ,$user['name'],$user['id']),
							'damage'=> $divData[$defId]['top_damage'][0]['damage'],
						];
					}
				}
				if($inv['user']['damage'] < $damage and $def['user']['damage'] < $damage){
					$msg = sprintf("%s (%6.2f%%)(%4d mini)%s[%s] <-T%-3d-> [%s]%s(%4d mini)(%6.2f%%)%s at %s %s\n",
						$inv['name'],
						$inv['wall'],
						$inv['domination'],
						$inv['user']['data'],
						number_format($inv['user']['damage']),
						$intTime,
						number_format($def['user']['damage']),
						$def['user']['data'],
						$def['domination'],
						$def['wall'],
						$def['name'],
						$battle['region']['name'],
						"https://www.erepublik.com/en/military/battlefield-new/".$battle['id']
					);
					$this->base->put($target, 'PRIVMSG', sprintf("[%s] %s",$hash,$msg));
				}
			}
		}
	}
	public function getUrlLoginData($strUrl){
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$strUrl);
		curl_setopt($ch,CURLOPT_HEADER,false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
		curl_setopt($ch, CURLOPT_COOKIEFILE, "cookie.txt");
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$temp=curl_exec($ch);
		$stat = curl_getinfo ($ch);
		$data = json_decode($temp,true);
		if($stat['http_code']!==200){
			return $stat['http_code'];
		}
		if($data['error']=='not_authorized'){
	        $url="https://www.erepublik.com/en";
	        curl_setopt($ch,CURLOPT_URL,$url);
	        $temp=curl_exec($ch);
	        preg_match("@csrfToken: '(.*)',@",$temp,$matches);
	        $url = "https://www.erepublik.com/en/login";
	        curl_setopt($ch,CURLOPT_URL,$url);
	        curl_setopt($ch, CURLOPT_POST, true); // 啟用POST
	        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array(
	            "_token"=>$matches[1],
	            "citizen_email"=>$this->erep_acc,
	            "citizen_password"=>$this->erep_pass,
	            "remember"=>"on",
	        )));
	        $temp=curl_exec($ch);
	        curl_setopt($ch,CURLOPT_URL,$strUrl);
			$temp=curl_exec($ch);
			$data = json_decode($temp,true);
		}
		return $data;
	}
}

?>
