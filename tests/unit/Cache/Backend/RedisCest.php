<?php

namespace Phalcon\Test\Unit\Cache\Backend;

use UnitTester;
use Phalcon\Cache\Exception;
use Phalcon\Cache\Frontend\Data;
use Phalcon\Cache\Backend\Redis;

/**
 * \Phalcon\Test\Unit\Cache\Backend\RedisCest
 * Tests the \Phalcon\Cache\Backend\Redis component
 *
 * @copyright (c) 2011-2016 Phalcon Team
 * @link      http://www.phalconphp.com
 * @author    Andres Gutierrez <andres@phalconphp.com>
 * @author    Serghei Iakovlev <serghei@phalconphp.com>
 * @package   Phalcon\Test\Unit\Cache\Backend
 *
 * The contents of this file are subject to the New BSD License that is
 * bundled with this package in the file docs/LICENSE.txt
 *
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world-wide-web, please send an email to license@phalconphp.com
 * so that we can send you a copy immediately.
 */
class RedisCest
{
    public function _before(UnitTester $I)
    {
        if (!extension_loaded('redis')) {
            throw new \PHPUnit_Framework_SkippedTestError(
                'Warning: redis extension is not loaded'
            );
        }
    }

    public function increment(UnitTester $I)
    {
        $I->wantTo('Increment counter by using Redis as cache backend');

        $key = '_PHCR' . 'increment';
        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT
        ]);

        $I->dontSeeInRedis($key);
        $I->haveInRedis('string', $key, 1);

        $I->assertEquals(2, $cache->increment('increment'));
        $I->seeInRedis($key, 2);

        $I->assertEquals(4, $cache->increment('increment', 2));
        $I->seeInRedis($key, 4);

        $I->assertEquals(14, $cache->increment('increment', 10));
        $I->seeInRedis($key, 14);
    }

    public function decrement(UnitTester $I)
    {
        $I->wantTo('Decrement counter by using Redis as cache backend');

        $key = '_PHCR' . 'decrement';
        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT
        ]);

        $I->dontSeeInRedis($key);
        $I->haveInRedis('string', $key, 100);

        $I->assertEquals(99, $cache->decrement('decrement'));
        $I->seeInRedis($key, 99);

        $I->assertEquals(97, $cache->decrement('decrement', 2));
        $I->seeInRedis($key, 97);

        $I->assertEquals(87, $cache->decrement('decrement', 10));
        $I->seeInRedis($key, 87);
    }

    public function get(UnitTester $I)
    {
        $I->wantTo('Get data by using Redis as cache backend');

        $key = '_PHCR' . 'data-get';
        $data = [uniqid(), gethostname(), microtime(), get_include_path(), time()];

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT
        ]);

        $I->haveInRedis('string', $key, serialize($data));
        $I->assertEquals($data, $cache->get('data-get'));

        $I->assertNull($cache->get($key));

        $data = 'sure, nothing interesting';

        $I->haveInRedis('string', $key, serialize($data));
        $I->assertEquals($data, $cache->get('data-get'));

        $I->assertNull($cache->get($key));
    }

    public function save(UnitTester $I)
    {
        $I->wantTo('Save data by using Redis as cache backend');

        $key = '_PHCR' . 'data-save';
        $data = [uniqid(), gethostname(), microtime(), get_include_path(), time()];

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT
        ]);

        $I->dontSeeInRedis($key);
        $cache->save('data-save', $data);

        $I->seeInRedis($key, serialize($data));

        $data = 'sure, nothing interesting';

        $I->dontSeeInRedis($key, serialize($data));

        $cache->save('data-save', $data);
        $I->seeInRedis($key, serialize($data));
    }


    public function delete(UnitTester $I)
    {
        $I->wantTo(/** @lang text */
            'Delete from cache by Redis as cache backend'
        );

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT
        ]);

        $I->assertFalse($cache->delete('non-existent-keys'));

        $I->haveInRedis('string', '_PHCR' . 'some-key-to-delete', 1);

        $I->assertTrue($cache->delete('some-key-to-delete'));
        $I->dontSeeInRedis('_PHCR' . 'some-key-to-delete');
    }

    public function flush(UnitTester $I)
    {
        $I->wantTo('Flush cache using by Redis as cache backend');

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host'     => TEST_RS_HOST,
            'port'     => TEST_RS_PORT,
            'statsKey' => '_PHCR'
        ]);

        $key1 = '_PHCR' . 'data-flush-1';
        $key2 = '_PHCR' . 'data-flush-2';

        $I->haveInRedis('string', $key1, 1);
        $I->haveInRedis('string', $key2, 2);

        $I->haveInRedis('set', '_PHCR', 'data-flush-1');
        $I->haveInRedis('set', '_PHCR', 'data-flush-2');

        $cache->save('data-flush-1', 1);
        $cache->save('data-flush-2', 3);

        $I->assertTrue($cache->flush());

        $I->dontSeeInRedis($key1);
        $I->dontSeeInRedis($key2);
    }

    public function queryKeys(UnitTester $I)
    {
        $I->wantTo('Get cache keys by Redis as cache backend');

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host'     => TEST_RS_HOST,
            'port'     => TEST_RS_PORT,
            'statsKey' => '_PHCR'
        ]);

        $I->haveInRedis('string', '_PHCR' . 'a', 1);
        $I->haveInRedis('string', '_PHCR' . 'b', 2);
        $I->haveInRedis('string', '_PHCR' . 'c', 3);

        $I->haveInRedis('set', '_PHCR', 'a');
        $I->haveInRedis('set', '_PHCR', 'b');
        $I->haveInRedis('set', '_PHCR', 'c');

        $keys = $cache->queryKeys();
        sort($keys);

        $I->assertEquals($keys, ['a', 'b', 'c']);
    }

    public function queryKeysWithoutStatsKey(UnitTester $I)
    {
        $I->wantTo('I want to get exception during the attempt getting cache keys by Redis as cache backend without statsKey');

        $cache = new Redis(new Data(['lifetime' => 20]), [
            'host' => TEST_RS_HOST,
            'port' => TEST_RS_PORT,
        ]);

        $I->expectException(
            new Exception("Cached keys need to be enabled to use this function (options['statsKey'] == '_PHCM')!"),
            function() use ($cache) {
                $cache->queryKeys();
            }
        );
    }
}
