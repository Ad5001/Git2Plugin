<?php


namespace Ad5001\Git2Plugin\tasks;



use pocketmine\Server;


use pocketmine\scheduler\PluginTask;


use pocketmine\plugin\PluginBase;


use pocketmine\plugin\PluginDescription;


use pocketmine\Player;



use Ad5001\Git2Plugin\Main;







class PluginRefreshTask extends PluginTask {
	
	
	
	
	public function __construct(Main $main) {
		
		
		parent::__construct($main);
		
		
		$this->main = $main;
		
		
		$this->server = $main->getServer();
		
		
	}
	
	
	
	
	public function onRun($tick) {
        $this->main->getLogger()->notice("Checking updates....");
		$git = $this->server->getPluginManager()->getPlugin("Gitable")->getGitClient()->executable;
		$this->server->getScheduler()->scheduleAsyncTask(new PluginRefreshAsync($this->main));
		foreach (array_diff(scandir($this->main->getDataFolder() . "tmp/"), [".", "..", ".git", "new"]) as $info) {
            
            if(!is_dir($this->main->getDataFolder() . "tmp/$info") || !file_exists($this->main->getDataFolder() . "tmp/$info/plugin.yml")) {
                $this->main->getLogger()->notice("$info is not a plugin folder.");
                continue;
            } else {
                $path = $this->main->getDataFolder() . "tmp/$info";
            }

            // For the events.
			$files = $this->main->getPluginFiles($path);
            $plyml = yaml_parse(file_get_contents($path . "/plugin.yml"));
            $ev = new PluginPreUpdateEvent($this->getServer()->getPlugin($plyml["name"]), new PluginDescription($plyml), $files);
            $this->server->getPluginManager()->callEvent($ev);
            if($ev->isCancelled()) continue;

			
			// 				Deleting old files.
				foreach($files as $file) {
					if(is_dir($this->server->getPluginPath() . $file)){
						if(file_exists($this->server->getPluginPath() . $file . "/plugin.yml")) {
							$this->delete_files($this->server->getPluginPath() . $file);
                            $this->main->getLogger()->info("Deleted folder $file... ");
						}
					} elseif(explode(".", $file)[count(explode(".", $file)) - 1] == "phar") {
						if(file_exists("phar://".$this->server->getPluginPath().$file . "/plugin.yml")) {
                            if(unlink($this->server->getPluginPath() . $file)) $this->main->getLogger()->debug("Deleted phar $file... ");
                        }
					} elseif(explode(".", $file)[count(explode(".", $file)) - 1] == "php") {
                        if(unlink($this->server->getPluginPath() . $file)) $this->main->getLogger()->debug("Deleted php plugin $file... ");
					}
				}
                $this->main->getLogger()->info("Installing " . $plyml["name"] . " version ".$plyml["version"]."... ");


                // Disabling the current plugin (to not have any problems)
                if($this->main->getServer()->getPluginManager()->getPlugin($plyml["name"]) instanceof \pocketmine\plugin\Plugin) {
                    $this->main->getServer()->getPluginManager()->disablePlugin($this->main->getServer()->getPluginManager()->getPlugin($plyml["name"]));
                    $class = new \ReflectionClass('pocketmine\\plugin\\PluginManager');
                    $property = $class->getProperty('plugins');
                    $property->setAccessible(true);
                    $plugins = $property->getValue(Server::getInstance()->getPluginManager());
                    unset($plugins[$plyml["name"]]);
                    $property->setValue(Server::getInstance()->getPluginManager(), $plugins);
                    $property->setAccessible(false);
                }
                
                // Installing the new version...
                   if($this->main->getConfig()->get("building_mode") == "ToPhar") {
                       $phar = new \Phar($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar", \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_FILENAME);
                       $phar->setMetadata($plyml);
                       $phar->setSignatureAlgorithm(\Phar::SHA1);
                       $phar->buildFromDirectory($path);
                       $phar->setStub('<?php echo "PocketMine-MP plugin '.$plyml["name"] .' v'.$plyml["version"].'\nThis file has been generated using Git2Plugin v'.$this->main->getDescription()->getVersion().' (https://github.com/Ad5001/Git2Plugin)\n----------------\n"; __HALT_COMPILER();');
                       $this->main->getLogger()->debug("Succefully installed new version and built it as phar. Loading plugin...");
                       $loader = new \pocketmine\plugin\PharPluginLoader($this->main->getServer());
                       $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar");
                       $ev = new PluginUpdateEvent($pl);
                       $this->server->callEvent($pl);
                       $loader->enablePlugin($pl);
                       $this->main->getLogger()->debug("Succefully enabled plugin " . $plyml["name"] . " !");
                       $this->delete_files($path);
                       $phar = null;



                   } else {
                       $this->delete_files($this->server->getFilePath() . "plugins/".$plyml["name"]);
                       rename($path, $this->server->getFilePath() . "plugins/".$plyml["name"]);
                       $this->main->getLogger()->info("Succefully installed new version. Loading plugin...");
                       if(!class_exists("FolderPluginLoader\\FolderPluginLoader")) {
                           $this->main->getLogger()->warning("Could not load plugin: DevTools is required to load a folder structured plugin.");
                       } else {
                           $loader = new \FolderPluginLoader\FolderPluginLoader($this->main->getServer());
                           $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . $plyml["name"]);
                           $ev = new PluginUpdateEvent($pl);
                           $this->server->callEvent($pl);
                           $loader->enablePlugin($pl);
                           $this->main->getLogger()->debug("Succefully enabled plugin " . $plyml["name"] . " !");
                       }
                   }
		}
	}

    
    public function delete_files($pt) { 
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pt, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $p) {
            if($p->isDir()) {
                rmdir($p);
            } else {
                unlink($p);
            }
        }
        rmdir($pt);
    }


}
