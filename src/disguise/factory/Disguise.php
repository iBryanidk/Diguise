<?php

namespace disguise\factory;

use disguise\utils\Time;

class Disguise {

    /**
     * @param string $nickname
     * @param string $realnickname
     * @param string $date
     * @param int $elapsed
     */
    public function __construct(
        protected string $nickname,
        protected string $realnickname,
        protected string $date,
        protected int $elapsed,
    ){}

    /**
     * @return string
     */
    public function getNickname() : string {
        return $this->nickname;
    }

    /**
     * @return string
     */
    public function getRealnickname() : string {
        return $this->realnickname;
    }

    /**
     * @return string
     */
    public function getDate() : string {
        return $this->date;
    }

    /**
     * @return string
     */
    public function elapsed() : string {
        return Time::getTimeElapsedToFullString((int)microtime(true) - $this->elapsed);
    }
}

?>