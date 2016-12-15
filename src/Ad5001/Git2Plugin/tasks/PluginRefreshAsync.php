<?php


namespace Ad5001\Git2Plugin\tasks;



use pocketmine\Server;


use pocketmine\scheduler\AsyncTask;


use pocketmine\Player;



use Ad5001\Git2Plugin\Main;







class PluginRefreshAsync extends AsyncTask {
	
	
	
	
	public function __construct(Main $main) {
		parent::__construct();
		$this->srcs = $main->getConfig()->get("srcs");
		$this->path = $main->getDataFolder() . "tmp/new/";
		$this->git = $main->getServer()->getPluginManager()->getPlugin("Gitable")->getGitClient()->executable;
	}
	
	
	
	
	public function onRun() {
		foreach ($this->srcs as $src) {
			@mkdir($this->path . explode(".", explode("/", $src)[count(explode("/", $src)) - 1])[0]);
			$name = explode(".", explode("/", $src)[count(explode("/", $src)) - 1])[0];
			$update = false;
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
					echo "Looks like we're having some trouble with the execution of async commands (ERROR 1).";
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
					echo "Looks like we're having some trouble with the execution of async commands (ERROR 2).";
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
				$process = proc_open( // Downloading new plugin
						"$this->git clone $src",
					    array(
						      0 => array("pipe", "r"), //S				TDIN
						      1 => array("pipe", "w"), //S				TDOUT
						      2 => array("pipe", "w"), //S				TDERR
					    ),
					    $pipes, "$path/$name"
				);
				$update = true;
			}
		}
	}
	
	
	
	
}
