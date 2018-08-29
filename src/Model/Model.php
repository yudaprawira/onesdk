<?php

namespace One\Model;

use One\Collection;
use Psr\Http\Message\UriInterface;

/**
 * Model base class
 * @method \One\Collection getCollection()
 * @method self withCollection(Collection $collection)
 * @method mixed|null get(string $key)
 * @method self set(string $key, mixed $value)
 * @method self add(string $key, mixed $value)
 * @method self map(\Closure $callback, array $context)
 * @method self filter filter(\Closure $callback)
 */
class Model
{
    const LEAD_SENTENCES_LIMIT = 5;

    /**
     * data variable to that work as One\Collection
     *
     * @var \One\Collection
     */
    protected $collection = null;

    /**
     * get Data Collection
     *
     * @return \One\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * immutable, return CLONE of original object with changed collection
     *
     * @param \One\Collection $collection
     * @return self
     */
    public function withCollection(Collection $collection)
    {
        $clone = clone $this;
        $clone->collection = $collection;

        return $clone;
    }

    /**
     * Clean non parseable char from string
     *
     * @param $string
     * @return string
     */
    protected function filterStringInstance($string)
    {
        if (empty($string)) {
            return '';
        }

        return $this->convertNonAscii($string);
    }

    /**
     * Make Sure Uri is a Psr\Http\Message\UriInterface instance
     *
     * @param \Psr\Http\Message\UriInterface|string|null $uri
     * @return string
     */
    protected function filterUriInstance($uri)
    {
        if ($uri instanceof UriInterface) {
            return (string) $uri;
        }

        if (is_string($uri)) {
            return (string) \One\createUriFromString($uri);
        }

        return (string) \One\createuriFromServer();
    }

    /**
     * Make Sure Date in string with correct format state
     *
     * @param \DateTimeInterface|string|int|null $date
     * @return string
     */
    protected function filterDateInstance($date)
    {
        if (empty($date)) {
            $date = new \DateTime("now", new \DateTimeZone("Asia/Jakarta"));
        }

        if (is_string($date) || is_int($date)) {
            $date = new \DateTime($date, new \DateTimeZone("Asia/Jakarta"));
        }

        return $this->formatDate($date);
    }

    /**
     * format date into required format
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function formatDate($date)
    {
        return $date->format("Y-m-d H:i:s");
    }

    /**
     * create lead/synopsis from body content if not available
     *
     * @param string $body
     * @return string
     */
    protected function createLeadFromBody($body)
    {
        $body = strip_tags($body);
        $sentences = array_filter(
            explode(".", $body),
            function ($item) {
                return trim($item);
            }
        );

        return implode(
            '. ',
            array_slice(
                $sentences,
                0,
                self::LEAD_SENTENCES_LIMIT
            )
        );
    }

    /**
     * proxy method to chain it to Collection class
     *
     * @param string $name
     * @param array $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->getCollection(), $name)) {
            return call_user_func_array(array($this->getCollection(), $name), $arguments);
        }

        throw new \Exception("method $name not exist");
    }

    /**
     * Clean non ASCII char from string
     *
     * @param $string
     * @return string
     */
    private function convertNonAscii($string)
    {
        $search = $replace = array();

        // Replace Single Curly Quotes
        $search[]  = chr(226) . chr(128) . chr(152);
        $replace[] = "'";
        $search[]  = chr(226) . chr(128) . chr(153);
        $replace[] = "'";

        // Replace Smart Double Curly Quotes
        $search[]  = chr(226) . chr(128) . chr(156);
        $replace[] = '"';
        $search[]  = chr(226) . chr(128) . chr(157);
        $replace[] = '"';

        // Replace En Dash
        $search[]  = chr(226) . chr(128) . chr(147);
        $replace[] = '--';

        // Replace Em Dash
        $search[]  = chr(226) . chr(128) . chr(148);
        $replace[] = '---';

        // Replace Bullet
        $search[]  = chr(226) . chr(128) . chr(162);
        $replace[] = '*';

        // Replace Middle Dot
        $search[]  = chr(194) . chr(183);
        $replace[] = '*';

        // Replace Ellipsis with three consecutive dots
        $search[]  = chr(226) . chr(128) . chr(166);
        $replace[] = '...';

        // Apply Replacements
        $string = str_replace($search, $replace, $string);

        // Remove any non-ASCII Characters
        $string = preg_replace("/[^\x01-\x7F]/", "", $string);

        return $string;
    }
}
