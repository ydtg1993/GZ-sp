<?php


namespace App\Helper;


class tool
{
    /**
     * 多条件查询二维数组
     *
     * @param $array
     * @param array $params
     * @return array
     */
    public static function multiQuery2Array($array, array $params)
    {
        $data = [];
        foreach ($array as $item) {
            $add = true;
            foreach ($params as $field => $value) {
                if ($item[$field] != $value) {
                    $add = false;
                }
            }
            if ($add) {
                $data[] = $item;
            }
        }
        return $data;
    }

    /**
     * 多条件查询二维数组 返回索引
     *
     * @param $array
     * @param array $params
     * @return int
     */
    public static function multiQuery2ArrayIndex($array, array $params)
    {
        foreach ($array as $key=>$item) {
            $add = true;
            foreach ($params as $field => $value) {
                if ($item[$field] != $value) {
                    $add = false;
                }
            }
            if ($add) {
                return $key;
            }
        }
        return -1;
    }

    /**
     * @param $url
     * @param array $vars
     * @param array $header
     * @param string $method
     * @param int $timeout
     * @param bool $CA
     * @param string $cacert
     * @return bool|int|string
     */
    static function curlRequest($url, $vars = array(),$header = array(), $method = 'POST', $timeout = 60, $CA = false, $cacert = '')
    {
        $method = strtoupper($method);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($method == 'GET' && !empty($vars)) {
            $params = is_array($vars) ? http_build_query($vars) : $vars;
            $url = rtrim($url, '?');
            if (false === strpos($url . $params, '?')) {
                $url = $url . '?' . ltrim($params, '&');
            } else {
                $url = $url . $params;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: {$method}"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ($SSL && $CA && $cacert) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长
        }
        $result = curl_exec($ch);
        $error_no = curl_errno($ch);
        if (!$error_no) {
            $result = trim($result);
        } else {
            $result = $error_no;
        }

        curl_close($ch);
        return $result;
    }
}
