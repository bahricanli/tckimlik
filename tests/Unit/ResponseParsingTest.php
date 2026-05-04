<?php

namespace BahriCanli\Tests;

use BahriCanli\TcKimlik;

class ResponseParsingTest extends TestCase
{
    /**
     * @group Unit
     */
    public function test_response_parser_accepts_true_xml_with_warning_prefix()
    {
        $response = 'Warning: preg_match(): Allocation of JIT memory failed in /var/www/html/index.php on line 1'
            . '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<TCKimlikNoDogrulaResponse xmlns="http://tckimlik.linux.org.tr/WS">'
            . '<TCKimlikNoDogrulaResult>true</TCKimlikNoDogrulaResult>'
            . '</TCKimlikNoDogrulaResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $method = new \ReflectionMethod(TcKimlik::class, 'responseIndicatesSuccess');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, $response));
    }

    /**
     * @group Unit
     */
    public function test_response_parser_rejects_false_xml()
    {
        $response = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<TCKimlikNoDogrulaResponse xmlns="http://tckimlik.linux.org.tr/WS">'
            . '<TCKimlikNoDogrulaResult>false</TCKimlikNoDogrulaResult>'
            . '</TCKimlikNoDogrulaResponse>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $method = new \ReflectionMethod(TcKimlik::class, 'responseIndicatesSuccess');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null, $response));
    }
}
