<?php
namespace SimpleLogin\task;

use pocketmine\scheduler\PluginTask;
use SimpleLogin\SimpleLogin;
use SimpleLogin\listener\EventListener;
use pocketmine\Player;
use SimpleLogin\database\PluginData;

class timeoutKickTask extends PluginTask {
	
	/**
	 * 
	 * @var SimpleLogin $owner
	 * @var Player $player
	 * @var EventListener $listener
	 * @var PluginData $db
	 */
	protected $owner, $player, $listener;
	private $db;
	
	public function __construct(SimpleLogin $owner, EventListener $listener, Player $player) {
		parent::__construct($owner);
		$this->player = $player;
		$this->listener = $listener;
		$this->db = PluginData::getInstance();
	}
	public function onRun($currentTick) {
		if(!$this->listener->isLogin($this->player)) {
			$this->player->kick($this->db->get("timeout"));
		}
	}
}