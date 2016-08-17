<?php

namespace Sqon\Event\Subscriber;

use Sqon\Event\BeforeSetPathEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filters one or more paths from being set in the Sqon.
 *
 * This subscriber will block one or more paths from being set in a Sqon by
 * using exclusion and inclusion rules. If an exclusion rule is provided, any
 * path matching the rule will be excluded regardless of any inclusion rules
 * defined. If an inclusion rule is provided, all paths are excluded by default
 * unless the path matches any one of the inclusion rules.
 *
 * Both exclusion and inclusion rules relying on matching rules.
 *
 * - **By Name** A name is the last part of a path. If `example` is given to
 *   match, the path `to/example` will match but `to/example/script.php` will
 *   not match.
 * - **By Path** A path is the beginning of any path. If `example/to` is given,
 *   any path that begins with `example/to` will match but `diff/example/to`
 *   will not match.
 * - **By Pattern** A pattern is a regular expression. Any path that is matched
 *   by the regular expression will match. The expression must provide its own
 *   delimiter.
 *
 * ```php
 * $subscriber = new FilterSubscriber();
 *
 * // Exclude a path by name.
 * $subscriber->excludeByName('broken.php');
 *
 * // Exclude an exact path.
 * $subscriber->excludeByPath('example/script.php');
 *
 * // Exclude paths matching a pattern.
 * $subscriber->excludeByPattern('/[Tt]ests/');
 *
 * // Only include paths with a specific name.
 * $subscriber->includeByName('LICENSE');
 *
 * // Only include an exact path.
 * $subscriber->includeByPath('bin/example');
 *
 * // Only include paths matching a pattern.
 * $subscriber->includeByPattern('/\.php$/');
 *
 * $dispatcher->addSubscriber($subscriber);
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
    private $rules = [
        'exclude' => [
            'name' => [],
            'path' => [],
            'regex' => []
        ],
        'include' => [
            'name' => [],
            'path' => [],
            'regex' => []
        ]
    ];

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
     * Adds a name to exclude.
     *
     * ```php
     * $subscriber->excludeByName('example.php');
     * ```
     *
     * @param string $name The name to exclude.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function excludeByName($name)
    {
        $this->rules['exclude']['name'][] = $name;

        return $this;
    }

    /**
     * Adds an exact path to exclude.
     *
     * ```php
     * $subscriber->excludeByPath('/path/to/example.php');
     * ```
     *
     * @param string $path The exact path to exclude.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function excludeByPath($path)
    {
        $this->rules['exclude']['path'][] = $path;

        return $this;
    }

    /**
     * Adds a regular expression to match for exclusion.
     *
     * ```php
     * $subscriber->excludeByPattern('/example/');
     * ```
     *
     * @param string $pattern The pattern to match for exclusion.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function excludeByPattern($pattern)
    {
        $this->rules['exclude']['regex'][] = $pattern;

        return $this;
    }

    /**
     * Adds a name to include.
     *
     * ```php
     * $subscriber->includeByName('example.php');
     * ```
     *
     * @param string $name The name to include.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function includeByName($name)
    {
        $this->rules['include']['name'][] = $name;

        return $this;
    }

    /**
     * Adds an exact path to include.
     *
     * ```php
     * $subscriber->includeByPath('/path/to/example.php');
     * ```
     *
     * @param string $path The exact path to include.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function includeByPath($path)
    {
        $this->rules['include']['path'][] = $path;

        return $this;
    }

    /**
     * Adds a regular expression to match for exclusion.
     *
     * ```php
     * $subscriber->includeByPattern('/example/');
     * ```
     *
     * @param string $pattern The pattern to match for exclusion.
     *
     * @return FilterSubscriber A fluent interface to this subscriber.
     */
    public function includeByPattern($pattern)
    {
        $this->rules['include']['regex'][] = $pattern;

        return $this;
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
        if (empty($this->rules['exclude']['name'])
            && empty($this->rules['exclude']['path'])
            && empty($this->rules['exclude']['regex'])) {
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
        if (empty($this->rules['include']['name'])
            && empty($this->rules['include']['path'])
            && empty($this->rules['include']['regex'])) {
            return true;
        }

        return $this->isMatch($path, $this->rules['include']);
    }

    /**
     * Checks if a path matches any of the matching rules.
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

                case 'path':
                    foreach ($matches as $exact) {
                        if (0 === strpos($path, $exact)) {
                            return true;
                        }
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
