<?php

namespace Sqon\Event\Subscriber;

use Sqon\Event\BeforeSetPathEvent;
use Sqon\Path\Memory;
use Sqon\Path\PathInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replaces the contents of some or all files.
 *
 * This subscriber will replace patterns matched in the contents of paths
 * before they are set in the Sqon. Patterns can be replaced globally, for
 * specific paths, or for any path that matches another pattern. Multiple
 * patterns can be replaced for each matching condition.
 *
 * ```php
 * $subscriber = new ReplaceSubscriber();
 *
 * // Adds a replacement for all paths.
 * $subscriber->replaceAll('/pattern/', 'replace');
 *
 * // Adds a replacement for a specific path.
 * $subscriber->replaceByPath('path/to/script.php', '/pattern/', 'replace');
 *
 * // Adds a replacement for any path matching a pattern.
 * $subscriber->replaceByPattern('/\.php$/', '/pattern/', 'replace');
 *
 * $dispatcher->addSubscriber($subscriber);
 * ```
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class ReplaceSubscriber implements EventSubscriberInterface
{
    /**
     * The replacements.
     *
     * @var array
     */
    private $replacements = [
        'global' => [],
        'path' => [],
        'regex' => []
    ];

    /**
     * Replaces the contents of the file, if applicable.
     *
     * @param BeforeSetPathEvent $event The event manager.
     */
    public function beforeSetPath(BeforeSetPathEvent $event)
    {
        $manager = $event->getManager();

        if (PathInterface::DIRECTORY === $manager->getType()) {
            return;
        }

        $path = $event->getPath();

        if ($this->isMatch($path)) {
            $event->setManager(
                new Memory(
                    $this->processContents($path, $manager->getContents()),
                    $manager->getType(),
                    $manager->getModified(),
                    $manager->getPermissions()
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeSetPathEvent::NAME => [
                ['beforeSetPath', 100]
            ]
        ];
    }

    /**
     * Sets a replacement pattern that will affect all paths.
     *
     * ```php
     * $subscriber->replaceAll('/pattern/', 'replacement');
     * ```
     *
     * @param string $pattern     The regular expression pattern.
     * @param string $replacement The value to replace with.
     *
     * @return ReplaceSubscriber A fluent interface to this subscriber.
     */
    public function replaceAll($pattern, $replacement)
    {
        $this->replacements['global'][$pattern] = $replacement;

        return $this;
    }

    /**
     * Sets a replacement pattern for a specific path.
     *
     * ```php
     * $subscriber->replaceByPath(
     *     'path/to/script.php',
     *     '/pattern/',
     *     'replacement'
     * );
     * ```
     *
     * @param string $path        The exact path to match.
     * @param string $pattern     The regular expression pattern.
     * @param string $replacement The value to replace with.
     *
     * @return ReplaceSubscriber A fluent interface to this subscriber.
     */
    public function replaceByPath($path, $pattern, $replacement)
    {
        if (!isset($this->replacements['path'][$path])) {
            $this->replacements['path'][$path] = [];
        }

        $this->replacements['path'][$path][$pattern] = $replacement;

        return $this;
    }

    /**
     * Sets a replacement pattern for all paths matching a regular expression.
     *
     * ```php
     * $subscriber->replaceByPattern('/path/', '/pattern/', 'replacements');
     * ```
     *
     * @param string $path        The pattern for the path to match.
     * @param string $pattern     The regular expression pattern.
     * @param string $replacement The value to replace with.
     *
     * @return ReplaceSubscriber A fluent interface to this subscriber.
     */
    public function replaceByPattern($path, $pattern, $replacement)
    {
        if (!isset($this->replacements['regex'][$path])) {
            $this->replacements['regex'][$path] = [];
        }

        $this->replacements['regex'][$path][$pattern] = $replacement;

        return $this;
    }

    /**
     * Checks if the path matches any of the available replacements.
     *
     * @param string $path The path to match.
     *
     * @return boolean Returns `true` if it matches, `false` if not.
     */
    private function isMatch($path)
    {
        if (!empty($this->replacements['global'])) {
            return true;
        }

        if (!empty($this->replacements['path'][$path])) {
            return true;
        }

        if (!empty($this->replacements['regex'])) {
            foreach ($this->replacements['regex'] as $regex => $replacements) {
                if (0 < preg_match($regex, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Finds applicable replacements and applies them to the contents.
     *
     * @param string $path     The path to the file.
     * @param string $contents The contents to replace.
     *
     * @return string The replaced contents.
     */
    private function processContents($path, $contents)
    {
        if (!empty($this->replacements['global'])) {
            $contents = $this->replace(
                $contents,
                $this->replacements['global']
            );
        }

        if (!empty($this->replacements['path'][$path])) {
            $contents = $this->replace(
                $contents,
                $this->replacements['path'][$path]
            );
        }

        if (!empty($this->replacements['regex'])) {
            foreach ($this->replacements['regex'] as $match => $replacements) {
                if (0 < preg_match($match, $path)) {
                    $contents = $this->replace($contents, $replacements);
                }
            }
        }

        return $contents;
    }

    /**
     * Replaces matching patterns in the contents.
     *
     * @param string $contents     The contents to modify.
     * @param array  $replacements The replacements to apply.
     *
     * @return string The modified contents.
     */
    private function replace($contents, array $replacements)
    {
        foreach ($replacements as $search => $replace) {
            $contents = preg_replace($search, $replace, $contents);
        }

        return $contents;
    }
}
