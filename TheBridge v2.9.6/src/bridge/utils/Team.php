<?php


namespace bridge\utils;

use pocketmine\Player;

class Team{
	
	private $base = [
	"blue" => ["count" => 0, "players" => []],
	"red" => ["count" => 0, "players" => []]
	];
	
	private $teams = [];
	
	public function __construct($max = 1){
		$this->max = $max;
		$this->teams = $this->base;
	}
	
	public function reset(){
		$this->teams = $this->base;
	}
	
	public function isInTeam($name){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		
		foreach($this->teams as $teams => $data){
			if(isset($data["players"][$name])){
				return true;
			}
		}
		return false;
	}
	
	public function removePlayerTeam($name){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		
		if(!$this->isInTeam($name)){
			return true;
		}
		
		foreach($this->teams as $teams => $data){
			if(isset($data["players"][$name])){
				unset($data["players"][$name]);
				$data["count"]--;
				
				$this->teams[$teams] = $data;
			}
		}
		return true;
	}
	
	public function addPlayerTeam($name, $team = "blue"){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		
		if($this->getPlayerTeam($name) == $team){
			return true;
		}
		$data = $this->getTeamData($team);
		if($data["count"] >= $this->max){
			return false;
		}
		if($this->removePlayerTeam($name)){
			$data["players"][$name] = true;
			$data["count"]++;
			
			$this->teams[$team] = $data;
			return true;
		}
		return false;
	}
	
	public function getPlayerTeam($name){
		if($name instanceof Player){
			$name = $name->getName();
		}
		$name = strtolower($name);
		
		foreach($this->teams as $teams => $data){
			if(isset($data["players"][$name])){
				return $teams;
			}
		}
		return null;
	}
	
	public function isTeam($name1, $name2){
		if($name1 instanceof Player){
			$name1 = $name1->getName();
		}
		$name1 = strtolower($name1);
		
		if($name2 instanceof Player){
			$name2 = $name2->getName();
		}
		$name2 = strtolower($name2);
		
		$team1 = $this->getPlayerTeam($name1);
		$team2 = $this->getPlayerTeam($name2);
		
		if(is_null($team1) or is_null($team2)){
			return false;
		}
		if($team1 == $team2){
			return true;
		}
		return false;
	}
	
	private function getTeamData($team = "blue"){
		$team = strtolower($team);
		if(isset($this->teams[$team])){
			return $this->teams[$team];
		}
		return $this->base[$team];
	}
	
}
