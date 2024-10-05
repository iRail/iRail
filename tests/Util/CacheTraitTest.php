<?php

namespace Tests\Util;

use Irail\Traits\Cache;
use Irail\Util\Tidy;
use Tests\TestCase;

class CacheTraitTest extends TestCase
{

    use Cache;
    public function testClearByPrefix_normalCase_shouldClear()
    {
        $this->setCachePrefix('test');
        $this->setCachedObject('key', 'value');
        $this->assertTrue($this->isCached('key'));
        $this->deleteCachedObjectsByPrefix('test');
        $this->assertFalse($this->isCached('key'));

        // Even an empty case should be able to be cleared
        $this->deleteCachedObjectsByPrefix('test');
    }

}
