<?php

namespace Sqon\Event\Subscriber;

use Sqon\Event\BeforeSetPathEvent;
use Sqon\Path\Memory;
use Sqon\Path\PathInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replaces the contents of some or all files.
 *
 * This subscriber will replace the contents of files. The subscriber will
 * first identify a file that needs to be modified by checking its name,
 * using a regular expression to match its path, or simply change all of them.
 * One or more regular expressions can be used to find a pattern to replace.
 *
 * ```php
 * $dispatcher->addSubscriber(
 *     new ReplaceSubscriber(
 *         [
 *             // Replace contents of specific files.
 *             'files' => [
 *                 'path/to/script.php' => [
 *                     '/search/' => 'replace'
 *                 ]
 *             ],
 *
 *             // Replace contents of all files.
 *             'global' => [
 *                 '/search/' => 'replace'
 *             ],
 *
 *             // Replace contents of matching files.
 *             'regex' => [
 *                 '/\.php$/' => [
 *                     '/search/' => 'replace'
 *                 ]
 *             ]
 *         ]
 *     )
 * );
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
    private $replacements;

    /**
     * Initializes the new replacement subscriber.
     *
     * @param array $replacements The replacements.
     */
    public function __construct(array $replacements)
    {
        $this->replacements = $replacements;
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
     * Checks if the path matches any of the available replacements.
     *
     * @param string $path The path to match.
     *
     * @return boolean Returns `true` if it matches, `false` if not.
     */
    private function isMatch($path)
    {
        if (isset($this->replacements['global'])) {
            return true;
        }

        if (isset($this->replacements['files'][$path])) {
            return true;
        }

        if (isset($this->replacements['regex'])) {
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
        if (isset($this->replacements['global'])) {
            $contents = $this->replace(
                $contents,
                $this->replacements['global']
            );
        }

        if (isset($this->replacements['files'][$path])) {
            $contents = $this->replace(
                $contents,
                $this->replacements['files'][$path]
            );
        }

        if (isset($this->replacements['regex'])) {
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
