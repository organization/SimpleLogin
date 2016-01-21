<?php

namespace SimpleLogin\listener;

use SimpleLogin\database\PluginData;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\Player;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\utils\TextFormat;
use SimpleLogin\task\timeoutKickTask;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\inventory\InventoryOpenEvent;

class EventListener implements Listener {
	/**
	 *
	 * @var Plugin
	 */
	private $plugin;
	private $db;
	private $listenerloader;
	private $islogin = [ ];
	private $clientid = [ ];
	/**
	 *
	 * @var Server
	 */
	private $server;
	private $kickev = [];
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->db = PluginData::getInstance ();
		$this->server = Server::getInstance ();
		
		$this->registerCommand ( "command-login", "simplelogin.command.login", "command-login-description", "command-login-help" );
		$this->registerCommand ( "command-register", "simplelogin.command.register", "command-register-description", "command-register-help" );
		$this->registerCommand ( "command-unregister", "simplelogin.command.unregister", "command-unregister-description", "command-unregister-help" );
		$this->registerCommand ( "command-manage", "simplelogin.command.manage", "command-manage-description", "command-manage-help" );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $plugin );
	}
	public function registerCommand($name, $permission, $description, $usage) {
		$name = $this->db->get ( $name );
		$description = $this->db->get ( $description );
		$usage = $this->db->get ( $usage );
		$this->db->registerCommand ( $name, $permission, $description, $usage );
	}
	public function getServer() {
		return $this->server;
	}
	/**
	 * 플레이어가 가입되어 있다면 true를 반환합니다.
	 *
	 * @param Player $player        	
	 * @return boolean
	 */
	public function isRegister(Player $player) {
		if (isset ( $this->db->db [strtolower ( $player->getName () )] ))
			return true;
		else
			return false;
	}
	/**
	 * 플레이어가 로그인되어있다면 true 를 반환합니다.
	 *
	 * @param Player $player        	
	 * @return boolean
	 */
	public function isLogin(Player $player) {
		if (isset ( $this->islogin [strtolower ( $player->getName () )] )) {
			return true;
		} else {
			return false;
		}
	}
	public function onCommand(CommandSender $player, Command $command, $label, array $args) {
		// 가 입 입력시
		if (strtolower ( $command ) == $this->db->get ( "command-register" )) {
			if (! isset ( $args [0] )) {
				// TODO - 명령어만 쳤을경우 도움말 표시
				$this->db->alert ( $player, $this->db->get ( "command-register-help" ) );
				return true;
			}
			// 가입되어 있을경우
			if ($this->isregister ( $player )) {
				$this->db->alert ( $player, $this->db->get ( "already-register" ) );
				return true;
			}
			// 로그인이 되어있지 않을경우
			if (! $this->isLogin ( $player )) {
				$password = ( string ) $args [0];
				// 패스워드 길이가 짧을경우
				if (strlen ( $password ) < 5) {
					$this->db->alert ( $player, $this->db->get ( "password-too-short" ) );
					return true;
				}
				// 데이터베이스에 플레이어 정보 저장
				$this->db->db [strtolower ( $player->getName () )] ["password"] = md5 ( $password );
				$this->db->db [strtolower ( $player->getName () )] ["ip"] = $player->getAddress ();
				$this->db->db [strtolower ( $player->getName () )] ["uuid"] = $this->clientid [$player->getName ()];
				$this->db->message ( $player, $this->db->get ( "register-success" ) );
				$this->islogin [$player->getName ()] = true;
				return true;
			}  // 로그인 되있는 경우
else {
				$this->db->alert ( $player, $this->db->get ( "already-login" ) );
				return true;
			}
		}
		// 로그인 입력시
		if (strtolower ( $command ) === $this->db->get ( "command-login" )) {
			// 명령어만 입력한 경우
			if (! isset ( $args [0] )) {
				$this->db->alert ( $player, $this->db->get ( "command-login-help" ) );
				return true;
			}
			// 로그인이 되어있는 경우
			if ($this->isLogin ( $player )) {
				$this->db->alert ( $player, $this->db->get ( "already-login" ) );
				return true;
			}  // 로그인이 되어있지 않을때
else {
				$password = ( string ) $args [0];
				// 패스워드가 맞다면 로그인
				if (md5 ( $password ) == $this->db->db [strtolower ( $player->getName () )] ["password"]) {
					$this->db->message ( $player, $this->db->get ( "login-success" ) );
					$this->db->db [strtolower ( $player->getName () )] ["ip"] = $player->getAddress ();
					$this->db->db [strtolower ( $player->getName () )] ["uuid"] = $this->clientid [$player->getName ()];
					$this->islogin [strtolower ( $player->getName () )] = true;
					return true;
				}  // 패스워드가 틀렸을때
else {
					$this->db->alert ( $player, $this->db->get ( "different-password" ) );
					return true;
				}
			}
			return true;
		}
		if (strtolower ( $command ) == $this->db->get ( "command-unregister" )) {
			if ($this->isLogin ( $player )) {
				unset ( $this->db->db [strtolower ( $player->getName () )] );
				$this->db->message ( $player, $this->db->get ( "unregister-success" ) );
				unset ( $this->islogin [strtolower ( $player->getName () )] );
				return true;
			}
		}
		// 계정관리 입력시
		if (strtolower ( $command ) == $this->db->get ( "command-manage" )) {
			// 아무것도 입력 안할시 도움말
			if (! isset ( $args [0] )) {
				$this->db->alert ( $player, $this->db->get ( "command-manage-help" ) );
				return true;
			}
			switch ($args [0]) {
				// 계정관리 탈퇴 입력시
				case $this->db->get ( "command-unregister" ) :
					// 플레이어 입력 안할시
					if (! isset ( $args [1] )) {
						$this->db->alert ( $player, $this->db->get ( "command-manage-help1" ) );
						return true;
					}
					$target = $args [1];
					if (! isset ( $this->db->db [strtolower ( $target )] )) {
						$this->db->alert ( $player, $this->db->get ( "cant-find-player" ) );
						return true;
					}
					unset ( $this->db->db [strtolower ( $target )] );
					$this->db->message ( $player, $this->db->get ( "manage-success" ) );
					break;
				case $this->db->get ( "command-change" ) :
					if (! isset ( $args [1] ) or ! isset ( $args [2] )) {
						$this->db->alert ( $player, $this->db->get ( "command-manage-help2" ) );
						return true;
					}
					$target = $args [1];
					$password = $args [2];
					if (! isset ( $this->db->db [strtolower ( $target )] )) {
						$this->db->alert ( $player, $this->db->get ( "cant-find-player" ) );
						return true;
					}
					$this->db->db [strtolower ( $target )] ["password"] = md5 ( $password );
					$this->db->message ( $player, $this->db->get ( "manage-success" ) );
					break;
				case $this->db->get ( "command-subaccount" ) :
					if ($this->db->db ["config"] ["allowsubaccount"] == true) {
						$this->db->db ["config"] ["allowsubaccount"] = false;
						$this->db->message ( $player, $this->db->get ( "subaccount-disallow" ) );
						return true;
					} else {
						$this->db->db ["config"] ["allowsubaccount"] = true;
						$this->db->message ( $player, $this->db->get ( "subaccount-allow" ) );
						return true;
					}
				default :
					$this->db->alert ( $player, $this->db->get ( "command-manage-help" ) );
					break;
			}
			
			return true;
		}
		
		return true;
	}
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer ();
		$this->clientid [$player->getName ()] = $player->getClientId ();
		// 가입되어있지 않을때
		if (! $this->isRegister ( $player )) {
			for($i = 0; $i < 5; $i ++) {
				$this->db->alert ( $player, $this->db->get ( "command-register-help" ) );
			}
			return true;
		}  // 가입되있을떄
else {
			// 마지막 접속 아이피와 지금 아이피가 같다면 자동로그인
			if ($player->getAddress () == $this->db->db [strtolower ( $player->getName () )] ["ip"]) {
				$this->db->db [strtolower ( $player->getName () )] ["uuid"] = $player->getClientId ();
				$this->db->message ( $player, $this->db->get ( "login-success" ) );
				$this->islogin [strtolower ( $player->getName () )] = true;
				return true;
			}  // 다르다면 로그인 메시지
else {
				for($i = 0; $i < 5; $i ++) {
					$this->db->alert ( $player, $this->db->get ( "command-login-help" ) );
				}
				return true;
			}
		}
	}
	public function onPlayerCommand(PlayerCommandPreprocessEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$message = $event->getMessage ();
			if ($message {0} === "/") {
				$command = substr ( $message, 1 );
				$args = explode ( " ", $command );
				if ($args [0] == "가입" or $args [0] == "로그인") {
					return true;
				} else {
					$event->setCancelled ();
				}
			} else {
				$this->db->alert ( $player, $this->db->get ( "to-login" ) );
				$event->setCancelled ();
				if (! $this->isRegister ( $player )) {
					$this->db->alert ( $player, $this->db->get ( "command-register-help" ) );
					return true;
				} else {
					$this->db->alert ( $player, $this->db->get ( "command-login-help" ) );
					return true;
				}
				return true;
			}
		}
		return true;
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$this->db->alert ( $player, $this->db->get ( "to-login" ) );
			$event->setCancelled ();
			if (! $this->isRegister ( $player )) {
				$this->db->alert ( $player, $this->db->get ( "command-register-help" ) );
				return true;
			} else {
				$this->db->alert ( $player, $this->db->get ( "command-login-help" ) );
				return true;
			}
			return true;
		}
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onPlayerDropItem(PlayerDropItemEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onPlayerQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer ();
		if(isset($this->kickev[$player->getName()])) {
			unset($this->kickev[$player->getName()]);
			return;
			
		}
		unset ( $this->islogin [strtolower ( $player->getName () )] );
		return true;
	}
	public function onPlayerItemConsume(PlayerItemConsumeEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onBlockBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onBlockPlace(BlockPlaceEvent $event) {
		$player = $event->getPlayer ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onPickupItem(InventoryPickupItemEvent $event) {
		$player = $event->getInventory ()->getHolder ();
		if (! $this->isLogin ( $player )) {
			$event->setCancelled ();
			return true;
		}
	}
	public function onLogin(PlayerLoginEvent $event) {
		$player = $event->getPlayer ()->getName ();
		$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( new timeoutKickTask ( $this->plugin, $this, $event->getPlayer () ), 20 * $this->db->config ["kick-time"] );
		if (strtolower ( $player ) == "config") {
			$event->setKickMessage ( TextFormat::RED . $this->db->get ( "cant-use-this-name" ) );
			$event->setCancelled ();
		}
		if ($this->isLogin ( $this->getServer ()->getPlayer ( $player ) )) {
			$event->setKickMessage ( TextFormat::RED . $this->db->get ( "already-login" ) );
			$event->setCancelled ();
			return true;
		}
		if ($this->db->db ["config"] ["allowsubaccount"] == false) {
			$puuid = $event->getPlayer ()->getClientId ();
			foreach ( $this->db->db as $playername => $key ) {
				if (! isset ( $this->db->db [$playername] ["uuid"] )) {
					continue;
				}
				if ($this->db->db [$playername] ["uuid"] == $puuid && strtolower ( $playername ) != strtolower ( $player )) {
					$event->setKickMessage ( TextFormat::RED . str_replace ( "%player%", $playername, $this->db->get ( "already-have-account" ) ) );
					$event->setCancelled ();
					break;
				}
			}
		}
	}
	public function onKick(PlayerKickEvent $event) {
		$player = $event->getPlayer ();
		if ($this->isLogin ( $player )) {
			if ($event->getReason () == "logged in from another location") {
				$this->kickev[$player->getName()] = true;
				$event->setCancelled ();
				return;
			}
		}
	}
}

?>