<?php

namespace disguise\factory;

use pocketmine\utils\SingletonTrait;

use LogicException;

class DisguiseFactory {
    use SingletonTrait;

    /** @var array<string, Disguise> */
    protected array $what = [];

    /**
     * @param string $nickname
     * @param string $realnickname
     * @param string $date
     * @param int $elapsed
     * @return void
     */
    public function addToDisguiseList(string $nickname, string $realnickname, string $date, int $elapsed) : void {
        $this->what[$realnickname] = new Disguise($nickname, $realnickname, $date, $elapsed);
    }

    /**
     * @param string $name
     * @return void
     */
    public function removeFromDisguiseList(string $name) : void {
        unset($this->what[$name]);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists(string $name) : bool {
        return isset($this->what[$name]);
    }

    /**
     * @param string $name
     * @return Disguise
     */
    public function getDisguiseFromList(string $name) : Disguise {
        return $this->what[$name] ?? throw new LogicException("Unrecognized ".$name);
    }

    /**
     * @param string $nickname
     * @return Disguise|null
     */
    public function getDisguiseFromListWithNickname(string $nickname) : ?Disguise {
        foreach($this->getDisguiseList() as $disguise){
            if($disguise->getNickname() === $nickname){
                return $this->what[$disguise->getRealnickname()];
            }
        }
        return null;
    }

    /**
     * @return Disguise[]
     */
    public function getDisguiseList() : array {
        return $this->what;
    }
}

?>