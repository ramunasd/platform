<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;

class DebugTranslatorTest extends \PHPUnit_Framework_TestCase
{
    protected $messages = array(
        'fr' => array(
            'jsmessages' => array(
                'foo' => 'foo (FR)',
            ),
            'messages' => array(
                'foo' => 'foo messages (FR)',
            ),
        ),
        'en' => array(
            'jsmessages' => array(
                'foo' => 'foo (EN)',
                'bar' => 'bar (EN)',
            ),
            'messages' => array(
                'foo' => 'foo messages (EN)',
            ),
            'validators' => array(
                'choice' => '{0} choice 0 (EN)|{1} choice 1 (EN)|]1,Inf] choice inf (EN)',
            ),
        ),
    );

    /**
     * @param string $locale
     * @param string $domain
     * @param string $source
     * @param string $expected
     * @dataProvider transDataProvider
     */
    public function testTrans($locale, $domain, $source, $expected)
    {
        $locales = array_keys($this->messages);
        $translator = $this->getTranslator($this->getLoader());
        $_locale = !is_null($locale) ? $locale : reset($locales);
        $translator->setLocale($_locale);
        $translator->setFallbackLocales(array_slice($locales, array_search($_locale, $locales) + 1));

        $this->assertEquals($expected, $translator->trans($source, [], $domain));
    }

    /**
     * @return array
     */
    public function transDataProvider()
    {
        return [
            'translated' => [
                'locale' => 'en',
                'domain' => 'messages',
                'source' => 'foo',
                'expected' => '[foo messages (EN)]',
            ],
            'not translated' => [
                'locale' => 'fr',
                'domain' => 'jsmessages',
                'source' => 'baz',
                'expected' => '!!!---baz---!!!',
            ]
        ];
    }

    /**
     * @param string $locale
     * @param string $domain
     * @param string $source
     * @param int number
     * @param string $expected
     * @dataProvider transChoiceDataProvider
     */
    public function testTransChoice($locale, $domain, $source, $number, $expected)
    {
        $locales = array_keys($this->messages);
        $translator = $this->getTranslator($this->getLoader());
        $_locale = !is_null($locale) ? $locale : reset($locales);
        $translator->setLocale($_locale);
        $translator->setFallbackLocales(array_slice($locales, array_search($_locale, $locales) + 1));

        $this->assertEquals($expected, $translator->transChoice($source, $number, [], $domain));
    }

    /**
     * @return array
     */
    public function transChoiceDataProvider()
    {
        return [
            'translated' => [
                'locale' => 'en',
                'domain' => 'validators',
                'source' => 'choice',
                'number' => 2,
                'expected' => '[choice inf (EN)]',
            ],
            'not translated' => [
                'locale' => 'fr',
                'domain' => 'validators',
                'source' => 'item',
                'number' => 1,
                'expected' => '!!!---item---!!!',
            ]
        ];
    }

    /**
     * Create a catalog and fills it in with messages
     *
     * @param string $locale
     * @param array $dictionary
     * @return MessageCatalogue
     */
    public function getCatalogue($locale, $dictionary)
    {
        $catalogue = new MessageCatalogue($locale);
        foreach ($dictionary as $domain => $messages) {
            foreach ($messages as $key => $translation) {
                $catalogue->set($key, $translation, $domain);
            }
        }
        return $catalogue;
    }

    /**
     * Creates a mock of Loader
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLoader()
    {
        $messages = $this->messages;
        $obj = $this;
        $loader = $this->getMock('Symfony\Component\Translation\Loader\LoaderInterface');
        $loader
            ->expects($this->any())
            ->method('load')
            ->will(
                $this->returnCallback(
                    function () use ($obj, $messages) {
                        $locale = func_get_arg(1);
                        return $obj->getCatalogue($locale, $messages[$locale]);
                    }
                )
            );
        return $loader;
    }

    /**
     * Creates a mock of Container
     *
     * @param LoaderInterface $loader
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContainer($loader)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue($loader));
        return $container;
    }

    /**
     * Creates instance of Translator
     *
     * @param $loader
     * @param array $options
     * @return DebugTranslator
     */
    public function getTranslator($loader, $options = array())
    {
        $translator = new DebugTranslator(
            $this->getContainer($loader),
            new MessageSelector(),
            array('loader' => array('loader')),
            $options
        );

        $translator->addResource('loader', 'foo', 'fr');
        $translator->addResource('loader', 'foo', 'en');

        return $translator;
    }
}
