<?php

namespace Behat\Mink\Tests;

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\WebAssert;

class WebAssertTest extends \PHPUnit_Framework_TestCase
{
    /** Cap timeout limit so that assertWrongAssertion() calls won't last 5 seconds */
    const TIMEOUT_LIMIT = 0.11;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $session;
    /**
     * @var WebAssert
     */
    private $assert;

    public function setUp()
    {
        $this->session = $this->getMockBuilder('Behat\\Mink\\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $this->session->expects($this->any())
            ->method('getDriver')
            ->will($this->returnValue($this->getMock('Behat\Mink\Driver\DriverInterface')));

        $this->assert = new WebAssert($this->session);
    }

    public function testAddressEquals()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            // This is necessary in order to mock the method called in the callback.
            ->setMethods(['getCurrentUrl'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                '',
                'http://example.com/script.php/sub/url?param=true#webapp/nav',
                '',
                '/sub/url#webapp/nav'
            ))
        ;

        $this->assertCorrectAssertion('addressEquals', ['/sub/url#webapp/nav']);
        $this->assertWrongAssertion(
            'addressEquals',
            ['sub_url', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current page is "/sub/url#webapp/nav", but "sub_url" expected.'
        );
    }

    public function testAddressEqualsEmptyPath()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMock()
        ;

        $this->session
            ->expects($this->once())
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $this->session
            ->expects($this->once())
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                '',
                'http://example.com'
            ))
        ;

        $this->assertCorrectAssertion('addressEquals', ['/', 0.15]);
    }

    public function testAddressEqualsEndingInScript()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                '',
                'http://example.com/script.php',
                'http://example.com/script.php',
                'http://example.com/script.php'
            ))
        ;

        $this->assertCorrectAssertion('addressEquals', ['/script.php']);
        $this->assertWrongAssertion(
            'addressEquals',
            ['/', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current page is "/script.php", but "/" expected.'
        );
    }

    public function testAddressNotEquals()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $this->session
            ->expects($this->exactly(3))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                '',
                'http://example.com/script.php/sub/url',
                'http://example.com/script.php/sub/url'
            ))
        ;

        $this->assertCorrectAssertion('addressNotEquals', ['sub_url']);
        $this->assertWrongAssertion(
            'addressNotEquals',
            ['/sub/url', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current page is "/sub/url", but should not be.'
        );
    }

    public function testAddressNotEqualsEndingInScript()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                '',
                'http://example.com/script.php',
                'http://example.com/script.php',
                'http://example.com/script.php'
            ))
        ;

        $this->assertCorrectAssertion('addressNotEquals', ['/']);
        $this->assertWrongAssertion(
            'addressNotEquals',
            ['/script.php', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current page is "/script.php", but should not be.'
        );
    }

    public function testAddressMatches()
    {
        $this->session
            ->expects($this->exactly(2))
            ->method('getCurrentUrl')
            ->will($this->returnValue('http://example.com/script.php/sub/url'))
        ;

        $this->assertCorrectAssertion('addressMatches', ['/su.*rl/']);
        $this->assertWrongAssertion(
            'addressMatches',
            ['/suburl/'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current page "/sub/url" does not match the regex "/suburl/".'
        );
    }

    public function testCookieEquals()
    {
        $this->session->
            expects($this->any())->
            method('getCookie')->
            will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('cookieEquals', ['foo', 'bar']);
        $this->assertWrongAssertion(
            'cookieEquals',
            ['bar', 'foo'],
            'Behat\Mink\Exception\ExpectationException',
            'Cookie "bar" value is "baz", but should be "foo".'
        );
    }

    public function testCookieExists()
    {
        $this->session->
            expects($this->any())->
            method('getCookie')->
            will($this->returnValueMap(
                [
                    ['foo', '1'],
                    ['bar', null],
                ]
            ));

        $this->assertCorrectAssertion('cookieExists', ['foo']);
        $this->assertWrongAssertion(
            'cookieExists',
            ['bar'],
            'Behat\Mink\Exception\ExpectationException',
            'Cookie "bar" is not set, but should be.'
        );
    }

    public function testStatusCodeEquals()
    {
        $this->session
            ->expects($this->exactly(2))
            ->method('getStatusCode')
            ->will($this->returnValue(200))
        ;

        $this->assertCorrectAssertion('statusCodeEquals', [200]);
        $this->assertWrongAssertion(
            'statusCodeEquals',
            [404],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current response status code is 200, but 404 expected.'
        );
    }

    public function testStatusCodeNotEquals()
    {
        $this->session
            ->expects($this->exactly(2))
            ->method('getStatusCode')
            ->will($this->returnValue(404))
        ;

        $this->assertCorrectAssertion('statusCodeNotEquals', [200]);
        $this->assertWrongAssertion(
            'statusCodeNotEquals',
            [404],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current response status code is 404, but should not be.'
        );
    }

    public function testResponseHeaderEquals()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderEquals', ['foo', 'bar']);
        $this->assertWrongAssertion(
            'responseHeaderEquals',
            ['bar', 'foo'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current response header "bar" is "baz", but "foo" expected.'
        );
    }

    public function testResponseHeaderNotEquals()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderNotEquals', ['foo', 'baz']);
        $this->assertWrongAssertion(
            'responseHeaderNotEquals',
            ['bar', 'baz'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Current response header "bar" is "baz", but should not be.'
        );
    }

    public function testResponseHeaderContains()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderContains', ['foo', 'ba']);
        $this->assertWrongAssertion(
            'responseHeaderContains',
            ['bar', 'bz'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The text "bz" was not found anywhere in the "bar" response header.'
        );
    }

    public function testResponseHeaderNotContains()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderNotContains', ['foo', 'bz']);
        $this->assertWrongAssertion(
            'responseHeaderNotContains',
            ['bar', 'ba'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The text "ba" was found in the "bar" response header, but it should not.'
        );
    }

    public function testResponseHeaderMatches()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderMatches', ['foo', '/ba(.*)/']);
        $this->assertWrongAssertion(
            'responseHeaderMatches',
            ['bar', '/b[^a]/'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The pattern "/b[^a]/" was not found anywhere in the "bar" response header.'
        );
    }

    public function testResponseHeaderNotMatches()
    {
        $this->session
            ->expects($this->any())
            ->method('getResponseHeader')
            ->will($this->returnValueMap(
                [
                    ['foo', 'bar'],
                    ['bar', 'baz'],
                ]
            ));

        $this->assertCorrectAssertion('responseHeaderNotMatches', ['foo', '/bz/']);
        $this->assertWrongAssertion(
            'responseHeaderNotMatches',
            ['bar', '/b[ab]z/'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The pattern "/b[ab]z/" was found in the text of the "bar" response header, but it should not.'
        );
    }

    public function testPageTextContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getText'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('getText')
            ->will($this->onConsecutiveCalls(
                'void',
                "Some  page\n\ttext",
                "Some  page\n\ttext",
                "Some  page\n\ttext"
            ))
        ;

        $this->assertCorrectAssertion('pageTextContains', ['PAGE text']);
        $this->assertWrongAssertion(
            'pageTextContains',
            ['html text', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ResponseTextException',
            'The text "html text" was not found anywhere in the text of the current page ("Some page text").'
        );
    }

    public function testPageTextNotContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('getText')
            ->will($this->returnValue("Some  html\n\ttext"))
        ;

        $this->assertCorrectAssertion('pageTextNotContains', ['PAGE text']);
        $this->assertWrongAssertion(
            'pageTextNotContains',
            ['HTML text'],
            'Behat\\Mink\\Exception\\ResponseTextException',
            'The text "HTML text" appears in the text of this page, but it should not.'
        );
    }

    public function testPageTextMatches()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getText'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('getText')
            ->will($this->onConsecutiveCalls(
                'void',
                'Some page text',
                'Some page text',
                'Some page text'
            ))
        ;

        $this->assertCorrectAssertion('pageTextMatches', ['/PA.E/i']);
        $this->assertWrongAssertion(
            'pageTextMatches',
            ['/html/', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ResponseTextException',
            'The pattern /html/ was not found anywhere in the text of the current page ("Some page text").'
        );
    }

    public function testPageTextNotMatches()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('getText')
            ->will($this->returnValue('Some html text'))
        ;

        $this->assertCorrectAssertion('pageTextNotMatches', ['/PA.E/i']);
        $this->assertWrongAssertion(
            'pageTextNotMatches',
            ['/HTML/i'],
            'Behat\\Mink\\Exception\\ResponseTextException',
            'The pattern /HTML/i was found in the text of the current page, but it should not.'
        );
    }

    public function testResponseContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('getContent')
            ->will($this->onConsecutiveCalls(
                'void',
                'Some page text',
                'Some page text',
                'Some page text'
            ))
        ;

        $this->assertCorrectAssertion('responseContains', ['PAGE text']);
        $this->assertWrongAssertion(
            'responseContains',
            ['html text', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The string "html text" was not found anywhere in the HTML response of the current page.'
        );
    }

    public function testResponseNotContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('getContent')
            ->will($this->returnValue('Some html text'))
        ;

        $this->assertCorrectAssertion('responseNotContains', ['PAGE text']);
        $this->assertWrongAssertion(
            'responseNotContains',
            ['HTML text'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The string "HTML text" appears in the HTML response of this page, but it should not.'
        );
    }

    public function testResponseMatches()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('getContent')
            ->will($this->onConsecutiveCalls(
                'void',
                'Some page text',
                'Some page text',
                'Some page text'
            ))
        ;

        $this->assertCorrectAssertion('responseMatches', ['/PA.E/i']);
        $this->assertWrongAssertion(
            'responseMatches',
            ['/html/', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The pattern /html/ was not found anywhere in the HTML response of the page.'
        );
    }

    public function testResponseNotMatches()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('getContent')
            ->will($this->returnValue('Some html text'))
        ;

        $this->assertCorrectAssertion('responseNotMatches', ['/PA.E/i']);
        $this->assertWrongAssertion(
            'responseNotMatches',
            ['/HTML/i'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The pattern /HTML/i was found in the HTML response of the page, but it should not.'
        );
    }

    public function testElementsCount()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findAll'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(3))
            ->method('findAll')
            ->with('css', 'h2 > span')
            ->will($this->onConsecutiveCalls(
                [0, 0],
                [1, 2],
                [1, 3]
            ))
        ;

        $this->assertCorrectAssertion('elementsCount', ['css', 'h2 > span', 2]);
        $this->assertWrongAssertion(
            'elementsCount',
            ['css', 'h2 > span', 3, null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            '2 elements matching css "h2 > span" found on the page, but should be 3.'
        );
    }

    public function testElementExists()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(8))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->onConsecutiveCalls(
                // assertCorrectAssertion().
                null,
                1,
                // assertWrongAssertion().
                null,
                null,
                // assertCorrectAssertion().
                null,
                1,
                // assertWrongAssertion().
                null,
                null
            ))
        ;

        $this->assertCorrectAssertion('elementExists', ['css', 'h2 > span']);
        $this->assertWrongAssertion(
            'elementExists',
            ['css', 'h2 > span',  null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementNotFoundException',
            'Element matching css "h2 > span" not found.'
        );

        $this->assertCorrectAssertion('elementExists', ['css', 'h2 > span', $page]);
        $this->assertWrongAssertion(
            'elementExists',
            ['css', 'h2 > span', $page, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementNotFoundException',
            'Element matching css "h2 > span" not found.'
        );
    }

    public function testElementExistsWithArrayLocator()
    {
        $container = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $this->session->expects($this->never())
            ->method('getPage')
        ;

        $container
            ->expects($this->exactly(3))
            ->method('find')
            ->with('named', ['element', 'Test'])
            ->will($this->onConsecutiveCalls(1, null, null))
        ;

        $this->assertCorrectAssertion('elementExists', ['named', ['element', 'Test'], $container]);
        $this->assertWrongAssertion(
            'elementExists',
            ['named', ['element', 'Test'], $container, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementNotFoundException',
            'Element with named "element Test" not found.'
        );
    }

    public function testElementNotExists()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->onConsecutiveCalls(null, 1, null, 1))
        ;

        $this->assertCorrectAssertion('elementNotExists', ['css', 'h2 > span']);
        $this->assertWrongAssertion(
            'elementNotExists',
            ['css', 'h2 > span'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'An element matching css "h2 > span" appears on this page, but it should not.'
        );

        $this->assertCorrectAssertion('elementNotExists', ['css', 'h2 > span', $page]);
        $this->assertWrongAssertion(
            'elementNotExists',
            ['css', 'h2 > span', $page],
            'Behat\\Mink\\Exception\\ExpectationException',
            'An element matching css "h2 > span" appears on this page, but it should not.'
        );
    }

    /**
     * @dataProvider getArrayLocatorFormats
     * @param mixed $selector
     * @param mixed $locator
     * @param mixed $expectedMessage
     */
    public function testElementNotExistsArrayLocator($selector, $locator, $expectedMessage)
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->once())
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->once())
            ->method('find')
            ->with($selector, $locator)
            ->will($this->returnValue(1))
        ;

        $this->assertWrongAssertion(
            'elementNotExists',
            [$selector, $locator],
            'Behat\\Mink\\Exception\\ExpectationException',
            $expectedMessage
        );
    }

    public function getArrayLocatorFormats()
    {
        return [
            'named' => [
                'named',
                ['button', 'Test'],
                'An button matching locator "Test" appears on this page, but it should not.',
            ],
            'custom' => [
                'custom',
                ['test', 'foo'],
                'An element matching custom "test foo" appears on this page, but it should not.',
            ],
        ];
    }

    public function testElementTextContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getText'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(4))
            ->method('getText')
            ->will($this->onConsecutiveCalls(
                '',
                'element text',
                '',
                ''
            ))
        ;

        $this->assertCorrectAssertion('elementTextContains', ['css', 'h2 > span', 'text']);
        $this->assertWrongAssertion(
            'elementTextContains',
            ['css', 'h2 > span', 'html', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The text "html" was not found in the text of the element matching css "h2 > span".'
        );
    }

    public function testElementTextNotContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getText'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('getText')
            ->will($this->returnValue('element text'))
        ;

        $this->assertCorrectAssertion('elementTextNotContains', ['css', 'h2 > span', 'html']);
        $this->assertWrongAssertion(
            'elementTextNotContains',
            ['css', 'h2 > span', 'text', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The text "text" appears in the text of the element matching css "h2 > span", but it should not.'
        );
    }

    public function testElementContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getHtml'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(4))
            ->method('getHtml')
            ->will($this->returnValue('element html'))
            ->will($this->onConsecutiveCalls(
                '',
                'element html',
                '',
                ''
            ))
        ;

        $this->assertCorrectAssertion('elementContains', ['css', 'h2 > span', 'html']);
        $this->assertWrongAssertion(
            'elementContains',
            ['css', 'h2 > span', 'text', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The string "text" was not found in the HTML of the element matching css "h2 > span".'
        );
    }

    public function testElementNotContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getHtml'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('getHtml')
            ->will($this->onConsecutiveCalls(
                '',
                'element html',
                '',
                ''
            ))
        ;

        $this->assertCorrectAssertion('elementNotContains', ['css', 'h2 > span', 'text']);
        $this->assertWrongAssertion(
            'elementNotContains',
            ['css', 'h2 > span', 'html', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The string "html" appears in the HTML of the element matching css "h2 > span", but it should not.'
        );
    }

    public function testElementAttributeContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['hasAttribute', 'getAttribute'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(4))
            ->method('hasAttribute')
            ->will($this->returnValue(true))
        ;

        $element
            ->expects($this->exactly(5))
            ->method('getAttribute')
            ->with('name')
            ->will($this->returnValue('foo'))
        ;

        $this->assertCorrectAssertion('elementAttributeContains', ['css', 'h2 > span', 'name', 'foo']);
        $this->assertWrongAssertion(
            'elementAttributeContains',
            ['css', 'h2 > span', 'name', 'bar', self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementHtmlException',
            'The text "bar" was not found in the attribute "name" of the element matching css "h2 > span".'
        );
    }

    public function testElementAttributeExists()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['hasAttribute'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->at(0))
            ->method('hasAttribute')
            ->with('name')
            ->will($this->returnValue(true))
        ;

        $element
            ->expects($this->at(1))
            ->method('hasAttribute')
            ->with('name')
            ->will($this->returnValue(false))
        ;

        $this->assertCorrectAssertion('elementAttributeExists', ['css', 'h2 > span', 'name']);
        $this->assertWrongAssertion(
            'elementAttributeExists',
            ['css', 'h2 > span', 'name', null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementHtmlException',
            'The attribute "name" was not found in the element matching css "h2 > span".'
        );
    }

    public function testElementAttributeNotContains()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['hasAttribute', 'getAttribute'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('find')
            ->with('css', 'h2 > span')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('hasAttribute')
            ->will($this->returnValue(true))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('getAttribute')
            ->with('name')
            ->will($this->returnValue('foo'))
        ;

        $this->assertCorrectAssertion('elementAttributeNotContains', ['css', 'h2 > span', 'name', 'bar']);
        $this->assertWrongAssertion(
            'elementAttributeNotContains',
            ['css', 'h2 > span', 'name', 'foo'],
            'Behat\\Mink\\Exception\\ElementHtmlException',
            'The text "foo" was found in the attribute "name" of the element matching css "h2 > span".'
        );
    }

    public function testFieldExists()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findField'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(3))
            ->method('findField')
            ->with('username')
            ->will($this->onConsecutiveCalls(
                $element,
                null,
                null
            ))
        ;

        $this->assertCorrectAssertion('fieldExists', ['username']);
        $this->assertWrongAssertion(
            'fieldExists',
            ['username', null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ElementNotFoundException',
            'Form field with id|name|label|value "username" not found.'
        );
    }

    public function testFieldNotExists()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('findField')
            ->with('username')
            ->will($this->onConsecutiveCalls(null, $element))
        ;

        $this->assertCorrectAssertion('fieldNotExists', ['username']);
        $this->assertWrongAssertion(
            'fieldNotExists',
            ['username'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'A field "username" appears on this page, but it should not.'
        );
    }

    public function testFieldValueEquals()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findField'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('findField')
            ->with('username')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(7))
            ->method('getValue')
            ->will($this->returnValue(234))
        ;

        $this->assertCorrectAssertion('fieldValueEquals', ['username', 234]);
        $this->assertWrongAssertion(
            'fieldValueEquals',
            ['username', 235, null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The field "username" value is "234", but "235" expected.'
        );
        $this->assertWrongAssertion(
            'fieldValueEquals',
            ['username', 23, null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The field "username" value is "234", but "23" expected.'
        );
        $this->assertWrongAssertion(
            'fieldValueEquals',
            ['username', '', null, self::TIMEOUT_LIMIT],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The field "username" value is "234", but "" expected.'
        );
    }

    public function testFieldValueNotEquals()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findField'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(4))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(4))
            ->method('findField')
            ->with('username')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(4))
            ->method('getValue')
            ->will($this->returnValue(235))
        ;

        $this->assertCorrectAssertion('fieldValueNotEquals', ['username', 234]);
        $this->assertWrongAssertion(
            'fieldValueNotEquals',
            ['username', 235],
            'Behat\\Mink\\Exception\\ExpectationException',
            'The field "username" value is "235", but it should not be.'
        );
        $this->assertCorrectAssertion('fieldValueNotEquals', ['username', 23]);
        $this->assertCorrectAssertion('fieldValueNotEquals', ['username', '']);
    }

    public function testCheckboxChecked()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findField'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('findField')
            ->with('remember_me')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('isChecked')
            ->will($this->onConsecutiveCalls(true, false))
        ;

        $this->assertCorrectAssertion('checkboxChecked', ['remember_me']);
        $this->assertWrongAssertion(
            'checkboxChecked',
            ['remember_me'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Checkbox "remember_me" is not checked, but it should be.'
        );
    }

    public function testCheckboxNotChecked()
    {
        $page = $this->getMockBuilder('Behat\\Mink\\Element\\DocumentElement')
            ->disableOriginalConstructor()
            ->setMethods(['findField'])
            ->getMock()
        ;

        $element = $this->getMockBuilder('Behat\\Mink\\Element\\NodeElement')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->session
            ->expects($this->exactly(2))
            ->method('getPage')
            ->will($this->returnValue($page))
        ;

        $page
            ->expects($this->exactly(2))
            ->method('findField')
            ->with('remember_me')
            ->will($this->returnValue($element))
        ;

        $element
            ->expects($this->exactly(2))
            ->method('isChecked')
            ->will($this->onConsecutiveCalls(false, true))
        ;

        $this->assertCorrectAssertion('checkboxNotChecked', ['remember_me']);
        $this->assertWrongAssertion(
            'checkboxNotChecked',
            ['remember_me'],
            'Behat\\Mink\\Exception\\ExpectationException',
            'Checkbox "remember_me" is checked, but it should not be.'
        );
    }

    private function assertCorrectAssertion($assertion, $arguments)
    {
        try {
            call_user_func_array([$this->assert, $assertion], $arguments);
        } catch (ExpectationException $e) {
            $this->fail('Correct assertion should not throw an exception: '.$e->getMessage());
        }
    }

    private function assertWrongAssertion($assertion, $arguments, $exceptionClass, $exceptionMessage)
    {
        if ('Behat\Mink\Exception\ExpectationException' !== $exceptionClass && !is_subclass_of($exceptionClass, 'Behat\Mink\Exception\ExpectationException')) {
            throw new \LogicException('Wrong expected exception for the failed assertion. It should be a Behat\Mink\Exception\ExpectationException.');
        }

        try {
            call_user_func_array([$this->assert, $assertion], $arguments);
            $this->fail('Wrong assertion should throw an exception');
        } catch (ExpectationException $e) {
            $this->assertInstanceOf($exceptionClass, $e);
            $this->assertSame($exceptionMessage, $e->getMessage());
        }
    }
}
