<?php

namespace Sqon\Event\Subscriber;

use Sqon\Event\BeforeSetPathEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filters one or more paths from being set in the Sqon.
 *
 * The filter subscriber supports matching paths by the name (e.g. "example" in
 * "/path/to/example") and by regular expression (e.g. "/example/" matches the
 * path "/some/example/path"). These matches can be used to exclude or include
 * files in the Sqon.
 *
 * There are two important things to remember when defining rules:
 *
 * - An "exclude" will always exclude.
 * - An "include" acts as a whitelist.
 *
 * ```php
 * $dispatcher->addSubscriber(
 *     new FilterSubscriber(
 *         [
 *             // Exclude any path that matches the following rules.
 *             'exclude' => [
 *                 'name' => [
 *
 *                     // Exclude any path named "broken.php"
 *                     'broken.php'
 *
 *                 ],
 *                 'regex' => [
 *
 *                     // Exclude any path containing "Tests" or "tests".
 *                     '/([Tt]ests)/'
 *
 *                 ]
 *             ],
 *
 *             // Include only paths that match the following rules.
 *             'include' => [
 *                 'name' => [
 *
 *                     // Only include paths named "LICENSE".
 *                     'LICENSE'
 *
 *                 ],
 *                 'regex' => [
 *
 *                     // Also only include paths that end with ".php".
 *                     '/\.php$/'
 *
 *                 ]
 *             ]
 *         ]
 *     )
 * );
 * ```
 *
 * Using the above rules, the following paths are stored in the Sqon:
 *
 * - LICENSE
 * - src/My/Example/Class.php
 *
 * But the following paths were prevented from being stored:
 *
 * - src/emoji.png
 * - tests/My/Example/ClassTest.php
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class FilterSubscriber implements EventSubscriberInterface
{
    /**
     * The filtering rules.
     *
     * @var array
     */
    private $rules;

    /**
     * Initializes the new path filtering subscriber.
     *
     * ```php
     * $subscriber = new FilterSubscriber(
     *     [
     *         'exclude' => [
     *             'name' => [
     *                 'exclude.php'
     *             ],
     *             'regex' => [
     *                 '/exclude\.php/'
     *             ]
     *         ],
     *         'include' => [
     *             'name' => [
     *                 'include.php'
     *             ],
     *             'regex' => [
     *                 '/include\.php/'
     *             ]
     *         ],
     *     ]
     * );
     * ```
     *
     * @param array $rules The path filtering rules.
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeSetPathEvent::NAME => [
                ['beforeSetPath', 200]
            ]
        ];
    }

    /**
     * Checks if the path should be filtered out.
     *
     * @param BeforeSetPathEvent $event The event manager.
     */
    public function beforeSetPath(BeforeSetPathEvent $event)
    {
        if (!$this->isAllowed($event->getPath())) {
            $event->skip();
        }
    }

    /**
     * Checks if a path is allowed.
     *
     * @param string $path The path to the file.
     *
     * @return boolean Returns `true` if allowed, `false` if not.
     */
    private function isAllowed($path)
    {
        if ($this->isExcluded($path)) {
            return false;
        }

        return $this->isIncluded($path);
    }

    /**
     * Checks if a path is disallowed through the exclusion rules.
     *
     * @param string $path The path to check.
     *
     * @return boolean Returns `true` if excluded, `false` if not.
     */
    private function isExcluded($path)
    {
        if (empty($this->rules['exclude'])) {
            return false;
        }

        return $this->isMatch($path, $this->rules['exclude']);
    }

    /**
     * Checks if a path is allowed through the exclusion rules.
     *
     * @param string $path The path to check.
     *
     * @return boolean Returns `true` if included, `false` if not.
     */
    private function isIncluded($path)
    {
        if (empty($this->rules['include'])) {
            return true;
        }

        return $this->isMatch($path, $this->rules['include']);
    }

    /**
     * Checks if a path matches any of the rules.
     *
     * @param string $path  The path to match.
     * @param array  $rules The matching rules.
     *
     * @return boolean Returns `true` if a rule matched, `false` if not.
     */
    private function isMatch($path, array $rules)
    {
        foreach ($rules as $rule => $matches) {
            switch ($rule) {
                case 'name':
                    if (in_array(basename($path), $matches)) {
                        return true;
                    }

                    break;

                case 'regex':
                    foreach ($matches as $regex) {
                        if (0 < preg_match($regex, $path)) {
                            return true;
                        }
                    }

                    break;
            }
        }

        return false;
    }
}
