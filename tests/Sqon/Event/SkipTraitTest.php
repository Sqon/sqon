<?php

namespace Test\Sqon\Event;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Event\SkipTrait;

/**
 * Verifies that the skip trait functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Event\SkipTrait
 */
class SkipTraitTest extends TestCase
{
    /**
     * The skip trait mock.
     *
     * @var MockObject|SkipTrait
     */
    private $skip;

    /**
     * Verifies that an event is skipped.
     */
    public function testSkipPostEventAction()
    {
        self::assertFalse(
            $this->skip->isSkipped(),
            'The post action event should not be skipped by default.'
        );

        $this
            ->skip
            ->expects($this->once())
            ->method('stopPropagation')
        ;

        $this->skip->skip();

        self::assertTrue(
            $this->skip->isSkipped(),
            'The post action event should be skipped.'
        );
    }

    /**
     * Creates a new mock of the skip trait.
     */
    protected function setUp()
    {
        $this->skip = $this
            ->getMockBuilder(SkipTrait::class)
            ->setMethods(['stopPropagation'])
            ->getMockForTrait()
        ;
    }
}
