<?php

namespace PhpCache;

/**
 * Does not cache anything. Useful as a safe default when no caching mechanism is configured.
 */
class Dummy extends AbstractCache {

    /**
     * @param CacheKey $key
     * @return boolean
     */
    public function check(CacheKey $key) {
        return false;
    }

    /**
     * @param CacheKey $key
     * @return mixed
     */
    public function get(CacheKey $key) {
        return false;
    }

    /**
     * @param CacheKey $key
     * @param mixed $value
     * @param int $sessionLength
     */
    public function set(CacheKey $key, $value, $sessionLength = null) {
    }

    /**
     * @param CacheKey $key
     */
    public function clear(CacheKey $key) {
    }

    public function clearAll() {
    }

}
