<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RequestFriendTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    // public function testExample()
    // {
    //     $this->assertTrue(true);
    // }

    public function testRequestFriend()
    {
            $data = [
                'requestor' => "krisna@test.com",
                'to' => "sanji@test.com",
            ];

            $response = $this->json('POST', '/api/friendRequest', $data);
            // dd($response);
            $response->assertStatus(200);
            $response->assertJson(['status' => true]);
      }
}
