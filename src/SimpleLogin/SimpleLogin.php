<?php

namespace SimpleLogin;

use SimpleLogin\database\PluginData;
use SimpleLogin\listener\EventListener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use SimpleLogin\task\AutoSaveTask;
use pocketmine\utils\Utils;

class SimpleLogin extends PluginBase implements Listener {
	private $database;
	private $eventListener;
	private $listenerLoader;
	private $plugin_version;
	/**
	 * Called when the plugin is enabled
	 *
	 * @see \pocketmine\plugin\PluginBase::onEnable()
	 */
	public function onEnable() {
		$this->database = new PluginData ( $this );
		$this->eventListener = new EventListener ( $this );
		$this->plugin_version = $this->getDescription()->getVersion();
		$version = json_decode(Utils::getURL("https://raw.githubusercontent.com/wsj7178/PMMP-plugins/master/version.json"), true);
		if($this->plugin_version < $version["SimpleLogin"]){
			$this->getLogger()->notice("플러그인의 새로운 버전이 존재합니다. 플러그인을 최신 버전으로 업데이트 해주세요!");
			$this->getLogger()->notice("현재버전: ".$this->plugin_version.", 최신버전: ".$version["SimpleLogin"]);
		}
		if(!isset($this->database->db["config"])){
			$this->database->db["config"]["allowsubaccount"] = false;
		}
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new AutoSaveTask ( $this ), 12000 );
	}
	/**
	 * Called when the plugin is disabled Use this to free open things and finish actions
	 *
	 * @see \pocketmine\plugin\PluginBase::onDisable()
	 */
	public function onDisable() {
		$this->save ();
	}
	/**
	 * Save plug-in configs
	 *
	 * @param string $async        	
	 */
	public function save($async = false) {
		$this->database->save ( $async );
	}
	/**
	 * Handles the received command
	 *
	 * @see \pocketmine\plugin\PluginBase::onCommand()
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		return $this->eventListener->onCommand ( $sender, $command, $label, $args );
	}
	/**
	 * Return Plug-in Database
	 */
	public function getDataBase() {
		return $this->database;
	}
	/**
	 * Return Plug-in Event Listener
	 */
	public function getEventListener() {
		return $this->eventListener;
	}
	/**
	 * Return Other Plug-in Event Listener
	 */
	public function getListenerLoader() {
		return $this->listenerLoader;
	}
}

?>