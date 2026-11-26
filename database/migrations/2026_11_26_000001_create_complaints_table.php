<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->string('reporter_name')->nullable();
            $table->string('kecamatan')->index();
            $table->string('kelurahan')->nullable();
            $table->text('raw_text');
            $table->text('clean_text');
            $table->string('predicted_category')->index();
            $table->string('actual_category')->nullable();
            $table->string('predicted_urgency')->index();
            $table->string('actual_urgency')->nullable();
            $table->string('assigned_opd');
            $table->string('assigned_unit');
            $table->string('sla_target');
            $table->float('confidence_svm')->default(0);
            $table->float('latency_ms')->default(0);
            $table->string('model_engine')->default('Linear SVM + TF-IDF (PHP Native)');
            $table->json('matched_tokens')->nullable();
            $table->json('class_scores')->nullable();
            $table->string('status')->default('Terdisposisi ke OPD')->index();
            $table->string('source')->default('portal_warga')->index();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
