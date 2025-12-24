<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\AuditActorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $users = User::all();
            $actorTypes = AuditActorType::all();
            
            if ($users->isEmpty() || $actorTypes->isEmpty()) {
                return;
            }
            
            $actions = [
                'vehicle.created',
                'vehicle.updated',
                'vehicle.deleted',
                'lead.created',
                'lead.updated',
                'user.created',
                'user.updated',
                'subscription.created',
                'subscription.updated',
            ];
            
            $targetTypes = ['Vehicle', 'Lead', 'User', 'DealerSubscription', 'Plan'];
            
            // Create audit logs
            for ($i = 0; $i < 25; $i++) {
                $actor = $users->random();
                $actorType = $actorTypes->random();
                $action = $faker->randomElement($actions);
                $targetType = $faker->randomElement($targetTypes);
                $targetId = $faker->numberBetween(1, 100);
                
                AuditLog::create([
                    'actor_id' => $actor->id,
                    'audit_actor_type_id' => $actorType->id,
                    'action' => $action,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'metadata' => [
                        'ip' => $faker->ipv4(),
                        'user_agent' => $faker->userAgent(),
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'ip_address' => $faker->ipv4(),
                    'created_at' => $faker->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        });
    }
}

