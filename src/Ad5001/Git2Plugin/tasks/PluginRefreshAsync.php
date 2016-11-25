<?php


namespace Ad5001\Git2Plugin\tasks;



use pocketmine\Server;


use pocketmine\scheduler\AsyncPluginTask;


use pocketmine\Player;



use Ad5001\Git2Plugin\Main;







class PluginRefreshTask extends AsyncPluginTask {
	
	
	
	
	public function __construct() {
		parent::__construct();
		$this->srcs = $main->getConfig()->get("srcs");
		$this->path = $main->getDataFolder() . "tmp/new/";
		$this->git = $this->getServer()->getPluginManager()->getPlugin("Gitable")->getGitClient()->executable;
	}
	
	
	
	
	public function onRun() {
		foreach ($this->srcs as $src) {
			
			$process = proc_open(
								    "cd $this->path\n$this->git clone $src",
								    array(
								      0 => array("pipe", "r"), //S			TDIN
								      1 => array("pipe", "w"), //S			TDOUT
								      2 => array("pipe", "w"), //S			TDERR
								    ),
								    $pipes
								  );
			if ($process !== false) {
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
			}
		}
	}
	
	
	
	
}
