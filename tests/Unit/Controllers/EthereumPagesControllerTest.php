<?php

namespace Tests\Unit\Controllers;

use App\Events\RandomPageGenerated;
use App\Keys\PageNumbers\EthereumPageNumber;
use App\Models\BiggestRandomPage;
use App\Models\CoinStats;
use App\Models\SmallestRandomPage;
use App\Support\Enums\CoinType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EthereumPagesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_can_show_the_index()
    {
        $this->get(route('ethPages.index'))
            ->assertStatus(200);
    }

    /** @test */
    function it_can_show_stats()
    {
        $this->get(route('ethPages.stats'))
            ->assertStatus(200);
    }

    /** @test */
    function it_can_show_the_search_page()
    {
        $this->get(route('ethPages.search'))
            ->assertStatus(200);
    }

    /** @test */
    function it_can_show_the_first_page()
    {
        $this->getPage('1')
            ->assertStatus(200)
            ->assertDontSee('noindex'); // first page should be indexed by robots.
    }

    /** @test */
    function some_pages_allow_robots()
    {
        $this->getPage('2')->assertStatus(200)->assertDontSee('noindex');
        $this->getPage('3')->assertStatus(200)->assertDontSee('noindex');
        $this->getPage('4')->assertStatus(200)->assertSee('noindex');

        $this->getPage('904625697166532776746648320380374280100293470930272690489102837043110636672')->assertStatus(200)->assertSee('noindex');
        $this->getPage('904625697166532776746648320380374280100293470930272690489102837043110636673')->assertStatus(200)->assertDontSee('noindex');
        $this->getPage('904625697166532776746648320380374280100293470930272690489102837043110636674')->assertStatus(200)->assertDontSee('noindex');
        $this->getPage('904625697166532776746648320380374280100293470930272690489102837043110636675')->assertStatus(200)->assertDontSee('noindex');
    }

    /** @test */
    function it_can_search_for_a_bitcoin_wif()
    {
        $statsToday = CoinStats::today(CoinType::ETHEREUM);

        $this->assertSame(0, $statsToday->times_searched);

        $this->postSearch([
                'private_key' => '52b72b4bfd1c3c531872abe9fc97adb56859f044e383d2efd89c378811e8a087',
            ])
            ->assertSessionHasNoErrors()
            ->assertStatus(302)
            ->assertRedirect(route('ethPages', '292291292347084573995273021603341148514969637192285667544689430121001767234'));

        $this->assertSame(1, $statsToday->refresh()->times_searched);
    }

    /** @test */
    function it_wont_search_for_invalid_wifs()
    {
        $statsToday = CoinStats::today(CoinType::ETHEREUM);

        $this->assertSame(0, $statsToday->times_searched);

        $this->postSearch(['private_key' => 'Hacked!!'])->assertSessionHasErrors('private_key');

        // shouldn't increment when the search fails
        $this->assertSame(0, $statsToday->refresh()->times_searched);
    }

    /** @test */
    function it_can_show_the_last_page()
    {
        $this->getPage(EthereumPageNumber::lastPageNumber())
            ->assertStatus(200)
            ->assertDontSee('noindex'); // first page should be indexed by robots.
    }

    /** @test */
    function it_can_show_a_random_page()
    {
        $this->expectsEvents(RandomPageGenerated::class);

        $this->assertSame(0, CoinStats::today(CoinType::ETHEREUM)->random_pages_generated);

        $this->followingRedirects()
            ->getRandomPage()
            ->assertStatus(200)
            ->assertViewIs('eth-page')
            ->assertSee('<meta name="robots" content="noindex, nofollow">');

        $this->assertSame(1, CoinStats::today(CoinType::ETHEREUM)->random_pages_generated);
    }

    /** @test */
    function you_get_redirected_when_exceeding_the_max_page_number()
    {
        $this->followingRedirects()
            ->getPage(EthereumPageNumber::lastPageNumber().'1234')
            ->assertStatus(200)
            ->assertViewIs('eth-page-too-big');
    }

    /** @test */
    function it_keeps_track_of_page_views_stats()
    {
        $this->assertSame(0, CoinStats::today(CoinType::ETHEREUM)->pages_viewed);
        $this->assertSame(0, CoinStats::today(CoinType::ETHEREUM)->keys_generated);

        $this->getPage('123456')->assertStatus(200);

        $this->assertSame(1, CoinStats::today(CoinType::ETHEREUM)->pages_viewed);
        $this->assertSame(128, CoinStats::today(CoinType::ETHEREUM)->keys_generated);

        $this->getPage(EthereumPageNumber::lastPageNumber())->assertStatus(200);

        $this->assertSame(2, CoinStats::today(CoinType::ETHEREUM)->pages_viewed);
        $this->assertSame(224, CoinStats::today(CoinType::ETHEREUM)->keys_generated);
    }

    /** @test */
    function biggest_and_smallest_random_bitcoin_page_get_stored()
    {
        $redirectUrl = $this->getRandomPage()
            ->assertStatus(302)
            ->headers
            ->get('location');

        $randomNumber = last(explode('/', $redirectUrl));

        $this->assertTrue(strlen($randomNumber) > 10);

        $this->assertSame(1, SmallestRandomPage::count());
        $this->assertSame($randomNumber, SmallestRandomPage::smallest(CoinType::ETHEREUM));

        $this->assertSame(1, BiggestRandomPage::count());
        $this->assertSame($randomNumber, BiggestRandomPage::biggest(CoinType::ETHEREUM));
    }

    /** @test */
    function it_stores_the_new_smallest_number()
    {
        SmallestRandomPage::create([
            'coin' => CoinType::ETHEREUM,
            'page_number' => '519480938980827735392876',
        ]);

        RandomPageGenerated::dispatch(
            new EthereumPageNumber('519480938980827735392877')
        );

        $this->assertSame(1, SmallestRandomPage::count());

        RandomPageGenerated::dispatch(
            new EthereumPageNumber('99948093898')
        );

        $this->assertSame(2, SmallestRandomPage::count());

        $this->assertSame('99948093898', SmallestRandomPage::smallest(CoinType::ETHEREUM));
    }

    /** @test */
    function it_stores_the_new_biggest_number()
    {
        SmallestRandomPage::create([
            'coin' => CoinType::ETHEREUM,
            'page_number' => '519480938980827735392876',
        ]);

        RandomPageGenerated::dispatch(
            new EthereumPageNumber('519480938980827735392875')
        );

        $this->assertSame(1, BiggestRandomPage::count());

        RandomPageGenerated::dispatch(
            new EthereumPageNumber('519480938980827735392877')
        );

        $this->assertSame(2, BiggestRandomPage::count());

        $this->assertSame('519480938980827735392877', BiggestRandomPage::biggest(CoinType::ETHEREUM));
    }

    private function getPage($number)
    {
        return $this->get(route('ethPages', $number));
    }

    private function getRandomPage()
    {
        return $this->get(route('ethPages.random'));
    }

    private function postSearch($data)
    {
        return $this->post(route('ethPages.search'), $data);
    }
}
