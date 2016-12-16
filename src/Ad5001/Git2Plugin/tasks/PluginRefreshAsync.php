<?php


namespace Ad5001\Git2Plugin\tasks;



use pocketmine\Server;


use pocketmine\scheduler\AsyncTask;


use pocketmine\Player;



use Ad5001\Git2Plugin\Main;







class PluginRefreshAsync extends AsyncTask {


	const RED = "\e[92m";
	const GREEN = "\e[93m";
	const YELLOW = "\e[94m";
	const BLUE = "\e[96m";
	const PURPLE = "\e[97m";
	const LIGHT_BLUE = "\e[98m";
	
	
	
	
	public function __construct(Main $main) {
		parent::__construct();
		$this->srcs = $main->getConfig()->get("srcs");
		$this->path = $main->getDataFolder() . "tmp/new/";
		$this->git = $main->getServer()->getPluginManager()->getPlugin("Gitable")->getGitClient()->executable;
	}
	
	
	
	
	public function onRun() {
		foreach ($this->srcs as $src) {
			$name = explode(".", explode("/", $src)[count(explode("/", $src)) - 1])[0];
			$update = false;
			$path = $this->path;
			if(is_dir("$path/$name")) {
				proc_open(
												    "$this->git fetch origin" ,
												    array(
												      0 => array("pipe", "r"), //S				TDIN
												      1 => array("pipe", "w"), //S				TDOUT
												      2 => array("pipe", "w"), //S				TDERR
												    ),
												    $pipes, "$path/$name"
				);


				$process = proc_open( // SOurce plugin count
												    "$this->git rev-list origin --count" ,
												    array(
												      0 => array("pipe", "r"), //S				TDIN
												      1 => array("pipe", "w"), //S				TDOUT
												      2 => array("pipe", "w"), //S				TDERR
												    ),
												    $pipes, "$path/$name"
				);
				if ($process !== false) {
					$newCount = (int) stream_get_contents($pipes[1]);
					fclose($pipes[1]);
					proc_close($process);
				} else {
					self::log( "Looks like we're having some trouble with the execution of async commands (ERROR 1).");
				}
				

				$process = proc_open( // Current/old plugin count
						"$this->git rev-list HEAD --count" ,
					    array(
						      0 => array("pipe", "r"), //S				TDIN
						      1 => array("pipe", "w"), //S				TDOUT
						      2 => array("pipe", "w"), //S				TDERR
					    ),
					    $pipes, "$path/$name"
				);
				if ($process !== false) {
					$oldCount = (int) stream_get_contents($pipes[1]);
					fclose($pipes[1]);
					proc_close($process);
				} else {
					self::log( "Looks like we're having some trouble with the execution of async commands (ERROR 2).");
				}


				if($oldCount < $newCount) {
					$process = proc_open( // Downloading changes
												    "$this->git pull" ,
												    array(
												      0 => array("pipe", "r"), //S				TDIN
												      1 => array("pipe", "w"), //S				TDOUT
												      2 => array("pipe", "w"), //S				TDERR
												    ),
												    $pipes, "$path/$name"
					);
					$update = true;
				}


			} else {
				@mkdir($this->path . explode(".", explode("/", $src)[count(explode("/", $src)) - 1])[0]);
				$process = proc_open( // Downloading new plugin
						"$this->git clone $src $path/$name",
					    array(
						      0 => array("pipe", "r"), //S				TDIN
						      1 => array("pipe", "w"), //S				TDOUT
						      2 => array("pipe", "w"), //S				TDERR
					    ),
					    $pipes, "$path/$name"
				);
				if ($process !== false) {
					$stdout = stream_get_contents($pipes[1]);
					fclose($pipes[1]);
					$stderr = stream_get_contents($pipes[2]);
					fclose($pipes[2]);
					proc_close($process);
				} else {
					self::log( "Looks like we're having some trouble with the execution of async commands (ERROR 3).");
				}

				$update = true;
			}


			// Cloning the update...
			if($update && file_exists("$path/$name/plugin.yml")) {
				@mkdir($path . "../pl-" .$name);
				$this->xcopy("$path/$name/", "$path../pl-$name/");
			} elseif(!file_exists("$path/$name/plugin.yml")) {
				self::log("$name downloaded at $src isn't a plugin !");
			}
		}
	}



	public function xcopy($src, $dst) {
   		$dir = opendir($src); 
    	@mkdir($dst); 
    	while(false !== ( $file = readdir($dir)) ) { 
        	if (( $file != '.' ) && ( $file != '..' ) && ($file !== ".git")) { 
            	if ( is_dir($src . '/' . $file) ) { 
                	$this->xcopy($src.'/'.$file, $dst.'/'.$file); 
            	} else { 
                	copy($src.'/'.$file, $dst.'/'.$file); 
            	} 
        	} 
    	} 
    	closedir($dir);
	}



	/*
	Logs a message.
	@param     $msg    string
	*/
	public static function log(string $msg) {
		echo self::GREEN . "[" . self::LIGHT_BLUE . "Git2Plugin - Async" . self::GREEN . "] " . self::RED . $msg . "\n";
	}
	
	
	
	
}
