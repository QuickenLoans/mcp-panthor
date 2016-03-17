<?php
/**
 * @copyright ©2005—2016 Quicken Loans Inc. All rights reserved. Trade Secret, Confidential and Proprietary. Any
 *     dissemination outside of Quicken Loans is strictly prohibited.
 */

namespace QL\Panthor\Utility;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Cookies;

class CookieToolTest extends \PHPUnit_Framework_TestCase
{

    public function testGetNoCookies()
    {
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn([]);
        $cookies = new CookieTool();
        $this->assertInstanceOf(Cookies::class, $cookies->getCookies($response));
        $this->assertEquals([], $cookies->getRawCookies($response));
    }

    private function loadCookies($rawCookies)
    {
        $cookies = new Cookies();
        foreach ($rawCookies as $name => $rawCookie){
            $cookies->set($name, $rawCookie);
        }
        return $cookies;
    }

    public function testGetCookiesFromHeaders()
    {
        $rawCookies = [
            'stuff' => 'thing',
            'mine' => 'yours'
        ];
        $cookies = $this->loadCookies($rawCookies);
        $cookieHeaders = $cookies->toHeaders();
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($cookieHeaders);
        $cookies = new CookieTool();
        $this->assertInstanceOf(Cookies::class, $cookies->getCookies($response));
        $this->assertEquals($rawCookies, $cookies->getRawCookies($response));
    }

    public function testGetCookiesFromKeys()
    {
        $rawCookies = [
            'stuff' => 'thing',
            'mine' => 'yours'
        ];
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($rawCookies);
        $cookies = new CookieTool();
        $this->assertInstanceOf(Cookies::class, $cookies->getCookies($response));
        $this->assertEquals($rawCookies, $cookies->getRawCookies($response));
    }

    public function testSetNoCookies()
    {
        $rawCookies = [
            'stuff' => 'thing',
            'mine' => 'yours'
        ];
        $builtCookies = [
            'stuff' => '',
            'mine' => ''
        ];
        $cookies = $this->loadCookies($rawCookies);
        $cookieHeaders = $cookies->toHeaders();
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($cookieHeaders);

        $newCookies = [];

        $response->shouldReceive('withHeader')
            ->withArgs(['Set-Cookie', $this->loadCookies($builtCookies)->toHeaders()])
            ->andReturn($response);

        $cookies = new CookieTool();
        $cookies->setCookies($response, new Cookies($newCookies));
    }

    public function testSetSomeCookies()
    {
        $rawCookies = [
            'stuff' => 'thing',
            'mine' => 'yours'
        ];
        $newCookies = [
            'blah' => 'blah'
        ];
        $builtCookies = [
            'stuff' => '',
            'mine' => '',
            'blah' => 'blah'
        ];
        $cookies = $this->loadCookies($rawCookies);
        $cookieHeaders = $cookies->toHeaders();
        $response = \Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getHeader')
            ->with('Set-Cookie')
            ->andReturn($cookieHeaders);

        $response->shouldReceive('withHeader')
            ->withArgs(['Set-Cookie', $this->loadCookies($builtCookies)->toHeaders()])
            ->andReturn($response);

        $cookies = new CookieTool();
        $cookies->setCookies($response, $this->loadCookies($newCookies));
    }
}
