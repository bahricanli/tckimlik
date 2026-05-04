<?php

namespace BahriCanli;

class TcKimlik
{
    private static $validationFields = ['tcno', 'isim', 'soyisim', 'dogumyili'];
    private static $yabanciValidationFields = ["tcno", "isim", "soyisim", "dogumgunu", "dogumayi", "dogumyili"];

    private static $torProxy = null;

    public static function useTor(string $proxy = 'socks5h://127.0.0.1:9050')
    {
        self::$torProxy = $proxy;
    }

    public static function disableTor()
    {
        self::$torProxy = null;
    }
    public static function verify($input)
    {
        $tcno = $input;

        if (is_array($input) && !empty($input['tcno'])) {
            $tcno = $input['tcno'];
        }

        if (is_array($tcno)) {
            $inputKeys = array_keys($tcno);
            $tcno = $input[$inputKeys[0]];
        }

        if (!preg_match('/^[1-9]{1}[0-9]{9}[0,2,4,6,8]{1}$/', $tcno)) {
            return false;
        }

        if (is_int($tcno)) {
            $tcno = (string) $tcno;
        }

        $odd = $tcno[0] + $tcno[2] + $tcno[4] + $tcno[6] + $tcno[8];
        $even = $tcno[1] + $tcno[3] + $tcno[5] + $tcno[7];
        $digit10 = ($odd * 7 - $even) % 10;
        $total = ($odd + $even + $tcno[9]) % 10;

        if ($digit10 != $tcno[9] ||  $total != $tcno[10]) {
            return false;
        }

        return true;
    }

    public static function validate(array $data, $autoUppercase = true)
    {
        if (!self::verify($data)) {
            return false;
        }

        $response = isset($data['yabanci']) && $data['yabanci'] == true ?
            self::yabanciKimlikValidate($data, $autoUppercase) :
            self::tcKimlikValidate($data, $autoUppercase);

        return self::responseIndicatesSuccess($response);
    }

    public static function trUppercase($string)
    {
        return \Transliterator::create('tr-Upper')->transliterate($string);
    }


    private static function tcKimlikValidate(array $data, $autoUppercase = TRUE)
    {
        if (count(array_diff(self::$validationFields, array_keys($data))) != 0) {
            return false;
        }

        if ($autoUppercase) {
            foreach (self::$validationFields as $field) {
                $data[$field] = self::trUppercase($data[$field]);
            }
        }

        return self::sendSoapRequest(
            '/Service/KPSPublic.asmx',
            [
                'TCKimlikNo' => $data['tcno'],
                'Ad' => $data['isim'],
                'Soyad' => $data['soyisim'],
                'DogumYili' => $data['dogumyili']
            ],
            'TCKimlikNoDogrula'
        );
    }

    private static function yabanciKimlikValidate(array $data, $autoUppercase = TRUE)
    {
        if ($autoUppercase) {
            foreach (self::$yabanciValidationFields as $field) {
                $data[$field] = mb_convert_case($data[$field], MB_CASE_UPPER, "UTF-8");
            }
        }

        return self::sendSoapRequest(
            '/Service/KPSPublicYabanciDogrula.asmx',
            [
                'KimlikNo' => $data['tcno'],
                'Ad' => $data['isim'],
                'Soyad' => $data['soyisim'],
                'DogumGun' => $data['dogumgunu'],
                'DogumAy' => $data['dogumayi'],
                'DogumYil' => $data['dogumyili']
            ],
            'YabanciKimlikNoDogrula'
        );
    }

    private static function sendSoapRequest(string $url, array $payload, string $soapAction)
    {
        $fields = array_reduce(
            array_chunk($payload, 1, true),
            function ($r, $i) {
                return $r . '<' . key($i) . ">" . current($i) . '</' . key($i) . ">" . PHP_EOL;
            },
            ""
        );

        $ch = curl_init();

        $postData = '<?xml version="1.0" encoding="utf-8"?>
                    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Body>
                            <' . $soapAction . ' xmlns="http://tckimlik.linux.org.tr/WS">
                                ' . $fields . '
                            </' . $soapAction . '>
                        </soap:Body>
                    </soap:Envelope>';

        // CURL options
        $options = array(
            CURLOPT_URL               => 'https://tckimlik.linux.org.tr' . $url,
            CURLOPT_POST              => true,
            CURLOPT_POSTFIELDS        => $postData,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_SSL_VERIFYPEER    => false,
            CURLOPT_HEADER            => false,
            CURLOPT_HTTPHEADER        => array(
                'POST ' . $url . ' HTTP/1.1',
                'Host: tckimlik.linux.org.tr',
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tckimlik.linux.org.tr/WS/' . $soapAction . '"',
                'Content-Length: ' . strlen($postData)
            ),
        );

        if (self::$torProxy !== null) {
            $options[CURLOPT_PROXY] = self::$torProxy;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    private static function responseIndicatesSuccess($response)
    {
        if (!is_string($response) || $response === '') {
            return false;
        }

        if (preg_match('/<[^>]+Result>\s*true\s*<\/[^>]+Result>/i', $response) === 1) {
            return true;
        }

        return trim(strip_tags($response)) === 'true';
    }
}
