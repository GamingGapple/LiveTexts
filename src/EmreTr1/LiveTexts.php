<?php
   //Please write "/lt updateall" in Game before Start!
   //Version of LiveTexts 1.4pre
   
   namespace EmreTr1;
   
   use pocketmine\plugin\PluginBase;
   use pocketmine\Player;
   use pocketmine\Server;
   use pocketmine\entity\Entity;
   use pocketmine\entity\Human;
   use pocketmine\math\Vector3;
   use pocketmine\event\Listener;
   use pocketmine\command\Command;
   use pocketmine\command\CommandSender;
   use pocketmine\event\player\PlayerJoinEvent;
   use pocketmine\event\entity\EntityDamageEvent;
   use pocketmine\event\entity\EntityDamageByEntityEvent;
   use pocketmine\nbt\tag\CompoundTag;
   use pocketmine\utils\Config;
   use pocketmine\event\player\PlayerMoveEvent;
   use pocketmine\nbt\tag\ByteTag;
   use pocketmine\nbt\tag\ListTag;
   use pocketmine\nbt\tag\DoubleTag;
   use pocketmine\nbt\tag\FloatTag;
   use pocketmine\nbt\tag\ShortTag;
   use pocketmine\nbt\tag\StringTag;
         
   class LiveTexts extends PluginBase implements Listener{
   	
   	public $config;
   	public $removers = [];
   private static $instance;
   	
   	public static function getInstance(){
   		return self::$instance;
   	}
   	
   	public function onEnable(){
   		self::$instance=$this;
   		$this->getServer()->getPluginManager()->registerEvents($this, $this);
   		Server::getInstance()->getLogger()->info("§dLiveTexts §eStarting...");
   		Server::getInstance()->getCommandMap()->register("FloatingText", new Livecommands("lt"));
   		Entity::registerEntity(Text::class, true);
   		$this->loadConfig();
   	}
   	
   	public function loadConfig(){
   		$main=LiveTexts::getInstance();
   		@mkdir($this->getDataFolder());
   		$this->config=new Config($this->getDataFolder() . "texts.yml", Config::YAML);
    		if(!$this->config->get("LiveTexts")){
   			$opt=[
   			"File"=>"welcome.txt"
   			/*"ShowToPlayers" => []  COMING SOON */];
   			$this->config->set("LiveTexts", array());
   			$cfg=$this->config->get("LiveTexts");
   			$cfg["Welcome"]=$opt;
   			$this->config->set("LiveTexts", $cfg);
   	    touch($main->getDataFolder()."welcome.txt");
   	    $dosya=fopen($main->getDataFolder()."welcome.txt", "a");
   	    fwrite($dosya, "Welcome to the LiveTexts");
   	    fclose($dosya);
   			$this->config->save();
   		}
   	}
   	
   	public function onDamage(EntityDamageEvent $event){
   		$entity=$event->getEntity();
   		$main=LiveTexts::getInstance();
   		if($event instanceof EntityDamageByEntityEvent){
   			$damager=$event->getDamager();
   			if($damager instanceof Player){
   		 if(isset($main->removers[$damager->getName()])){
   		  	 $entity->close();
   		  	 $damager->sendMessage("§6[LiveTexts]§c LiveText removed.");
   		  	 unset($main->removers[$damager->getName()]);
   		  }
   		 }
   		}
   	}
   	
   	public function replacedText(string $text){
   		$tps=$this->getServer()->getTicksPerSecond();
   		$onlines=count($this->getServer()->getOnlinePlayers());
   		$maxplayers=$this->getServer()->getMaxPlayers();
   		$worldsc=count($this->getServer()->getLevels());
   		$server=$this->getServer();
   		$variables=[
   		"{line}"=>"\n",
   		"{tps}"=>$tps,
   		"{maxplayers}"=>$maxplayers,
   		"{onlines}"=>$onlines,
   		"{worldscount}"=>$worldsc,
   		"{ip}"=>$server->getIp(),
   		"{port}"=>$server->getPort(),
   		"{motd}"=>$server->getMotd(),
   		"{network}"=>$server->getNetwork()->getName()];
   		foreach($variables as $var=>$ms){
   			$text=str_ireplace($var, $ms, $text);
   		}
   		return $text;
   	}
   	
   	public function replaceForPlayer(Player $p, string $text){
   		$specialvars = [
   		"{name}"=>$p->getName(),
   		"{nametag}"=>$p->getNameTag(),
   		"{hunger}"=>$p->getFood(),
   		"{health}"=>$p->getHealth(),
   		"{maxhealth}"=>$p->getMaxHealth(),
   		"{nbt}"=>$p->namedtag,
   		"{level}"=>$p->getLevel()->getFolderName()];
   		foreach($specialvars as $var=>$ms){
   			$text = str_ireplace($var, $ms, $text);
   		}
   		return $text;
   	}
   	
   	public function createLiveText($x, $y, $z, $skin, $skinId, $inv, $yaw, $pitch, $chunk, $tag, $name, $file=""){
   	 $nbt = new CompoundTag;
   	 $nbt->Pos = new ListTag("Pos", [
   	 new DoubleTag("", $x),
   	 new DoubleTag("", $y),
   	 new DoubleTag("", $z)
   	 ]);
    $nbt->Rotation = new ListTag("Rotation", [
    new FloatTag("", $yaw),
    new FloatTag("", $pitch)
    ]);
    $nbt->Inventory = new ListTag("Inventory", $inv);
    $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $skin), "Name" => new StringTag("Name", $skinId)]);
    $nbt->Health = new ShortTag("Health", 20);
    $nbt->Invulnerable = new ByteTag("Invulnerable", 1);
    $nbt->LiveTextName= new StringTag("LiveTextName", $name);
    $nbt->CustomName=new StringTag("CustomName", $tag);
    $nbt->infos=new ListTag("infos", ["file"=>$file, "datafolder"=>$this->getDataFolder()."$name"]);
   		$entity=Entity::createEntity("Text", $chunk, $nbt, $tag);
   		$entity->spawnToAll();
   	}
   }
   
   class Livecommands extends Command{
   	
   	private $name;
   	
   	public function __construct($name){
   		parent::__construct(
   		$name,
   		"LiveTexts plugin main Command",
   		"/lt <add|cancel|remove>");
   		$this->setPermission("livetext.command.use");
   	}
   	
   	public function execute(CommandSender $s, $label, array $args){
    if(!$s->hasPermission("livetext.command.use")){
      return true;
    }
    $help="§aLive§bTexts §6Help Page\n
    §7- /lt addtext <text(unlimited args)> :§e Add livetext without file. You can use {line} for new line\n
    §7- /lt add <TextName> :§e Add livetext with file\n
    §7- /lt remove :§e Remove a LiveText when Tap a entity\n
    §7- /lt updateall :§e Update All old LiveTexts to New LiveTexts\n
    §7- /lt cancel :§e Cancel remove event";
   		if(!empty($args[0])){
   			$main=LiveTexts::getInstance();
   			$core=LiveTexts::getInstance();
   			switch($args[0]){
   				case "addtext":
   				    array_shift($args);
   				    $text="";
   				    foreach($args as $t){
   				    	$text.=$t." ";
   				    }
   				    $replaced=$main->replacedText($text);
   				    $main->createLiveText($s->x, $s->y - 1, $s->z, $s->getSkinData(), $s->getSkinId(), $s->getInventory(), $s->yaw, $s->pitch, $s->chunk, $replaced, $args[0]);
   				    $s->sendMessage("§6[LiveTexts] §eLiveText created(not file)");
   				    break;
   				case "add":
   				    if(!empty($args[1])){
   				    	  $file=$args[1];
   				    	  if($main->config->getNested("LiveTexts.$file")){
   				    	  	  $ad=$main->config->getNested("LiveTexts.$file")["File"];
   				    	  	  $yazi = file_get_contents($main->getDataFolder()."$ad");
   				    	  	  $x=$s->x;
   				    	  	  $y=$s->y;
   				    	  	  $z=$s->z;
   				    	  	  $skin=$s->getSkinData();
   				    	  	  $skinId=$s->getSkinId();
   				    	  	  $yaw=$s->yaw;
   				    	  	  $pitch=$s->pitch;
   				    	  	  $inv=$s->getInventory();
   				    	  	  $main->createLiveText($x, $y, $z, $skin, $skinId, $inv, $yaw, $pitch, $s->chunk, $yazi, $args[1], $dosya);
   				    	  	  $s->sendMessage("§6[LiveTexts]§a Text created.");
   				    	  }else{
   				    	  	 $s->sendMessage("§6[LiveTexts] §cText not found on texts.yml");
   				    	  }
   				    }else{
   				    	 $s->sendMessage("§eUsage: /lt add <textname>");
   				    }
   				    break;
   				case "updateall":
   				    $levels=$main->getServer()->getLevels();
   				    foreach($levels as $level){
   				    	$entities=$level->getEntities();
   				    	foreach($entities as $entity){
   				    		if(isset($entity->namedtag->LiveTextName)){
   				    			if(!isset($entity->namedtag->infos) or (!$entity instanceof Human)){
   				    				$ad=$entity->namedtag->LiveTextName;
   				    				$yazi = file_get_contents($main->getDataFolder()."$ad");
   				    				$main->createLiveText($entity->x, $entity->y + 1, $entity->z, $entity->getSkinData(), $entity->getSkinId(), $entity->getInventory(), $entity->yaw, $entity->pitch, $entity->chunk, $entity->namedtag->CustomName, "$ad", $dosya);
   				    				$entity->close();
   				    			}
   				    		}
   				    	}
   				    }
   				    $s->sendMessage("§6[LiveTexts]§a All old LiveTexts has been updated!");
   				    break;
   		  case "cancel":
   		      if(isset($main->removers[$s->getName()])){
   		      	 unset($main->removers[$s->getName()]);
   		      }
   		      $s->sendMessage("§6[LiveTexts]§e Event cancelled.");
   		      break;
   		  case "remove":
   		      $main->removers[$s->getName()]=true;
   		      $s->sendMessage("§6[LiveTexts]§c Please Touch a LiveText now.");
   		      break;
     	}
   }else{
   	 $s->sendMessage($help);
   }
  }
 }
?>
