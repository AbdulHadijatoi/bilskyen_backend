<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ChatMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $threads = ChatThread::with('lead')->get();
            $dealerStaff = User::whereHas('dealers')->get();
            
            if ($threads->isEmpty()) {
                return;
            }
            
            $messages = [
                'Hello, I am interested in this vehicle. Can you provide more information?',
                'What is the condition of the vehicle?',
                'Is the vehicle still available?',
                'Can I schedule a test drive?',
                'What is your best price?',
                'Thank you for the information. I will get back to you.',
                'The vehicle looks great. When can I see it?',
                'Do you offer financing options?',
                'What is the vehicle history?',
                'Can you send me more photos?',
            ];
            
            foreach ($threads as $thread) {
                $lead = $thread->lead;
                $buyer = $lead->buyerUser;
                $assignedStaff = $lead->assignedUser ?? $dealerStaff->random();
                
                // Create 3-8 messages per thread
                $messageCount = $faker->numberBetween(3, 8);
                
                for ($i = 0; $i < $messageCount; $i++) {
                    // Alternate between buyer and staff
                    $sender = $i % 2 === 0 ? $buyer : $assignedStaff;
                    $isInternal = $i % 2 === 1 && $faker->boolean(20);
                    
                    ChatMessage::create([
                        'thread_id' => $thread->id,
                        'sender_id' => $sender->id,
                        'message' => $isInternal 
                            ? $faker->sentence()
                            : $faker->randomElement($messages),
                        'is_internal' => $isInternal,
                        'created_at' => $faker->dateTimeBetween($thread->created_at, 'now'),
                    ]);
                }
            }
        });
    }
}

