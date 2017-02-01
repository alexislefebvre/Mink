<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\Element;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ElementTextException;

/**
 * Mink web assertions tool.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class WebAssert
{
    protected $session;

    /**
     * Initializes assertion engine.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Checks that current session address is equals to provided one.
     *
     * @param string $page
     *
     * @throws ExpectationException
     */
    public function addressEquals($page, $timeout = 10)
    {
        $cleanedUrl = $this->cleanUrl($page);
        $message = sprintf('Current page is "%s", but "%s" expected.', $this->getCurrentUrlPath(), $this->cleanUrl($page));
        $this->assert(
            $this->session->getPage()->waitFor($timeout, function () use ($cleanedUrl) {
                return $this->getCurrentUrlPath() === $cleanedUrl;
            }), $message
        );
    }

    /**
     * Checks that current session address is not equals to provided one.
     *
     * @param string $page
     *
     * @throws ExpectationException
     */
    public function addressNotEquals($page, $timeout = 10)
    {
        $cleanedUrl = $this->cleanUrl($page);
        $message = sprintf('Current page is "%s", but should not be.', $this->getCurrentUrlPath());
        $this->assert(
            $this->session->getPage()->waitFor($timeout, function () use ($cleanedUrl) {
                return $this->getCurrentUrlPath() !== $cleanedUrl;
            }), $message
        );
    }

    /**
     * Checks that current session address matches regex.
     *
     * @param string $regex
     *
     * @throws ExpectationException
     */
    public function addressMatches($regex)
    {
        $actual = $this->getCurrentUrlPath();
        $message = sprintf('Current page "%s" does not match the regex "%s".', $actual, $regex);

        $this->assert((bool) preg_match($regex, $actual), $message);
    }

    /**
     * Checks that specified cookie exists and its value equals to a given one.
     *
     * @param string $name  cookie name
     * @param string $value cookie value
     *
     * @throws ExpectationException
     */
    public function cookieEquals($name, $value)
    {
        $this->cookieExists($name);

        $actualValue = $this->session->getCookie($name);
        $message = sprintf('Cookie "%s" value is "%s", but should be "%s".', $name, $actualValue, $value);

        $this->assert($actualValue == $value, $message);
    }

    /**
     * Checks that specified cookie exists.
     *
     * @param string $name cookie name
     *
     * @throws ExpectationException
     */
    public function cookieExists($name)
    {
        $message = sprintf('Cookie "%s" is not set, but should be.', $name);
        $this->assert($this->session->getCookie($name) !== null, $message);
    }

    /**
     * Checks that current response code equals to provided one.
     *
     * @param int $code
     *
     * @throws ExpectationException
     */
    public function statusCodeEquals($code)
    {
        $actual = $this->session->getStatusCode();
        $message = sprintf('Current response status code is %d, but %d expected.', $actual, $code);

        $this->assert(intval($code) === intval($actual), $message);
    }

    /**
     * Checks that current response code not equals to provided one.
     *
     * @param int $code
     *
     * @throws ExpectationException
     */
    public function statusCodeNotEquals($code)
    {
        $actual = $this->session->getStatusCode();
        $message = sprintf('Current response status code is %d, but should not be.', $actual);

        $this->assert(intval($code) !== intval($actual), $message);
    }

    /**
     * Checks that current response header equals value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws ExpectationException
     */
    public function responseHeaderEquals($name, $value)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf('Current response header "%s" is "%s", but "%s" expected.', $name, $actual, $value);

        $this->assert($value === $actual, $message);
    }

    /**
     * Checks that current response header does not equal value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws ExpectationException
     */
    public function responseHeaderNotEquals($name, $value)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf('Current response header "%s" is "%s", but should not be.', $name, $actual, $value);

        $this->assert($value !== $actual, $message);
    }

    /**
     * Checks that current response header contains value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws ExpectationException
     */
    public function responseHeaderContains($name, $value)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf('The text "%s" was not found anywhere in the "%s" response header.', $value, $name);

        $this->assert(false !== stripos($actual, $value), $message);
    }

    /**
     * Checks that current response header does not contain value.
     *
     * @param string $name
     * @param string $value
     *
     * @throws ExpectationException
     */
    public function responseHeaderNotContains($name, $value)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf('The text "%s" was found in the "%s" response header, but it should not.', $value, $name);

        $this->assert(false === stripos($actual, $value), $message);
    }

    /**
     * Checks that current response header matches regex.
     *
     * @param string $name
     * @param string $regex
     *
     * @throws ExpectationException
     */
    public function responseHeaderMatches($name, $regex)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf('The pattern "%s" was not found anywhere in the "%s" response header.', $regex, $name);

        $this->assert((bool) preg_match($regex, $actual), $message);
    }

    /**
     * Checks that current response header does not match regex.
     *
     * @param string $name
     * @param string $regex
     *
     * @throws ExpectationException
     */
    public function responseHeaderNotMatches($name, $regex)
    {
        $actual = $this->session->getResponseHeader($name);
        $message = sprintf(
            'The pattern "%s" was found in the text of the "%s" response header, but it should not.',
            $regex,
            $name
        );

        $this->assert(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that current page contains text.
     *
     * @param string $text
     * @param integer $timeout
     *
     * @throws ResponseTextException
     */
    public function pageTextContains($text, $timeout = 10)
    {
        $regex  = '/'.preg_quote($text, '/').'/ui';
        $actual = null;
        $callback = function (ElementInterface $givenNode) use ($regex, &$actual) {
            $actual = $givenNode->getText();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            return (bool) preg_match($regex, $actual);
        };

        $message = sprintf('The text "%s" was not found anywhere in the text of the current page. Found = %s.', $text, $actual);
        $this->assertResponseText($this->session->getPage()->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that current page does not contains text.
     *
     * @param string $text
     *
     * @throws ResponseTextException
     */
    public function pageTextNotContains($text)
    {
        $actual = $this->session->getPage()->getText();
        $actual = preg_replace('/\s+/u', ' ', $actual);
        $regex = '/'.preg_quote($text, '/').'/ui';
        $message = sprintf('The text "%s" appears in the text of this page, but it should not.', $text);

        $this->assertResponseText(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that current page text matches regex.
     *
     * @param string $regex
     * @param integer $timeout
     *
     * @throws ResponseTextException
     */
    public function pageTextMatches($regex, $timeout = 10)
    {
        $message = sprintf('The pattern %s was not found anywhere in the text of the current page.', $regex);
        $callback = function (ElementInterface $givenNode) use ($regex) {
            $actual = $givenNode->getText();
            return (bool)preg_match($regex, $actual);
        };
        $this->assertResponseText($this->session->getPage()->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that current page text does not matches regex.
     *
     * @param string $regex
     *
     * @throws ResponseTextException
     */
    public function pageTextNotMatches($regex)
    {
        $actual = $this->session->getPage()->getText();
        $message = sprintf('The pattern %s was found in the text of the current page, but it should not.', $regex);

        $this->assertResponseText(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that page HTML (response content) contains text.
     *
     * @param string $text
     * @param integer $timeout
     *
     * @throws ExpectationException
     */
    public function responseContains($text, $timeout = 10)
    {
        $regex = '/'.preg_quote($text, '/').'/ui';
        $message = sprintf('The string "%s" was not found anywhere in the HTML response of the current page.', $text);
        $callback = function (ElementInterface $givenNode) use ($regex) {
            $actual = $givenNode->getContent();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            return (bool) preg_match($regex, $actual);
        };

        $this->assert($this->session->getPage()->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that page HTML (response content) does not contains text.
     *
     * @param string $text
     *
     * @throws ExpectationException
     */
    public function responseNotContains($text)
    {
        $actual = $this->session->getPage()->getContent();
        $regex = '/'.preg_quote($text, '/').'/ui';
        $message = sprintf('The string "%s" appears in the HTML response of this page, but it should not.', $text);

        $this->assert(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that page HTML (response content) matches regex.
     *
     * @param string  $regex
     * @param integer $timeout
     *
     * @throws ExpectationException
     */
    public function responseMatches($regex, $timeout = 10)
    {
        $message = sprintf('The pattern %s was not found anywhere in the HTML response of the page.', $regex);
        $callback = function (ElementInterface $givenNode) use ($regex) {
            $actual = $givenNode->getContent();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            return (bool) preg_match($regex, $actual);
        };

        $this->assert($this->session->getPage()->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that page HTML (response content) does not matches regex.
     *
     * @param $regex
     *
     * @throws ExpectationException
     */
    public function responseNotMatches($regex)
    {
        $actual = $this->session->getPage()->getContent();
        $message = sprintf('The pattern %s was found in the HTML response of the page, but it should not.', $regex);

        $this->assert(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that there is specified number of specific elements on the page.
     *
     * @param string           $selectorType element selector type (css, xpath)
     * @param string|array     $selector     element selector
     * @param int              $count        expected count
     * @param ElementInterface $container    document to check against
     * @param integer          $timeout      time to wait until fail
     *
     * @throws ExpectationException
     */
    public function elementsCount($selectorType, $selector, $count, ElementInterface $container = null, $timeout = 10)
    {
        $container = $container ?: $this->session->getPage();
        $nodes = array();
        $callback = function (ElementInterface $givenNode) use ($count, $selectorType, $selector, &$nodes) {
            $nodes = $givenNode->findAll($selectorType, $selector);

            return intval($count) === count($nodes);
        };
        $result = $container->waitFor($timeout, $callback);
        $message = sprintf(
            '%d %s found on the page, but should be %d.',
            count($nodes),
            $this->getMatchingElementRepresentation($selectorType, $selector, count($nodes) > 1),
            $count
        );

        $this->assert((bool) $result, $message);


    }

    /**
     * Checks that specific element exists on the current page.
     *
     * @param string           $selectorType element selector type (css, xpath)
     * @param string|array     $selector     element selector
     * @param ElementInterface $container    document to check against
     * @param integer          $timeout      time to wait until fail
     *
     * @return NodeElement
     *
     * @throws ElementNotFoundException
     */
    public function elementExists($selectorType, $selector, ElementInterface $container = null, $timeout = 10)
    {
        $container = $container ?: $this->session->getPage();
        $nodes = array();
        $callback = function (ElementInterface $givenNode) use ($selectorType, $selector, &$nodes) {
            $node = $givenNode->find($selectorType, $selector);

            return $node;
        };

        if (null === $node = $container->waitFor($timeout, $callback)) {
            if (is_array($selector)) {
                $selector = implode(' ', $selector);
            }

            throw new ElementNotFoundException($this->session->getDriver(), 'element', $selectorType, $selector);
        }

        return $node;
    }

    /**
     * Checks that specific element does not exists on the current page.
     *
     * @param string           $selectorType element selector type (css, xpath)
     * @param string|array     $selector     element selector
     * @param ElementInterface $container    document to check against
     *
     * @throws ExpectationException
     */
    public function elementNotExists($selectorType, $selector, ElementInterface $container = null)
    {
        $container = $container ?: $this->session->getPage();
        $node = $container->find($selectorType, $selector);

        $message = sprintf(
            'An %s appears on this page, but it should not.',
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );

        $this->assert(null === $node, $message);
    }

    /**
     * Checks that specific element contains text.
     *
     * @param string       $selectorType element selector type (css, xpath)
     * @param string|array $selector     element selector
     * @param string       $text         expected text
     * @param integer      $timeout      time to wait until fail
     *
     * @throws ElementTextException
     */
    public function elementTextContains($selectorType, $selector, $text, $timeout = 10)
    {
        $regex = '/'.preg_quote($text, '/').'/ui';
        $message = sprintf(
            'The text "%s" was not found in the text of the %s.',
            $text,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );
        $callback = function (ElementInterface $givenNode) use ($regex) {
            $actual = $givenNode->getText();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            return (bool) preg_match($regex, $actual);
        };

        $element = $this->elementExists($selectorType, $selector);
        $this->assertResponseText($element->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that specific element does not contains text.
     *
     * @param string       $selectorType element selector type (css, xpath)
     * @param string|array $selector     element selector
     * @param string       $text         expected text
     *
     * @throws ElementTextException
     */
    public function elementTextNotContains($selectorType, $selector, $text)
    {
        $element = $this->elementExists($selectorType, $selector);
        $actual = $element->getText();
        $regex = '/'.preg_quote($text, '/').'/ui';

        $message = sprintf(
            'The text "%s" appears in the text of the %s, but it should not.',
            $text,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );

        $this->assertElementText(!preg_match($regex, $actual), $message, $element);
    }

    /**
     * Checks that specific element contains HTML.
     *
     * @param string       $selectorType element selector type (css, xpath)
     * @param string|array $selector     element selector
     * @param string       $html         expected text
     * @param integer      $timeout      time to wait until fail
     *
     * @throws ElementHtmlException
     */
    public function elementContains($selectorType, $selector, $html, $timeout = 10)
    {
        $regex = '/'.preg_quote($html, '/').'/ui';
        $message = sprintf(
            'The string "%s" was not found in the HTML of the %s.',
            $html,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );
        $callback = function (ElementInterface $givenNode) use ($regex) {
            $actual = $givenNode->getHtml();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            return (bool) preg_match($regex, $actual);
        };

        $element = $this->elementExists($selectorType, $selector);
        $this->assertElement($element->waitFor($timeout, $callback), $message, $element);
    }

    /**
     * Checks that specific element does not contains HTML.
     *
     * @param string       $selectorType element selector type (css, xpath)
     * @param string|array $selector     element selector
     * @param string       $html         expected text
     *
     * @throws ElementHtmlException
     */
    public function elementNotContains($selectorType, $selector, $html)
    {
        $element = $this->elementExists($selectorType, $selector);
        $actual = $element->getHtml();
        $regex = '/'.preg_quote($html, '/').'/ui';

        $message = sprintf(
            'The string "%s" appears in the HTML of the %s, but it should not.',
            $html,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );

        $this->assertElement(!preg_match($regex, $actual), $message, $element);
    }

    /**
     * Checks that an attribute exists in an element.
     *
     * @param string       $selectorType
     * @param string|array $selector
     * @param string       $attribute
     * @param integer      $timeout      time to wait until fail
     *
     * @return NodeElement
     *
     * @throws ElementHtmlException
     */
    public function elementAttributeExists($selectorType, $selector, $attribute, $timeout = 10)
    {
        $message = sprintf(
            'The attribute "%s" was not found in the %s.',
            $attribute,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );
        $callback = function (ElementInterface $givenNode) use ($attribute) {

            return $givenNode->hasAttribute($attribute);
        };

        $element = $this->elementExists($selectorType, $selector);
        $this->assertElement($element->waitFor($timeout, $callback), $message, $element);

        return $element;
    }

    /**
     * Checks that an attribute of a specific elements contains text.
     *
     * @param string       $selectorType
     * @param string|array $selector
     * @param string       $attribute
     * @param string       $text
     * @param integer      $timeout      time to wait until fail
     *
     * @throws ElementHtmlException
     */
    public function elementAttributeContains($selectorType, $selector, $attribute, $text, $timeout = 10)
    {
        $element = $this->elementAttributeExists($selectorType, $selector, $attribute);
        $actual = $element->getAttribute($attribute);
        $regex = '/'.preg_quote($text, '/').'/ui';

        $message = sprintf(
            'The text "%s" was not found in the attribute "%s" of the %s.',
            $text,
            $attribute,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );
        $callback = function (ElementInterface $givenNode) use ($regex, $attribute) {
            $actual  = $givenNode->getAttribute($attribute);

            return (bool) preg_match($regex, $actual);
        };

        $element = $this->elementAttributeExists($selectorType, $selector, $attribute);
        $this->assertElement($element->waitFor($timeout, $callback), $message, $element);
    }

    /**
     * Checks that an attribute of a specific elements does not contain text.
     *
     * @param string       $selectorType
     * @param string|array $selector
     * @param string       $attribute
     * @param string       $text
     *
     * @throws ElementHtmlException
     */
    public function elementAttributeNotContains($selectorType, $selector, $attribute, $text)
    {
        $element = $this->elementAttributeExists($selectorType, $selector, $attribute);
        $actual = $element->getAttribute($attribute);
        $regex = '/'.preg_quote($text, '/').'/ui';

        $message = sprintf(
            'The text "%s" was found in the attribute "%s" of the %s.',
            $text,
            $attribute,
            $this->getMatchingElementRepresentation($selectorType, $selector)
        );

        $this->assertElement(!preg_match($regex, $actual), $message, $element);
    }

    /**
     * Checks that specific field exists on the current page.
     *
     * @param string             $field     field id|name|label|value
     * @param TraversableElement $container document to check against
     *
     * @return NodeElement
     *
     * @throws ElementNotFoundException
     */
    public function fieldExists($field, TraversableElement $container = null, $timeout = 10)
    {
        $container = $container ?: $this->session->getPage();
        $callback = function (ElementInterface $givenNode) use ($field) {
            $node = $givenNode->findField($field);

            return $node;
        };

        if (null === $node = $container->waitFor($timeout, $callback)) {
            throw new ElementNotFoundException($this->session, 'form field', 'id|name|label|value', $field);
        }

        return $node;
    }

    /**
     * Checks that specific field does not exists on the current page.
     *
     * @param string             $field     field id|name|label|value
     * @param TraversableElement $container document to check against
     *
     * @throws ExpectationException
     */
    public function fieldNotExists($field, TraversableElement $container = null)
    {
        $container = $container ?: $this->session->getPage();
        $node = $container->findField($field);

        $this->assert(null === $node, sprintf('A field "%s" appears on this page, but it should not.', $field));
    }

    /**
     * Checks that specific field have provided value.
     *
     * @param string             $field     field id|name|label|value
     * @param string             $value     field value
     * @param TraversableElement $container document to check against
     *
     * @throws ExpectationException
     */
    public function fieldValueEquals($field, $value, TraversableElement $container = null, $timeout = 10)
    {
        $node = $this->fieldExists($field, $container);
        $regex = '/^'.preg_quote($value, '/').'$/ui';
        $message = null;
        $callback = function (ElementInterface $givenNode) use ($regex, &$message, $field, $value) {
            $actual = $givenNode->getValue();
            $message = sprintf('The field "%s" value is "%s", but "%s" expected.', $field, $actual, $value);

            return (bool) preg_match($regex, $actual);
        };

        $this->assert($node->waitFor($timeout, $callback), $message);
    }

    /**
     * Checks that specific field have provided value.
     *
     * @param string             $field     field id|name|label|value
     * @param string             $value     field value
     * @param TraversableElement $container document to check against
     *
     * @throws ExpectationException
     */
    public function fieldValueNotEquals($field, $value, TraversableElement $container = null)
    {
        $node = $this->fieldExists($field, $container);
        $actual = $node->getValue();
        $regex = '/^'.preg_quote($value, '/').'$/ui';

        $message = sprintf('The field "%s" value is "%s", but it should not be.', $field, $actual);

        $this->assert(!preg_match($regex, $actual), $message);
    }

    /**
     * Checks that specific checkbox is checked.
     *
     * @param string             $field     field id|name|label|value
     * @param TraversableElement $container document to check against
     *
     * @throws ExpectationException
     */
    public function checkboxChecked($field, TraversableElement $container = null)
    {
        $node = $this->fieldExists($field, $container);

        $this->assert($node->isChecked(), sprintf('Checkbox "%s" is not checked, but it should be.', $field));
    }

    /**
     * Checks that specific checkbox is unchecked.
     *
     * @param string             $field     field id|name|label|value
     * @param TraversableElement $container document to check against
     *
     * @throws ExpectationException
     */
    public function checkboxNotChecked($field, TraversableElement $container = null)
    {
        $node = $this->fieldExists($field, $container);

        $this->assert(!$node->isChecked(), sprintf('Checkbox "%s" is checked, but it should not be.', $field));
    }

    /**
     * Gets current url of the page.
     *
     * @return string
     */
    protected function getCurrentUrlPath()
    {
        return $this->cleanUrl($this->session->getCurrentUrl());
    }

    /**
     * Trims scriptname from the URL.
     *
     * @param string $url
     *
     * @return string
     */
    protected function cleanUrl($url)
    {
        $parts = parse_url($url);
        $fragment = empty($parts['fragment']) ? '' : '#'.$parts['fragment'];
        $path = empty($parts['path']) ? '/' : $parts['path'];

        return preg_replace('/^\/[^\.\/]+\.php\//', '/', $path).$fragment;
    }

    /**
     * Asserts a condition.
     *
     * @param bool   $condition
     * @param string $message   Failure message
     *
     * @throws ExpectationException when the condition is not fulfilled
     */
    private function assert($condition, $message)
    {
        if ($condition) {
            return;
        }

        throw new ExpectationException($message, $this->session->getDriver());
    }

    /**
     * Asserts a condition involving the response text.
     *
     * @param bool   $condition
     * @param string $message   Failure message
     *
     * @throws ResponseTextException when the condition is not fulfilled
     */
    private function assertResponseText($condition, $message)
    {
        if ($condition) {
            return;
        }

        throw new ResponseTextException($message, $this->session->getDriver());
    }

    /**
     * Asserts a condition on an element.
     *
     * @param bool    $condition
     * @param string  $message   Failure message
     * @param Element $element
     *
     * @throws ElementHtmlException when the condition is not fulfilled
     */
    private function assertElement($condition, $message, Element $element)
    {
        if ($condition) {
            return;
        }

        throw new ElementHtmlException($message, $this->session->getDriver(), $element);
    }

    /**
     * Asserts a condition involving the text of an element.
     *
     * @param bool    $condition
     * @param string  $message   Failure message
     * @param Element $element
     *
     * @throws ElementTextException when the condition is not fulfilled
     */
    private function assertElementText($condition, $message, Element $element)
    {
        if ($condition) {
            return;
        }

        throw new ElementTextException($message, $this->session->getDriver(), $element);
    }

    /**
     * @param string       $selectorType
     * @param string|array $selector
     * @param bool         $plural
     *
     * @return string
     */
    private function getMatchingElementRepresentation($selectorType, $selector, $plural = false)
    {
        $pluralization = $plural ? 's' : '';

        if (in_array($selectorType, array('named', 'named_exact', 'named_partial'))
            && is_array($selector) && 2 === count($selector)
        ) {
            return sprintf('%s%s matching locator "%s"', $selector[0], $pluralization, $selector[1]);
        }

        if (is_array($selector)) {
            $selector = implode(' ', $selector);
        }

        return sprintf('element%s matching %s "%s"', $pluralization, $selectorType, $selector);
    }
}
