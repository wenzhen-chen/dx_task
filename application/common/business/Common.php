<?php
/**
 * 公共工具类
 * @author wenzhen-chen
 * @time 2018-8-15
 */
namespace app\common\business;
class Common
{
    /**
     * 获取一天起始时间戳
     * @param $key @距离当天天数  昨天为-1，今天为0 明天为1
     * @return array
     * @author wenzhen-chen
     * @time 2018-8-15
     */
    public static function getDayTimeByKey($key = 0)
    {
        $start_time = strtotime(date('Y-m-d', strtotime("{$key} day")));
        $end_time = $start_time + 24 * 3600 - 1;
        return [
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
    }

    /**
     * 获取一周的起始时间
     * @param int $key @距离本周的周数  上周为-1，本周为0 下周为1
     * @return array
     * @author wenzhen-chen
     * @time 2018-10-30
     */
    public static function getWeekTimeByKey($key = 0)
    {
        $start_time = mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 + $key * 7, date("Y"));
        $end_time = mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 + $key * 7, date("Y"));
        return [
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
    }

    /**
     * 获取月的其实时间
     * @param int $key @距离本月的月数  上月为-1，本月为0 下月为1
     * @return array
     * @author wenzhen-chen
     * @time 2018-10-30
     */
    public static function getMonthTimeByKey($key = 0)
    {
        $start_time = mktime(0, 0, 0, date('m') + $key, 1, date('Y'));
        $end_time = mktime(23, 59, 59, date('m') + $key, date('t'), date('Y'));
        return [
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
    }

    /**
     * curl请求
     * @param $url
     * @param string $method
     * @param array $params
     * @param int $timeout
     * @return array|mixed
     */
    public static function curlRequest($url, $method = "GET", $params = array(), $timeout = 30)
    {

        $paramString = http_build_query($params, '', '&');
        if (strtoupper($method) == "GET" && $params) {
            $url .= "?" . $paramString;
        }

        $ch = curl_init($url);
        if (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, false);
        //  curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        //检测是否是https访问
        if (strpos($url, 'https') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        $result = curl_exec($ch);
        //请求失败的处理方法
        if (curl_errno($ch)) {
            return [];
        }
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * 去掉字符串中的emoji字符
     * @param $text
     * @return null|string|string[]
     * @author wenzhen-chen
     * @time 2019-1-19
     */
    public static function removeEmoji($text)
    {
        $clean_text = "";
        // 匹配的表情符号
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);
        // 匹配各种符号和象形文字
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        // 匹配运输和地图符号
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);
        // 匹配其他符号
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);
        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);
        return $clean_text;
    }
}