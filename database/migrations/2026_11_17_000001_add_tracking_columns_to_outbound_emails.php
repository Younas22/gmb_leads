<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: what was actually sent.
 *  - link_tracking: whether eligible links were replaced with tracking redirects
 *  - sent_body: the final rendered body handed to Gmail (fixed once queued);
 *    `body` keeps the untracked version, and the AI draft itself is never changed
 *  - mime_sha256: fingerprint of the exact MIME payload accepted by Gmail
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_emails', 'link_tracking')) {
                $table->boolean('link_tracking')->default(false)->after('body');
            }
            if (!Schema::hasColumn('outbound_emails', 'sent_body')) {
                $table->longText('sent_body')->nullable()->after('link_tracking');
            }
            if (!Schema::hasColumn('outbound_emails', 'mime_sha256')) {
                $table->string('mime_sha256', 64)->nullable()->after('rfc_message_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table) {
            $table->dropColumn(['link_tracking', 'sent_body', 'mime_sha256']);
        });
    }
};
