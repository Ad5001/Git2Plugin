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
		foreach (array_diff(scandir($this->main->getDataFolder() . "tmp/new/"), [".", "..", ".git"]) as $info) {
			$path = $this->main->getDataFolder() . "tmp/new/". $info;
			if(is_dir($path) && file_exists($path . "/plugin.yml")) {
				$oldcommit = "";
				if(is_dir($this->main->getDataFolder() . "tmp/pl-" . $info) ) {
                    $process = proc_open(
												    "$git log -n 1",
												    array(
												      0 => array("pipe", "r"), //S				TDIN
												      1 => array("pipe", "w"), //S				TDOUT
												      2 => array("pipe", "w"), //S				TDERR
												    ),
												    $pipes, $this->main->getDataFolder() . "tmp/pl-" . $info
												  );
                                    if ($process !== false) {
					$stdout = stream_get_contents($pipes[1]);
					$stderr = stream_get_contents($pipes[2]);
					fclose($pipes[1]);
					fclose($pipes[2]);
                    $oldcommit = explode(" ", explode("\n", $stdout)[0])[1];
					proc_close($process);
				}
                }
				
				$newversion = false;





                // Checks current commit
                $process = proc_open(
						"$git log -n 1" ,
								array(
									0 => array("pipe", "r"), //S				TDIN
									1 => array("pipe", "w"), //S				TDOUT
									2 => array("pipe", "w"), //S				TDERR
								),
								$pipes, $this->main->getDataFolder() . "tmp/new/$info/"
								);
                if ($process !== false) {
					$stdout = stream_get_contents($pipes[1]);
					$stderr = stream_get_contents($pipes[2]);
					fclose($pipes[1]);
					fclose($pipes[2]);
                    $commit = explode(" ", explode("\n", $stdout)[0])[1];
                    if($commit == $oldcommit) {
                        $newversion = false;
                    }
					proc_close($process);
				}
				
				// 				Deleting old files.
				$files = $this->main->getPluginFiles($path);
				foreach($files as $file) {
					if(is_dir($this->server->getPluginPath() . $file)){
						if(file_exists($this->server->getPluginPath() . $file . "/plugin.yml") && (version_compare(yaml_parse(file_get_contents($this->server->getPluginPath() . $file . "/plugin.yml"))["version"], yaml_parse(file_get_contents($path . "/plugin.yml"))["version"], "<=") || $newversion)) {
							// 							Check if not a DataFolder and if the version isn't superior (for dev servers)
                            $plyml = yaml_parse(file_get_contents($this->server->getPluginPath() . $file . "/plugin.yml"));
                            if($this->server->getPluginManager()->getPlugin($plyml["name"]) instanceof \pocketmine\plugin\PluginBase) {
                                $this->main->getLogger()->info("Found a new version of " . $plyml["name"] . " : ".$plyml["version"].". Disabling and uninstalling the current one...");
                                $this->server->getPluginManager()->getPlugin($plyml["name"])->setEnabled(false);
                                $newversion = true;
                            }
							$this->delete_files($this->server->getPluginPath() . $file);
                            $this->main->getLogger()->info("Installing version ".$plyml["version"]."... ");
						}
					} elseif(pathinfo($this->server->getPluginPath().$file, PATHINFO_EXTENSION) == "phar") {
						if(file_exists($this->server->getPluginPath().$file . "/plugin.yml") && (version_compare(yaml_parse(file_get_contents("phar://" . $this->server->getPluginPath().$file . "/plugin.yml"))["version"], yaml_parse(file_get_contents($path . "/plugin.yml"))["version"], "<=") || $newversion)) {
                            $plyml = yaml_parse(file_get_contents("phar://" . $this->server->getPluginPath() . $file . "/plugin.yml"));
                            if($this->server->getPluginManager()->getPlugin($plyml["name"]) instanceof \pocketmine\plugin\PluginBase) {
                                $this->main->getLogger()->info("Found a new version of " . $plyml["name"] . " : ".$plyml["version"].". Disabling and uninstalling the current one...");
                                $this->server->getPluginManager()->getPlugin($plyml["name"])->setEnabled(false);
                                $newversion = true;
                            }
                            unlink($this->server->getPluginPath() . $file);
                            $this->main->getLogger()->info("Installing version ".$plyml["version"]."... ");
                        }
					}
				}



                // If there is no old plugin (installing)
                $plyml = yaml_parse(file_get_contents($path . "/plugin.yml"));
                if(count($files) == 0) {
                    $this->main->getLogger()->info("Installing " . $plyml["name"] . " v".$plyml["version"]."...");
                    $newversion = true;
                }



                // Installing the new version...
                if($newversion) {
                   if($this->main->getConfig()->get("building_mode") == "ToPhar") {
                       $phar = new \Phar($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar");
                       $phar->setMetadata($plyml);
                       $phar->setStub('<?php echo "PocketMine-MP plugin '.$plyml["name"] .' v'.$plyml["version"].'\nThis file has been generated using Git2Plugin v'.$this->main->getDescription()->getVersion().' (https://github.com/Ad5001/Git2Plugin)\n----------------\n"; __HALT_COMPILER();');
                       $phar->setSignatureAlgorithm(\Phar::SHA1);
                       $phar->buildFromDirectory($path);
                       $this->main->getLogger()->info("Succefully installed new version and buildt it as phar. Loading plugin...");
                       $this->delete_files($this->main->getDataFolder() . "tmp/pl-" . $plyml["name"]);
                       rename($path, $this->main->getDataFolder() . "tmp/pl-" . $plyml["name"]);
                       $loader = new \pocketmine\plugin\PharPluginLoader($this->main->getServer());
                       $pl = $loader->loadPlugin($this->main->getServer()->getPluginPath() . $plyml["name"] . "_v" . $plyml["version"] . ".phar");
                       $loader->enablePlugin($pl);



                   } else {

                       $this->delete_files($this->main->getDataFolder() . "tmp/pl-" . $plyml["name"]);
                       copy($path, $this->main->getDataFolder() . "tmp/pl-" . $plyml["name"]);
                       $this->delete_files($this->server->getFilePath() . "plugins/".$plyml["name"]);
                       rename($path, $this->server->getFilePath() . "plugins/".$plyml["name"]);
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
			} else {
                $url = "*Unknown url*";
                foreach($this->main->getConfig()->get("srcs") as $src) {
                    if(substr($src, strlen($src) - strlen($info . ".git")) == $info . ".git") { // Checking if it's the right URL
                        $url = $src;
                    }
                }
                $this->main->getLogger()->warning("Source downloaded at $url isn't a plugin ! Be sure that it's a real plugin git.");
            }
		}
	}

    

    public function delete_files($dir) { 
        if(is_dir($dir)) {
            if(is_dir($dir . "/.git/")) {
			           $process = proc_open(
								    (stripos(php_uname("s"), "Win") !== false or php_uname("s") === "Msys") ? "del .git /S /Q" : "rm -rf !$/.git",
								    array(
								      0 => array("pipe", "r"), //S			TDIN
								      1 => array("pipe", "w"), //S			TDOUT
								      2 => array("pipe", "w"), //S			TDERR
								    ),
								    $pipes, $dir
								  );
            }
            foreach(array_diff(scandir($dir), array('.','..')) as $file) {
                chown($dir ."/" .$file, 777);
                if(is_dir($dir ."/" .$file)) $this->delete_files($dir ."/" .$file); else unlink($dir ."/" .$file); 
            } rmdir($dir); 
        }
    }


}
