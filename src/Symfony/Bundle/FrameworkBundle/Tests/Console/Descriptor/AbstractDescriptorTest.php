<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Descriptor\Descriptor;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class AbstractDescriptorTest extends TestCase
{
    /** @dataProvider getDescribeRouteCollectionTestData */
    public function testDescribeRouteCollection(RouteCollection $routes, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $routes);
    }

    public function getDescribeRouteCollectionTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getRouteCollections());
    }

    /** @dataProvider getDescribeRouteTestData */
    public function testDescribeRoute(Route $route, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $route);
    }

    public function getDescribeRouteTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getRoutes());
    }

    /** @dataProvider getDescribeContainerParametersTestData */
    public function testDescribeContainerParameters(ParameterBag $parameters, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $parameters);
    }

    public function getDescribeContainerParametersTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getContainerParameters());
    }

    /** @dataProvider getDescribeContainerBuilderTestData */
    public function testDescribeContainerBuilder(ContainerBuilder $builder, $expectedDescription, array $options)
    {
        $this->assertDescription($expectedDescription, $builder, $options);
    }

    public function getDescribeContainerBuilderTestData()
    {
        return $this->getContainerBuilderDescriptionTestData(ObjectsProvider::getContainerBuilders());
    }

    /**
     * @dataProvider getDescribeContainerExistingClassDefinitionTestData
     */
    public function testDescribeContainerExistingClassDefinition(Definition $definition, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $definition);
    }

    public function getDescribeContainerExistingClassDefinitionTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getContainerDefinitionsWithExistingClasses());
    }

    /** @dataProvider getDescribeContainerDefinitionTestData */
    public function testDescribeContainerDefinition(Definition $definition, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $definition);
    }

    public function getDescribeContainerDefinitionTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getContainerDefinitions());
    }

    /** @dataProvider getDescribeContainerDefinitionWithArgumentsShownTestData */
    public function testDescribeContainerDefinitionWithArgumentsShown(Definition $definition, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $definition, array('show_arguments' => true));
    }

    public function getDescribeContainerDefinitionWithArgumentsShownTestData()
    {
        $definitions = ObjectsProvider::getContainerDefinitions();
        $definitionsWithArgs = array();

        foreach ($definitions as $key => $definition) {
            $definitionsWithArgs[str_replace('definition_', 'definition_arguments_', $key)] = $definition;
        }

        return $this->getDescriptionTestData($definitionsWithArgs);
    }

    /** @dataProvider getDescribeContainerAliasTestData */
    public function testDescribeContainerAlias(Alias $alias, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $alias);
    }

    public function getDescribeContainerAliasTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getContainerAliases());
    }

    /** @dataProvider getDescribeContainerDefinitionWhichIsAnAliasTestData */
    public function testDescribeContainerDefinitionWhichIsAnAlias(Alias $alias, $expectedDescription, ContainerBuilder $builder, $options = array())
    {
        $this->assertDescription($expectedDescription, $builder, $options);
    }

    public function getDescribeContainerDefinitionWhichIsAnAliasTestData()
    {
        $builder = current(ObjectsProvider::getContainerBuilders());
        $builder->setDefinition('service_1', $builder->getDefinition('definition_1'));
        $builder->setDefinition('.service_2', $builder->getDefinition('.definition_2'));

        $aliases = ObjectsProvider::getContainerAliases();
        $aliasesWithDefinitions = array();
        foreach ($aliases as $name => $alias) {
            $aliasesWithDefinitions[str_replace('alias_', 'alias_with_definition_', $name)] = $alias;
        }

        $i = 0;
        $data = $this->getDescriptionTestData($aliasesWithDefinitions);
        foreach ($aliases as $name => $alias) {
            $file = array_pop($data[$i]);
            $data[$i][] = $builder;
            $data[$i][] = array('id' => $name);
            $data[$i][] = $file;
            ++$i;
        }

        return $data;
    }

    /** @dataProvider getDescribeContainerParameterTestData */
    public function testDescribeContainerParameter($parameter, $expectedDescription, array $options)
    {
        $this->assertDescription($expectedDescription, $parameter, $options);
    }

    public function getDescribeContainerParameterTestData()
    {
        $data = $this->getDescriptionTestData(ObjectsProvider::getContainerParameter());

        $file = array_pop($data[0]);
        $data[0][] = array('parameter' => 'database_name');
        $data[0][] = $file;
        $file = array_pop($data[1]);
        $data[1][] = array('parameter' => 'twig.form.resources');
        $data[1][] = $file;

        return $data;
    }

    /** @dataProvider getDescribeEventDispatcherTestData */
    public function testDescribeEventDispatcher(EventDispatcher $eventDispatcher, $expectedDescription, array $options)
    {
        $this->assertDescription($expectedDescription, $eventDispatcher, $options);
    }

    public function getDescribeEventDispatcherTestData()
    {
        return $this->getEventDispatcherDescriptionTestData(ObjectsProvider::getEventDispatchers());
    }

    /** @dataProvider getDescribeCallableTestData */
    public function testDescribeCallable($callable, $expectedDescription)
    {
        $this->assertDescription($expectedDescription, $callable);
    }

    public function testGetClassDescription()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\DescriptionTest'
        );

        $this->assertEquals('Interface DescriptionTest', $classDescription);
    }

    public function testGetClassDescriptionWithMultilineDescription()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\MultilineDescriptionTest'
        );

        $this->assertEquals('Interface DescriptionTest', $classDescription);
    }

    public function testGetClassDescriptionWithNoDescription()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\NoDescription'
        );

        $this->assertEmpty($classDescription);
    }

    public function testGetClassDescriptionEmptyDocblock()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\EmptyDocblockTest'
        );

        $this->assertEmpty($classDescription);
    }

    public function testGetClassDescriptionOnlyAts()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\OnlyAtsInDescriptionTest'
        );

        $this->assertEmpty($classDescription);
    }

    public function testGetClassDescriptionWithDescriptionAfterAt()
    {
        $classDescription = Descriptor::getClassDescription(
            'Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor\DescriptionAfterAtTest'
        );

        $this->assertEmpty($classDescription);
    }

    public function getDescribeCallableTestData()
    {
        return $this->getDescriptionTestData(ObjectsProvider::getCallables());
    }

    abstract protected function getDescriptor();

    abstract protected function getFormat();

    private function assertDescription($expectedDescription, $describedObject, array $options = array())
    {
        $options['raw_output'] = true;
        $options['raw_text'] = true;
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);

        if ('txt' === $this->getFormat()) {
            $options['output'] = new SymfonyStyle(new ArrayInput(array()), $output);
        }

        $this->getDescriptor()->describe($output, $describedObject, $options);

        if ('json' === $this->getFormat()) {
            $this->assertEquals(json_encode(json_decode($expectedDescription), JSON_PRETTY_PRINT), json_encode(json_decode($output->fetch()), JSON_PRETTY_PRINT));
        } else {
            $this->assertEquals(trim($expectedDescription), trim(str_replace(PHP_EOL, "\n", $output->fetch())));
        }
    }

    private function getDescriptionTestData(array $objects)
    {
        $data = array();
        foreach ($objects as $name => $object) {
            $file = sprintf('%s.%s', trim($name, '.'), $this->getFormat());
            $description = file_get_contents(__DIR__.'/../../Fixtures/Descriptor/'.$file);
            $data[] = array($object, $description, $file);
        }

        return $data;
    }

    private function getContainerBuilderDescriptionTestData(array $objects)
    {
        $variations = array(
            'services' => array('show_hidden' => true),
            'public' => array('show_hidden' => false),
            'tag1' => array('show_hidden' => true, 'tag' => 'tag1'),
            'tags' => array('group_by' => 'tags', 'show_hidden' => true),
            'arguments' => array('show_hidden' => false, 'show_arguments' => true),
        );

        $data = array();
        foreach ($objects as $name => $object) {
            foreach ($variations as $suffix => $options) {
                $file = sprintf('%s_%s.%s', trim($name, '.'), $suffix, $this->getFormat());
                $description = file_get_contents(__DIR__.'/../../Fixtures/Descriptor/'.$file);
                $data[] = array($object, $description, $options, $file);
            }
        }

        return $data;
    }

    private function getEventDispatcherDescriptionTestData(array $objects)
    {
        $variations = array(
            'events' => array(),
            'event1' => array('event' => 'event1'),
        );

        $data = array();
        foreach ($objects as $name => $object) {
            foreach ($variations as $suffix => $options) {
                $file = sprintf('%s_%s.%s', trim($name, '.'), $suffix, $this->getFormat());
                $description = file_get_contents(__DIR__.'/../../Fixtures/Descriptor/'.$file);
                $data[] = array($object, $description, $options, $file);
            }
        }

        return $data;
    }
}

/**
 * Interface DescriptionTest
 *
 * @package Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor
 */
interface DescriptionTest {}

/**
 * Interface DescriptionTest
 *
 * This is more information
 *
 * @package Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor
 */
interface MultilineDescriptionTest {}

interface NoDescriptionTest {}

/**
 */
interface EmptyDocblockTest {}

/**
 * @package Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor
 */
interface OnlyAtsInDescriptionTest {}

/**
 * @package Symfony\Bundle\FrameworkBundle\Tests\Console\Descriptor
 *
 * Interface DescriptionAfterAt
 */
interface DescriptionAfterAtTest {}
