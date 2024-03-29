<?php

/*
       _             _____           ______ __
      | |           |  __ \         |____  / /
      | |_   _ _ __ | |  | | _____   __ / / /_
  _   | | | | | '_ \| |  | |/ _ \ \ / // / '_ \
 | |__| | |_| | | | | |__| |  __/\ V // /| (_) |
  \____/ \__,_|_| |_|_____/ \___| \_//_/  \___/


This program was produced by JunDev76 and cannot be reproduced, distributed or used without permission.

Developers:
 - JunDev76 (https://github.jundev.me/)

Copyright 2021. JunDev76. Allrights reserved.
*/

namespace JunDev76\SteakPriceChange;

use Exception;
use JunKR\CrossUtils;
use ojy\band\BandReporter;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use skh6075\ShopPlugin\ShopPlugin;

class SteakPriceChange extends PluginBase{

    /**
     * @throws Exception
     */
    public function onEnable() : void{
        CrossUtils::registercommand('스테이크가격변경', $this, '스테이크 가격을 변경합니다.', 'op');
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
            $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
                if(random_int(0, 5) === 0){
                    $this->price_change();
                }
            }), 20 * (60 * random_int(50, 120)));
        }), 20 * (60 * random_int(5, 10)));
    }

    /**
     * @throws Exception
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === '스테이크가격변경'){
            $this->price_change();
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public function price_change() : void{
        $price = ShopPlugin::getInstance()?->getItemPrice(ShopPlugin::itemToStr(Item::get(ItemIds::STEAK)));
        $before_price = $price?->getSellPrice();
        $new_price = random_int(9550, 9800);
        if(!isset($new_price)){
            return;
        }
        $price?->setSellPrice($new_price);
        $new_price_korean_format = EconomyAPI::getInstance()->koreanWonFormat($new_price);

        if($before_price === $new_price){
            $updown = '●';
        }else{
            $updown = ($before_price > $new_price ? ('▼' . EconomyAPI::getInstance()->koreanWonFormat($before_price - $new_price)) : ('▲' . EconomyAPI::getInstance()->koreanWonFormat($new_price - $before_price)));
        }

        BandReporter::addPost("#스테이크 #가격변동\n[스테이크 가격변동 알림]\n\n스테이크 판매가격이 변동되었습니다!\n\n판매가: $new_price_korean_format($updown)");
        Server::getInstance()->broadcastMessage('§b§l[스테이크] §r§7스테이크 판매가격이 변동되었습니다! ' . "§b판매가: {$new_price_korean_format}§r§o§7($updown)");

        $task = new class($new_price_korean_format . ' (' . $updown . ')') extends AsyncTask{

            public function __construct(public string $text){
            }

            public function onRun() : void{
                $ch = curl_init(); // 리소스 초기화

                //      속도 우선! https 사용 X
                $url = 'http://discord_server.crsbe.kr:32363/steak?' . urlencode($this->text);

                // 옵션 설정
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
                curl_exec($ch);

                curl_close($ch);  // 리소스 해제
            }
        };
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

}