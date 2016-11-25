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
		$this->server->getScheduler()->scheduleAsyncTask(new PluginRefreshAsync());
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->main->getDataFolder() . "tmp/new/", RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $info) {
            $path = $info->getRealPath();
			if(is_dir($path) && file_exists($path . "/plugin.yml")) {

                $newversion = false;

                // Deleting old files.
				$files = $this->main->getPluginFiles($path);
				foreach($files as $file) {
					if($file->isDir()){
						if(file_exists($file->getRealPath() . "/plugin.yml") && version_compare(yaml_decode(file_get_contents($file->getRealPath() . "/plugin.yml"))["version"], yaml_decode(file_get_contents($path . "/plugin.yml"))["version"], "<=")) { // Check if not a DataFolder and if the version isn't superior (for dev servers)
                            $plyml = yaml_decode(file_get_contents($file->getRealPath() . "/plugin.yml"));
                            if($this->server->getPlugin($plyml["name"]) instanceof PluginBase) {
                                $this->main->getLogger()->info("Found a new version of " . $plyml["name"] . " : ".$plyml["version"].". Disabling and uninstalling the current one...");
                                $this->server->getPlugin($plyml["name"])->setEnabled(false);
                                $newversion = true;
                            }
							$it = new RecursiveDirectoryIterator($file->getRealPath(), RecursiveDirectoryIterator::SKIP_DOTS);
							$fs = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
							foreach($fs as $fil) {
								if ($fil->isDir()){
									rmdir($fil->getRealPath());
								}
								else {
									unlink($fil->getRealPath());
								}
							}
							rmdir($file->getRealPath());
                            $this->main->getLogger()->info("Installing version ".$plyml["version"]."... ");
						}
					} elseif(pathinfo($path, PATHINFO_EXTENSION) == "phar") {
						if(file_exists($file->getRealPath() . "/plugin.yml") && version_compare(yaml_decode(file_get_contents("phar://" . $file->getRealPath() . "/plugin.yml"))["version"], yaml_decode(file_get_contents($path . "/plugin.yml"))["version"], "<=")) {
                            $plyml = yaml_decode(file_get_contents("phar://" . $file->getRealPath() . "/plugin.yml"));
                            if($this->server->getPlugin($plyml["name"]) instanceof PluginBase) {
                                $this->main->getLogger()->info("Found a new version of " . $plyml["name"] . " : ".$plyml["version"].". Disabling and uninstalling the current one...");
                                $this->server->getPlugin($plyml["name"])->setEnabled(false);
                                $newversion = true;
                            }
                            unlink($file->getRealPath());
                            $this->main->getLogger()->info("Installing version ".$plyml["version"]."... ");
                        }
					}
				}



                // If there is no old plugin (installing)
                if(count($files) == 0) {
                    $this->main->getLogger()->info("Installing " . $plyml["name"] . " v".$plyml["version"]."...");
                    $newversion = true;
                }


                // Installing the new version...
                $plyml = yaml_decode(file_get_contents($path . "/plugin.yml"));
                if($newversion) {
                   if($this->main->getConfig()->get("building_mode") == "ToPhar") {

                   } else {
                       rename($info->getRealPath(), $this->server->getFilePath() . "plugins/".$plyml["name"]);
                       copy($this->server->getFilePath() . "plugins/".$plyml["name"], $this->main->getDataFolder() . "tmp/pl-" . $plyml["name"])
                       $this->main->getLogger()->info("Succefully installed new version. Loading plugin...");
                   }
                }
			} else {
                $url = "*Unknown url*";
                foreach($this->main->getConfig()->get("srcs") as $src) {
                    if(substr($str, strlen($str) - strlen($info->getFilename()) . ".git") == $info->getFilename()) . ".git") { // Checking if it's the right URL'
                        $url = $src;
                    }
                }
                $this->getLogger()->warning("Source downloaded at $url isn't a plugin ! Be sure that it's a real plugin git.");
            }
		}
	}
	
	
	
	
}
