<?php
declare(strict_types=1);

namespace App\Common\Service\Lottery;

use Upp\Traits\HelpTrait;

class LotteryService
{
    use HelpTrait;


    /**
     * 判断是否中奖
     *
     * @param array $message
     * @param array $loginParams
     * @return bool
     */
    function winning($leixing, $tz_wei, $tz ,$kj)
    {
        $kjs = substr(strval(intval($kj)),-2,2); //分割开奖价格/号码
        switch ($leixing) {
            case 'dx':        //大小
                $tzs = strval($tz);
                $shiwei = 0;
                $gewei = 0;
                if($tz_wei == 1){
                    if ($kjs[1] > 4) { //大
                        $gdx = '1';
                    } else { //小
                        $gdx = '2';
                    }
                    if (substr_count($tzs[0], $gdx) == 1) { //判断个位 大小是否中
                        $gewei = $gewei + 1;
                    }
                }elseif ($tz_wei == 2){
                    if ($kjs[0] > 4) { //大
                        $sdx = '1';
                    } else { //小
                        $sdx = '2';
                    }
                    if (substr_count($tzs[0], $sdx) == 1) { //判断十位 大小是否中
                        $shiwei = $shiwei + 1;
                    }
                }
                break;
            case 'ds':        //单双
                $tzs = strval($tz);
                $shiwei = 0;
                $gewei = 0;
                if($tz_wei == 1){
                    if ($kjs[1] % 2 == 1) { //单
                        $gdx = '3';
                    } else { //双
                        $gdx = '4';
                    }
                    if (substr_count($tzs[0], $gdx) == 1) { //判断个位 大小是否中
                        $gewei = $gewei + 1;
                    }
                }elseif ($tz_wei == 2){
                    if ($kjs[0] % 2 == 1) { //单
                        $sdx = '3';
                    } else { //双
                        $sdx = '4';
                    }
                    if (substr_count($tzs[0], $sdx) == 1) { //判断十位 大小是否中
                        $shiwei = $shiwei + 1;
                    }
                }
                break;
            case 'shuzhi':        //猜数字
                $tzs = strval($tz);
                $shiwei = 0;
                $gewei = 0;
                if($tz_wei == 1){  //单数-个位
                    if (substr_count($tzs[0], $kjs[1]) == 1) { //判断个位数字是否出现在开奖号里
                        $gewei = $gewei + 1;
                    }
                }elseif ($tz_wei == 3){ //双数  个+十
                    if (substr_count($tzs, $kjs) == 1) { //判断十位数字是否出现在开奖号里
                        $shiwei = $shiwei + 1;
                    }
                }
                break;
            case 'dxds':        //双号
                break;
        }
        return [$gewei,$shiwei];
    }


    /**
     * 判断是否中奖
     *
     * @param array $message
     * @param array $loginParams
     * @return bool
     */
    function shifouzhongjiang($leixing, $tz, $kj)
    {
        $kjs = explode(',', $kj); //分割开奖号码
        switch ($leixing) {
            case 'q3fs':    //前三复试
                $tzs = explode(',', $tz);
                if (substr_count($tzs[0], $kjs[0]) == 1 and substr_count($tzs[1], $kjs[1]) == 1 and substr_count($tzs[2], $kjs[2]) == 1) {
                    $zhongjiang = 1; //中奖返回中奖注数
                } else {
                    $zhongjiang = 0; //未中奖范围0
                }
                break;
            case 'z3fs':    //中三复试
                $tzs = explode(',', $tz);
                if (substr_count($tzs[0], $kjs[1]) == 1 and substr_count($tzs[1], $kjs[2]) == 1 and substr_count($tzs[2], $kjs[3]) == 1) {
                    $zhongjiang = 1; //中奖返回中奖注数
                } else {
                    $zhongjiang = 0; //未中奖范围0
                }
                break;
            case 'h3fs':    //后三复试
                $tzs = explode(',', $tz);
                if (substr_count($tzs[0], $kjs[2]) == 1 and substr_count($tzs[1], $kjs[3]) == 1 and substr_count($tzs[2], $kjs[4]) == 1) {
                    $zhongjiang = 1; //中奖返回中奖注数
                } else {
                    $zhongjiang = 0; //未中奖范围0
                }
                break;
            case 'q3ds':    //前三单试
                $zhongjiang = substr_count($tz, $kjs[0] . $kjs[1] . $kjs[2]);
                break;
            case 'z3ds':    //中三单试
                $zhongjiang = substr_count($tz, $kjs[1] . $kjs[2] . $kjs[3]);
                break;
            case 'h3ds':    //后三单试
                $zhongjiang = substr_count($tz, $kjs[2] . $kjs[3] . $kjs[4]);
                break;
            case 'q3z3':    //前三组三
                $kj = array($kjs[0], $kjs[1], $kjs[2]);
                $kj = array_unique($kj);
                if (count($kj) == 2) { //剩2位 为组三号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'z3z3':    //中三组三
                $kj = array($kjs[1], $kjs[2], $kjs[3]);
                $kj = array_unique($kj);
                if (count($kj) == 2) { //剩2位 为组三号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'h3z3':    //后三组三
                $kj = array($kjs[2], $kjs[3], $kjs[4]);
                $kj = array_unique($kj);
                if (count($kj) == 2) { //剩2位 为组三号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'q3z6':    //前三组六
                $kj = array($kjs[0], $kjs[1], $kjs[2]);
                $kj = array_unique($kj);
                if (count($kj) == 3) { //剩3位 为组六号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1 and substr_count($tz, $kj[2]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'z3z6':    //中三组六
                $kj = array($kjs[1], $kjs[2], $kjs[3]);
                $kj = array_unique($kj);
                if (count($kj) == 3) { //剩3位 为组六号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1 and substr_count($tz, $kj[2]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'h3z6':    //后三组六
                $kj = array($kjs[2], $kjs[3], $kjs[4]);
                $kj = array_unique($kj);
                if (count($kj) == 3) { //剩3位 为组六号码
                    $kj = array_values($kj);
                    if (substr_count($tz, $kj[0]) == 1 and substr_count($tz, $kj[1]) == 1 and substr_count($tz, $kj[2]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'q3hz':    //前三混组
                $tzs = explode(',', $tz); //分割投注号码
                $kjs = array($kjs[0], $kjs[1], $kjs[2]); //获取 开奖号码
                $kj = array_unique($kjs); //删除开奖号码重复
                if (count($kj) == 3) { //组六
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        if (substr_count($danzu, $kj[0]) == 1 and substr_count($danzu, $kj[1]) == 1 and substr_count($danzu, $kj[2]) == 1) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 'l';
                    }
                } else if (count($kj) == 2) {  //组三
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        $cishu = array_count_values($kjs); //计算 组三中两个数字 各出现的次数
                        if (substr_count($danzu, $kj[0]) == $cishu[$kj[0]] and substr_count($danzu, $kj[1]) == $cishu[$kj[1]]) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 's';
                    }
                } else { //豹子号不中混组
                    $zhongjiang = 0;
                }
                break;
            case 'z3hz':    //中三混组
                $tzs = explode(',', $tz); //分割投注号码
                $kjs = array($kjs[1], $kjs[2], $kjs[3]); //获取 开奖号码
                $kj = array_unique($kjs); //删除开奖号码重复
                if (count($kj) == 3) { //组六
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        if (substr_count($danzu, $kj[0]) == 1 and substr_count($danzu, $kj[1]) == 1 and substr_count($danzu, $kj[2]) == 1) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 'l';
                    }
                } else if (count($kj) == 2) {  //组三
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        $cishu = array_count_values($kjs); //计算 组三中两个数字 各出现的次数
                        if (substr_count($danzu, $kj[0]) == $cishu[$kj[0]] and substr_count($danzu, $kj[1]) == $cishu[$kj[1]]) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 's';
                    }
                } else { //豹子号不中混组
                    $zhongjiang = 0;
                }
                break;
            case 'h3hz':    //后三混组
                $tzs = explode(',', $tz); //分割投注号码
                $kjs = array($kjs[2], $kjs[3], $kjs[4]); //获取 开奖号码
                $kj = array_unique($kjs); //删除开奖号码重复
                if (count($kj) == 3) { //组六
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        if (substr_count($danzu, $kj[0]) == 1 and substr_count($danzu, $kj[1]) == 1 and substr_count($danzu, $kj[2]) == 1) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 'l';
                    }
                } else if (count($kj) == 2) {  //组三
                    $kj = array_values($kj); // 重新排序数组键名
                    $zhongjiang = 0;
                    foreach ($tzs as $danzu) {
                        $cishu = array_count_values($kjs); //计算 组三中两个数字 各出现的次数
                        if (substr_count($danzu, $kj[0]) == $cishu[$kj[0]] and substr_count($danzu, $kj[1]) == $cishu[$kj[1]]) {
                            $zhongjiang = $zhongjiang + 1;
                        }
                    }
                    if ($zhongjiang > 0) {
                        $zhongjiang = $zhongjiang . 's';
                    }
                } else { //豹子号不中混组
                    $zhongjiang = 0;
                }
                break;
            case 'q2fs':    //前二复式
                $tzs = explode(',', $tz);
                if (substr_count($tzs[0], $kjs[0]) == 1 and substr_count($tzs[1], $kjs[1]) == 1) {
                    $zhongjiang = 1; //中奖返回中奖注数
                } else {
                    $zhongjiang = 0; //未中奖范围0
                }
                break;
            case 'h2fs':    //后二复式
                $tzs = explode(',', $tz);
                if (substr_count($tzs[0], $kjs[3]) == 1 and substr_count($tzs[1], $kjs[4]) == 1) {
                    $zhongjiang = 1; //中奖返回中奖注数
                } else {
                    $zhongjiang = 0; //未中奖范围0
                }
                break;
            case 'q2ds':    //前二单式
                $zhongjiang = substr_count($tz, $kjs[0] . $kjs[1]);
                break;
            case 'h2ds':    //后二单式
                $zhongjiang = substr_count($tz, $kjs[3] . $kjs[4]);
                break;
            case 'q2zx':    //前二组二
                if ($kjs[0] != $kjs[1]) {
                    if (substr_count($tz, $kjs[0]) == 1 and substr_count($tz, $kjs[1]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'h2zx':    //后二组二
                if ($kjs[3] != $kjs[4]) {
                    if (substr_count($tz, $kjs[3]) == 1 and substr_count($tz, $kjs[4]) == 1) {
                        $zhongjiang = 1;
                    } else {
                        $zhongjiang = 0;
                    }
                } else {
                    $zhongjiang = 0;
                }
                break;
            case 'q3bd':    //前三不定位
                $kj = array($kjs[0], $kjs[1], $kjs[2]);
                $kj = array_unique($kj);
                $kj = array_values($kj);
                if (count($kj) == 2) { //组三号
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]);
                } elseif (count($kj) == 1) { //豹子号
                    $zhongjiang = substr_count($tz, $kj[0]);
                } else {
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]) + substr_count($tz, $kj[2]);
                }
                break;
            case 'z3bd':    //后三不定位
                $kj = array($kjs[1], $kjs[2], $kjs[3]);
                $kj = array_unique($kj);
                $kj = array_values($kj);
                if (count($kj) == 2) { //组三号
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]);
                } elseif (count($kj) == 1) { //豹子号
                    $zhongjiang = substr_count($tz, $kj[0]);
                } else {
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]) + substr_count($tz, $kj[2]);
                }
                break;
            case 'h3bd':    //中三不定位
                $kj = array($kjs[2], $kjs[3], $kjs[4]);
                $kj = array_unique($kj);
                $kj = array_values($kj);
                if (count($kj) == 2) { //组三号
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]);
                } elseif (count($kj) == 1) { //豹子号
                    $zhongjiang = substr_count($tz, $kj[0]);
                } else {
                    $zhongjiang = substr_count($tz, $kj[0]) + substr_count($tz, $kj[1]) + substr_count($tz, $kj[2]);
                }
                break;
            case 'dwd':        //定位胆
                $tzs = explode(',', $tz);
                $zhongjiang = substr_count($tzs[0], $kjs[0]) + substr_count($tzs[1], $kjs[1]) + substr_count($tzs[2], $kjs[2]) + substr_count($tzs[3], $kjs[3]) + substr_count($tzs[4], $kjs[4]);
                break;
            case 'dxds':        //大小单双
                $tzs = explode(',', $tz);
                $shiwei = 0;
                $gewei = 0;
                if ($kjs[3] > 4) { //大
                    $sdx = '1';
                } else { //小
                    $sdx = '2';
                }
                if ($kjs[3] % 2 == 1) { //单
                    $sds = '3';
                } else { //双
                    $sds = '4';
                }
                if ($kjs[4] > 4) { //大
                    $gdx = '1';
                } else { //小
                    $gdx = '2';
                }
                if ($kjs[4] % 2 == 1) { //单
                    $gds = '3';
                } else { //双
                    $gds = '4';
                }
                if (substr_count($tzs[0], $sdx) == 1) { //判断十位 大小是否中
                    $shiwei = $shiwei + 1;
                }
                if (substr_count($tzs[0], $sds) == 1) { //判断十位 单双是否中
                    $shiwei = $shiwei + 1;
                }
                if (substr_count($tzs[1], $gdx) == 1) { //判断个位 大小是否中
                    $gewei = $gewei + 1;
                }
                if (substr_count($tzs[1], $gds) == 1) { //判断个位 单双是否中
                    $gewei = $gewei + 1;
                }
                if ($shiwei != 0 and $gewei != 0) { //判断 十位 或 个位  有一位为0 则为不中
                    $zhongjiang = $shiwei * $gewei;
                } else {
                    $zhongjiang = 0;
                }
                break;

        }

        return $zhongjiang;
        }
    }
