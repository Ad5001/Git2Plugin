<?php


namespace Ad5001\Git2Plugin\tasks;



use pocketmine\Server;


use pocketmine\scheduler\PluginTask;
use pocketmine\plugin\PluginBase;


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
				
				// 				Deleting old files.
				$files = $this->main->getPluginFiles($path);
				foreach($files as $file) {
					if(is_dir($this->server->getPluginPath() . $file)){
						if(file_exists($this->server->getPluginPath() . $file . "/plugin.yml")) {
							$this->delete_files($this->server->getPluginPath() . $file);
						}
					} elseif(pathinfo($this->server->getPluginPath().$file, PATHINFO_EXTENSION) == "phar") {
						if(file_exists($this->server->getPluginPath().$file . "/plugin.yml")) {
                            unlink($this->server->getPluginPath() . $file);
                        }
					}
				}
                $plyml = yaml_parse(file_get_contents($path . "/plugin.yml"));
                $this->main->getLogger()->info("Installing version ".$plyml["version"]."... ");


                // Disabling the current plguin (to not have any problems)
                var_dump($this->main->getServer()->getPluginManager()->getPlugin($plyml["name"]));
                if($this->main->getServer()->getPluginManager()->getPlugin($plyml["name"]) instanceof \pocketmine\plugin\Plugin) {
                    $this->main->getServer()->getPluginManager()->getPlugin($plyml["name"] )->setEnabled(false);
                    
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
                       $phar = new \Phar($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar");
                       $phar->setMetadata($plyml);
                       $phar->setStub('<?php echo "PocketMine-MP plugin '.$plyml["name"] .' v'.$plyml["version"].'\nThis file has been generated using Git2Plugin v'.$this->main->getDescription()->getVersion().' (https://github.com/Ad5001/Git2Plugin)\n----------------\n"; __HALT_COMPILER();');
                       $phar->setSignatureAlgorithm(\Phar::SHA1);
                       $phar->buildFromDirectory($path);
                       $this->main->getLogger()->info("Succefully installed new version and buildt it as phar. Loading plugin...");
                       $loader = new \pocketmine\plugin\PharPluginLoader($this->main->getServer());
                       $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar");
                       $loader->enablePlugin($pl);



                   } else {
                       $this->delete_files($this->server->getFilePath() . "plugins/".$plyml["name"]);
                       copy($path, $this->server->getFilePath() . "plugins/".$plyml["name"]);
                       $this->main->getLogger()->info("Succefully installed new version. Loading plugin...");
                       if(!class_exists("FolderPluginLoader\\FolderPluginLoader")) {
                           $this->main->getLogger()->warning("Could not load plugin: DevTools is required to load a folder structured plugin.");
                       } else {
                           $loader = new \FolderPluginLoader\FolderPluginLoader($this->main->getServer());
                           $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . $plyml["name"]);
                           $loader->enablePlugin($pl);
                       }
                   }
		}
	}

    
    public function delete_files($src) { 
   		$dir = opendir($src); 
    	while(false !== ( $file = readdir($dir)) ) { 
        	if (( $file != '.' ) && ( $file != '..' )) { 
            	if ( is_dir($src . '/' . $file) ) { 
                	$this->delete_files($src.'/'.$file); 
            	} else { 
                	unlink($src.'/'.$file); 
            	} 
        	} 
    	}
    	closedir($dir);
        rmdir($src);
    }


}
