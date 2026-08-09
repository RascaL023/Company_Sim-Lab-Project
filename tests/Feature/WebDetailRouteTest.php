<?php

namespace Tests\Feature;

use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDetailRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_show_page_embeds_route_id_in_x_data(): void
    {
        $item = Item::factory()->create();

        $response = $this->get('/items/'.$item->id);

        $response->assertOk();
        $response->assertSee("itemDetailPage({ id: '{$item->id}' })", false);
    }

    public function test_borrowing_show_page_embeds_route_id_in_x_data(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $request = BorrowingRequest::factory()->create([
            'requested_by' => $peminjam->id,
            'status' => 'diajukan',
        ]);

        $response = $this->get('/borrowings/'.$request->id);

        $response->assertOk();
        $response->assertSee("borrowingDetailPage({ id: '{$request->id}' })", false);
    }
}
