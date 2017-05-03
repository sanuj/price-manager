<?php

namespace App\Services;

use Illuminate\Cache\Repository as Cache;

class ThrottleService
{
    /**
     * @var Cache
     */
    protected $cache;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var int
     */
    protected $minutes;

    /**
     * @var int
     */
    protected $count;

    public function __construct(string $name, int $limit, int $minutes)
    {
        $this->name = $name.'__count__';
        $this->limit = $limit;
        $this->minutes = $minutes;
        $this->cache = resolve(Cache::class);
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function hit()
    {
        if ($this->count()) {
            $this->cache->increment($this->name);
            $this->count++;
        } else {
            $this->cache->put($this->name, 1, $this->minutes);
            $this->count = 1;
        }
    }

    public function check(): bool
    {
        return $this->count() < $this->limit;
    }

    public function attempt(): bool
    {
        $response = $this->check();

        $this->hit();

        return $response;
    }

    public function count(): int
    {
        if ($this->count !== null) {
            return $this->count;
        }

        $this->count = (int)$this->cache->get($this->name, 0);

        if (!$this->count) {
            $this->count = 0;
        }

        return $this->count;
    }
}
