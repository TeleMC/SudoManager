<?PHP

namespace SudoManager;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SudoManager extends PluginBase {

    private static $instance = null;
    public $pre = "§e•";
    //public $pre = "§l§e[ §fSudo §e]§r§e";
    public $OnlineSudo = [];

    public static function getInstance() {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["list" => [], "commandban" => []]);
        $this->data = $this->config->getAll();
    }

    public function onDisable() {
        $this->save();
    }

    public function save() {
        $this->config->setAll($this->data);
        $this->config->save();
    }

    public function onCommand(CommandSender $sender, Command $command, $label, $args): bool {
        if ($command->getName() == "sudo") {
            if (!isset($args[0])) {
                foreach ($this->getSudoCommandList($sender) as $key => $value) {
                    $sender->sendMessage("{$this->pre} {$value}");
                }
                return false;
            }
            if ($sender instanceof Player) {
                if ($args[0] == "login") {
                    if (!$this->isSudo($sender->getName())) {
                        $sender->sendMessage("{$this->pre} 권한 목록에 추가되어있지 않습니다.");
                        return false;
                    }
                    if ($this->isOnline($sender)) {
                        $sender->sendMessage("{$this->pre} 이미 인증된 상태입니다.");
                        return false;
                    } elseif (!isset($args[1])) {
                        $sender->sendMessage("{$this->pre} 패스워드가 입력되지 않았습니다.");
                        return false;
                    } elseif (!$this->isMatchPasswd($sender->getName(), $args[1])) {
                        $sender->sendMessage("{$this->pre} 패스워드가 맞지 않습니다.");
                        return false;
                    } else {
                        $sender->sendMessage("{$this->pre} 인증에 성공하였습니다.");
                        $this->setOp($sender);
                        return true;
                    }
                }
                if ($args[0] == "logout") {
                    if (!$this->isSudo($sender->getName())) {
                        $sender->sendMessage("{$this->pre} 권한 목록에 추가되어있지 않습니다.");
                        return false;
                    }
                    if (!$this->isOnline($sender)) {
                        $sender->sendMessage("{$this->pre} 인증되지 않은 상태입니다.");
                        return false;
                    } else {
                        $sender->sendMessage("{$this->pre} 인증받은 권한을 취소하였습니다.");
                        $this->setDeop($sender);
                        return true;
                    }
                } elseif ($args[0] == "passwd") {
                    if (!$this->isSudo($sender->getName())) {
                        $sender->sendMessage("{$this->pre} 권한 목록에 추가되어있지 않습니다.");
                        return false;
                    } elseif (!$this->isOnline($sender)) {
                        $sender->sendMessage("{$this->pre} 인증되지 않았습니다.");
                        return false;
                    } elseif (!isset($args[1])) {
                        $sender->sendMessage("{$this->pre} 패스워드가 입력되지 않았습니다.");
                        return false;
                    } else {
                        $this->setPasswd($sender->getName(), $args[1]);
                        $sender->sendMessage("{$this->pre} 패스워드를 변경하였습니다.");
                        return true;
                    }
                } elseif ($args[0] == "list") {
                    if (!$this->isSudo($sender->getName())) {
                        $sender->sendMessage("{$this->pre} 권한 목록에 추가되어있지 않습니다.");
                        return false;
                    }
                    if (!$this->isOnline($sender)) {
                        $sender->sendMessage("{$this->pre} 인증되지 않았습니다.");
                        return false;
                    }
                    $sudo = "§7";
                    foreach ($this->getSudoList() as $key => $value) {
                        $sudo .= "<{$value}> ";
                    }
                    $sender->sendMessage("{$this->pre} Sudo List");
                    $sender->sendMessage($sudo);
                    return true;
                } else {
                    foreach ($this->getSudoCommandList($sender) as $key => $value) {
                        $sender->sendMessage("{$this->pre} {$value}");
                    }
                    return false;
                }
            } else {
                if ($args[0] == "add") {
                    if (!isset($args[1])) {
                        $sender->sendMessage("{$this->pre} 이름이 기입되지 않았습니다.");
                        return true;
                    } elseif (!file_exists("{$this->getServer()->getDataPath()}players/" . mb_strtolower($args[1]) . ".dat")) {
                        $sender->sendMessage("{$this->pre} 해당 플레이어는 존재하지 않습니다.");
                        return true;
                    } elseif ($this->isSudo($args[1])) {
                        $sender->sendMessage("{$this->pre} 이미 추가된 플레이어입니다.");
                        return true;
                    } elseif (!$this->addSudo($args[1], "tele")) {
                        $sender->sendMessage("{$this->pre} 등록에 실패하였습니다.");
                        return true;
                    } else {
                        $sender->sendMessage("{$this->pre} 등록에 성공하였습니다.");
                        return true;
                    }
                } elseif ($args[0] == "del") {
                    if (!isset($args[1])) {
                        $sender->sendMessage("{$this->pre} 이름이 기입되지 않았습니다.");
                        return true;
                    } elseif (!$this->isSudo($args[1])) {
                        $sender->sendMessage("{$this->pre} 등록되지 않은 플레이어입니다.");
                        return true;
                    } elseif (!$this->delSudo($args[1])) {
                        $sender->sendMessage("{$this->pre} 제거에 실패하였습니다.");
                        return true;
                    } else {
                        $sender->sendMessage("{$this->pre} 제거에 성공하였습니다.");
                        return true;
                    }
                } elseif ($args[0] == "list") {
                    $sudo = "§7";
                    foreach ($this->getSudoList() as $key => $value) {
                        $sudo .= "<{$value}> ";
                    }
                    $sender->sendMessage("{$this->pre} Sudo List");
                    $sender->sendMessage($sudo);
                    return true;
                } else {
                    foreach ($this->getSudoCommandList($sender) as $key => $value) {
                        $sender->sendMessage("{$this->pre} {$value}");
                    }
                    return false;
                }
            }
        }
        return true;
    }

    private function getSudoCommandList(CommandSender $sender) {
        $command = [];
        if ($sender instanceof Player) {
            $command[] = "/sudo login <passwd> | 패스워드로 권한을 인증받습니다.";
            $command[] = "/sudo logout | 인증 받은 권한을 취소합니다.";
            $command[] = "/sudo passwd <passwd> | 패스워드를 변경합니다.";
        } else {
            $command[] = "/sudo add <name> | 플레이어를 Sudo에 추가합니다.";
            $command[] = "/sudo del <name> | 플레이어를 Sudo에서 제거합니다.";
            $command[] = "/sudo passwd <name> <passwd> | 플레이어의 패스워드를 변경합니다.";
        }
        $command[] = "/sudo list | Sudo 목록을 확인합니다.";
        return $command;
    }

    public function isSudo(string $name) {
        return isset($this->data["list"][mb_strtolower($name)]);
    }

    public function isOnline(Player $player) {
        return in_array($player, $this->OnlineSudo);
    }

    public function isMatchPasswd(string $name, string $passwd) {
        $name = mb_strtolower($name);
        $passwd = base64_encode($passwd);
        if (!$this->isSudo($name))
            return false;
        return $this->data["list"][$name] == $passwd;
    }

    public function setOp(Player $player) {
        if (!$this->isSudo($player->getName()) || in_array($player, $this->OnlineSudo))
            return false;
        $player->setOp(true);
        $this->OnlineSudo[] = $player;
        return true;
    }

    public function setDeop(Player $player) {
        if ($player->isOp())
            $player->setOp(false);
        if (in_array($player, $this->OnlineSudo))
            unset($this->OnlineSudo[array_search($player, $this->OnlineSudo)]);
        $player->setGamemode(0);
        return true;
    }

    public function setPasswd(string $name, string $passwd) {
        $name = mb_strtolower($name);
        $passwd = base64_encode($passwd);
        if (!$this->isSudo($name))
            return false;
        $this->data["list"][$name] = $passwd;
        if (($player = $this->getServer()->getPlayer($name)) instanceof Player)
            $this->setDeop($player);
        return true;
    }

    public function getSudoList() {
        $sudo = [];
        foreach ($this->data["list"] as $key => $value) {
            $sudo[] = $key;
        }
        return $sudo;
    }

    public function addSudo(string $name, string $passwd) {
        $name = mb_strtolower($name);
        if (!file_exists("{$this->getServer()->getDataPath()}players/{$name}.dat") || isset($this->data["list"][$name]))
            return false;
        $this->data["list"][$name] = base64_encode($passwd);
        return true;
    }

    public function delSudo(string $name) {
        $name = mb_strtolower($name);
        if (!isset($this->data["list"][$name]))
            return false;
        unset($this->data["list"][$name]);
        if (($player = $this->getServer()->getPlayer($name)) instanceof Player)
            $this->setDeop($player);
        return true;
    }
}
