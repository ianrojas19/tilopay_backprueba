<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 5000 tickets distributed across 10 different users.
     */
    public function run(): void
    {
        // Create tickets in chunks to avoid memory issues
        $totalTickets = 5000;
        $chunkSize = 500;
        $userIds = range(1, 10);

        for ($i = 0; $i < $totalTickets; $i += $chunkSize) {
            $tickets = [];
            $count = min($chunkSize, $totalTickets - $i);
            
            for ($j = 0; $j < $count; $j++) {
                $tickets[] = [
                    'subject' => fake()->sentence(rand(3, 8)),
                    'body' => fake()->paragraphs(rand(1, 3), true),
                    'status' => fake()->randomElement(['open', 'closed']),
                    'user_id' => fake()->randomElement($userIds),
                    'created_at' => now()->subDays(rand(0, 365)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];
            }

            Ticket::insert($tickets);
        }
    }
}
