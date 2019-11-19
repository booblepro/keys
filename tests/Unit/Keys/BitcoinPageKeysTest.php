<?php

namespace Tests\Unit\Keys;

use App\Keys\BitcoinPageKeys;
use App\Keys\PageNumbers\BitcoinPageNumber;
use Tests\TestCase;

class BitcoinPageKeysTest extends TestCase
{
    /** @test */
    function it_can_generate_keys_for_the_first_page()
    {
        $bitcoinPageKeys = new BitcoinPageKeys('1');

        $this->assertMatchesSnapshot(
            $bitcoinPageKeys->getKeys()
        );
    }

    /** @test */
    function it_can_generate_keys_for_the_last_page()
    {
        $bitcoinPageKeys = new BitcoinPageKeys(
            BitcoinPageNumber::lastPageNumber()
        );

        $this->assertMatchesSnapshot(
            $bitcoinPageKeys->getKeys()
        );
    }

    /** @test */
    function it_can_generate_keys_for_a_random_page()
    {
        $keys = BitcoinPageKeys::generate('374280100293470930');

        $this->assertMatchesSnapshot($keys);
    }
}
