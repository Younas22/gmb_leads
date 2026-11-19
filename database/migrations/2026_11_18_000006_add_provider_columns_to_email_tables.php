<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, non-destructive extensions so Gmail and Resend share history,
 * events, replies and follow-ups. Existing rows keep their meaning:
 * outbound_emails.provider defaults to "gmail" (every existing row was sent through Gmail),
 * and Gmail rows never get delivery data (Gmail provides none).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table) {
            if (!Schema::hasColumn('outbound_emails', 'provider')) {
                $table->string('provider', 10)->default('gmail')->after('owner_user_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'resend_integration_id')) {
                $table->unsignedBigInteger('resend_integration_id')->nullable()->after('connected_account_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'resend_sender_id')) {
                $table->unsignedBigInteger('resend_sender_id')->nullable()->after('resend_integration_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->after('draft_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'marketing_contact_id')) {
                $table->unsignedBigInteger('marketing_contact_id')->nullable()->after('campaign_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'provider_message_id')) {
                $table->string('provider_message_id', 64)->nullable()->after('gmail_thread_id');
            }
            if (!Schema::hasColumn('outbound_emails', 'delivery_status')) {
                // Resend only: sent | delivery_delayed | delivered | bounced | failed | suppressed
                $table->string('delivery_status', 20)->nullable()->after('provider_message_id');
            }
            foreach (['delivered_at', 'bounced_at', 'complained_at'] as $column) {
                if (!Schema::hasColumn('outbound_emails', $column)) {
                    $table->timestamp($column)->nullable();
                }
            }
        });

        Schema::table('outbound_emails', function (Blueprint $table) {
            if (!Schema::hasIndex('outbound_emails', 'outbound_emails_provider_msg_idx')) {
                $table->index(['provider', 'provider_message_id'], 'outbound_emails_provider_msg_idx');
            }
            if (!Schema::hasIndex('outbound_emails', 'outbound_emails_campaign_to_idx')) {
                $table->index(['campaign_id', 'to_email'], 'outbound_emails_campaign_to_idx');
            }
        });

        Schema::table('email_tracking_events', function (Blueprint $table) {
            if (!Schema::hasColumn('email_tracking_events', 'provider')) {
                $table->string('provider', 10)->nullable()->after('event_source');
            }
            if (!Schema::hasColumn('email_tracking_events', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->after('outbound_email_id');
            }
            if (!Schema::hasColumn('email_tracking_events', 'marketing_contact_id')) {
                $table->unsignedBigInteger('marketing_contact_id')->nullable()->after('saved_lead_id');
            }
        });

        // Follow-ups for opted-in contacts that are not saved leads
        Schema::table('email_follow_ups', function (Blueprint $table) {
            if (!Schema::hasColumn('email_follow_ups', 'marketing_contact_id')) {
                $table->unsignedBigInteger('marketing_contact_id')->nullable()->after('saved_lead_id')->index();
            }
            $table->unsignedBigInteger('saved_lead_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_follow_ups', function (Blueprint $table) {
            $table->dropColumn('marketing_contact_id');
        });
        Schema::table('email_tracking_events', function (Blueprint $table) {
            $table->dropColumn(['provider', 'campaign_id', 'marketing_contact_id']);
        });
        Schema::table('outbound_emails', function (Blueprint $table) {
            $table->dropIndex('outbound_emails_provider_msg_idx');
            $table->dropIndex('outbound_emails_campaign_to_idx');
            $table->dropColumn(['provider', 'resend_integration_id', 'resend_sender_id', 'campaign_id', 'marketing_contact_id',
                'provider_message_id', 'delivery_status', 'delivered_at', 'bounced_at', 'complained_at']);
        });
    }
};
