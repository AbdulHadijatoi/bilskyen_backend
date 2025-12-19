<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->enum('type', ['individual', 'business']);
            $table->string('phone', 15)->nullable()->comment('8-15 chars, numbers and + only');
            $table->string('email', 255)->nullable()->comment('Lowercase, trimmed');
            $table->string('source', 50)->comment('Enum: CONTACT_SOURCES');
            $table->string('name', 100)->nullable()->comment('For individual contacts, 2-100 chars');
            $table->text('address')->nullable();
            $table->string('company_name', 100)->nullable()->comment('For business contacts, 2-100 chars');
            $table->string('contact_person', 100)->nullable()->comment('For business contacts, 2-100 chars');
            $table->json('images')->comment('Array of file URLs, max 20 items');
            $table->string('remarks', 500)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_contacts_created_at');
            $table->index('name', 'idx_contacts_name');
            $table->index('email', 'idx_contacts_email');
            $table->index('phone', 'idx_contacts_phone');
            $table->index('type', 'idx_contacts_type');
            $table->index('source', 'idx_contacts_source');
            $table->index('company_name', 'idx_contacts_company_name');
            $table->fullText(['name', 'company_name', 'email'], 'idx_contacts_search');
        });

        // Add CHECK constraints using raw SQL (not supported in SQLite)
        if (DB::getDriverName() !== 'sqlite') {
            $lengthFunc = DB::getDriverName() === 'mysql' ? 'CHAR_LENGTH' : 'LENGTH';
            DB::statement("ALTER TABLE contacts ADD CONSTRAINT chk_contacts_name_length CHECK (name IS NULL OR ({$lengthFunc}(name) >= 2 AND {$lengthFunc}(name) <= 100))");
            DB::statement("ALTER TABLE contacts ADD CONSTRAINT chk_contacts_company_name_length CHECK (company_name IS NULL OR ({$lengthFunc}(company_name) >= 2 AND {$lengthFunc}(company_name) <= 100))");
            DB::statement("ALTER TABLE contacts ADD CONSTRAINT chk_contacts_contact_person_length CHECK (contact_person IS NULL OR ({$lengthFunc}(contact_person) >= 2 AND {$lengthFunc}(contact_person) <= 100))");
            
            // Add REGEXP constraint using raw SQL (MySQL 8.0.4+)
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE contacts ADD CONSTRAINT chk_contacts_phone_format CHECK (phone IS NULL OR phone REGEXP \'^[+0-9]{8,15}$\')');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

