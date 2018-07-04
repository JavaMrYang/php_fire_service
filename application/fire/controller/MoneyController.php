<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/6/11
 * Time: 15:47
 */
namespace app\fire\controller;

use app\fire\common\JPushClient;
use think\Controller;

class MoneyController extends Controller{
    function testJPush(){
        $app_key='12abb23efaa0c600412e4ced';
        $master_secret='75067e9537d81b125c75711f';
        $jg_push=new JPushClient($app_key,$master_secret);
        $msg='php推送消息';
        $res=$jg_push->push('all','php消息标题', $msg);
        echo $res!==false?json_encode($res):'失败';
    }
      public $number=['零','壹','贰','叁','肆','伍','陆','柒','捌','玖'];
      public $dw=['十','百','千','万','亿'];
      public $xsw=['角','分'];
    function tranMoney($money){
        $num=explode('.',$money);
        $high=$num[0];
        $low=$num[1];
        echo $high.'   '.$low;
        $this->toHigh($high);
    }
    function toHigh($high){
        $zw_high='';
        $high=strrev($high);
        if(strlen($high)>8){
            for($i=0;$i<strlen($high);$i++){
                $w=substr($high,$i,1);
                $zw_high.=$this->number[$w];
            }
        }
    }


    function test(){
        $a='hello';
        $b=&$a;
        echo $b;
        unset($b);
        $b='world';
        echo $a;
    }

}