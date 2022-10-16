<?php

namespace Tests;

use Webklex\PHPIMAP\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testRfc822ParseHeaders()
    {
        $mock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $config = new \ReflectionProperty($mock, 'config');
        $config->setValue($mock, ['rfc822' => true]);

        $mockHeader = "Content-Type: text/csv; charset=WINDOWS-1252;  name*0=\"TH_Is_a_F ile name example 20221013.c\"; name*1=sv\r\nContent-Transfer-Encoding: quoted-printable\r\nContent-Disposition: attachment; filename*0=\"TH_Is_a_F ile name example 20221013.c\"; filename*1=\"sv\"\r\n";

        $expected = new \stdClass();
        $expected->content_type = 'text/csv; charset=WINDOWS-1252;  name*0="TH_Is_a_F ile name example 20221013.c"; name*1=sv';
        $expected->content_transfer_encoding = 'quoted-printable';
        $expected->content_disposition = 'attachment; filename*0="TH_Is_a_F ile name example 20221013.c"; filename*1="sv"';

        $this->assertEquals($expected, $mock->rfc822_parse_headers($mockHeader));
    }

    public function testExtractHeaderExtensions()
    {
        $mock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $method = new \ReflectionMethod($mock, 'extractHeaderExtensions');
        $method->setAccessible(true);

        $mockAttributes = [
            'content_type' => 'text/csv; charset=WINDOWS-1252;  name*0="TH_Is_a_F ile name example 20221013.c"; name*1=sv',
            'content_transfer_encoding' => 'quoted-printable',
            'content_disposition' => 'attachment; filename*0="TH_Is_a_F ile name example 20221013.c"; filename*1="sv"; attribute_test=attribute_test_value',
        ];

        $attributes = new \ReflectionProperty($mock, 'attributes');
        $attributes->setValue($mock, $mockAttributes);

        $method->invoke($mock);

        $this->assertArrayHasKey('filename', $mock->getAttributes());
        $this->assertArrayNotHasKey('filename*0', $mock->getAttributes());
        $this->assertEquals('TH_Is_a_F ile name example 20221013.csv', $mock->get('filename'));

        $this->assertArrayHasKey('name', $mock->getAttributes());
        $this->assertArrayNotHasKey('name*0', $mock->getAttributes());
        $this->assertEquals('TH_Is_a_F ile name example 20221013.csv', $mock->get('name'));

        $this->assertArrayHasKey('content_type', $mock->getAttributes());
        $this->assertEquals('text/csv', $mock->get('content_type'));

        $this->assertArrayHasKey('charset', $mock->getAttributes());
        $this->assertEquals('WINDOWS-1252', $mock->get('charset'));

        $this->assertArrayHasKey('content_transfer_encoding', $mock->getAttributes());
        $this->assertEquals('quoted-printable', $mock->get('content_transfer_encoding'));

        $this->assertArrayHasKey('content_disposition', $mock->getAttributes());
        $this->assertEquals('attachment', $mock->get('content_disposition'));
        $this->assertEquals('quoted-printable', $mock->get('content_transfer_encoding'));

        $this->assertArrayHasKey('attribute_test', $mock->getAttributes());
        $this->assertEquals('attribute_test_value', $mock->get('attribute_test'));
    }
}
