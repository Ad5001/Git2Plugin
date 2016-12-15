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
            
            if(substr($info, 3) != "pl-") {
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

    

    public function delete_files($dir) { 
        if(is_dir($dir)) {
            foreach(array_diff(scandir($dir), array('.','..')) as $file) {
                chown($dir ."/" .$file, 777);
                if(is_dir($dir ."/" .$file)) $this->delete_files($dir ."/" .$file); else unlink($dir ."/" .$file); 
            } rmdir($dir); 
        }
    }


}
