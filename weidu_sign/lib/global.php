<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/2/23
 * Time: 15:00
 */
function inString($_str , $str , $html = ','){
    $arr = explode($html , $str);
    if(in_array($_str , $arr)){
        return $_str;
    }else{
        return false;
    }
}