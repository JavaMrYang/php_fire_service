<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/5/31
 * Time: 14:36
 */
namespace app\fire\controller;

class Helper_Spell{

    public $spellArray = array();

    static public function getArray() {
        return unserialize(file_get_contents('pytable_without_tune.txt'));
    }
    /**
     * @desc 将字符串转换成拼音字符串
     * @param $string 汉字字符串
     * @param $upper 是否大写
     * @return string
     *
     * 例如：getChineseChar('我是作者'); 全部字符串+小写
     * return "woshizuozhe"
     *
     * 例如：getChineseChar('我是作者',true); 首字母+小写
     * return "wszz"
     *
     * 例如：getChineseChar('我是作者',true,true); 首字母+大写
     * return "WSZZ"
     *
     * 例如：getChineseChar('我是作者',false,true); 首字母+大写
     * return "WOSHIZUOZHE"
     */
    static public function getChineseChar($string,$isOne=false,$upper=false) {
        global $spellArray;
        $spellArray=[];
        if(is_numeric($string)) return $string;
        $str_arr = self::utf8_str_split($string,1); //将字符串拆分成数组
        $result = array();
        foreach($str_arr as $char)
        {
            if(preg_match('/^[\x{4e00}-\x{9fa5}]+$/u',$char))
            {
                $chinese = $spellArray[$char];
                $chinese  = $chinese[0];
            }else{
                $chinese=$char;
            }
            $chinese = $isOne ? substr($chinese,0,1) : $chinese;
            $result[] = $upper ? strtoupper($chinese) : $chinese;
        }
        return implode('',$result);
    }
    /**
     * @desc 将字符串转换成数组
     * @param $str 要转换的数组
     * @param $split_len
     * @return array
     */
    private function utf8_str_split($str,$split_len=1) {
        if(!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1) {
            return FALSE;
        }

        $len = mb_strlen($str, 'UTF-8');
        if ($len <= $split_len) {
            return array($str);
        }
        preg_match_all('/.{'.$split_len.'}|[^\x00]{1,'.$split_len.'}$/us', $str, $ar);

        return $ar[0];
    }
}