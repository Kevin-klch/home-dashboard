<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aufgaben des Haushalts.
     *
     * Nur ein Datum, keine Uhrzeit: "Müll rausbringen" ist eine Tagesaufgabe.
     * Was zu einer festen Zeit stattfindet, gehört in den Kalender.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['completed_at', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
