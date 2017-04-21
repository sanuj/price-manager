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
    protected $time;

    /**
     * @var int
     */
    protected $count;

    public function __construct(string $name, int $limit, int $time)
    {
        $this->name = $name.'__count__';
        $this->limit = $limit;
        $this->time = $time;
        $this->cache = resolve(Cache::class);
    }

    public function hit()
    {
        if ($this->count()) {
            $this->cache->increment($this->name);
            $this->count++;
        } else {
            $this->cache->put($this->name, 1, $this->time);
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
