<?php

namespace Tests\Feature\Security;

use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadSecurityTest extends SecurityTestCase
{
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Never write test uploads into the real public/ folder
        $this->publicDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cnm-test-public-' . uniqid();
        File::ensureDirectoryExists($this->publicDir . '/assets/avatar');
        File::ensureDirectoryExists($this->publicDir . '/images/payments');
        $this->app->usePublicPath($this->publicDir);

        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);
        parent::tearDown();
    }

    private function avatarFiles(): array
    {
        return array_map('basename', File::files($this->publicDir . '/assets/avatar'));
    }

    // ── Avatars ──────────────────────────────────────────────────────────

    public function test_avatar_upload_rejects_php_files(): void
    {
        $user = $this->makeSubscribedUser();
        $shell = UploadedFile::fake()->createWithContent('shell.php', '<?php echo "pwned"; ?>');

        $this->actingAs($user)->postJson('/user/avatar/upload', ['avatar' => $shell])->assertStatus(422);

        $this->assertSame([], $this->avatarFiles());
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_avatar_upload_rejects_php_disguised_with_image_extension(): void
    {
        // The client calls it "avatar.png", but its content is detected as PHP. The storage
        // helper must use the detected type (guessExtension), never the client name.
        $disguised = \Mockery::mock(UploadedFile::fake()->create('avatar.png', 1))->makePartial();
        $disguised->shouldReceive('guessExtension')->andReturn('php');
        $disguised->shouldReceive("getClientOriginalName")->andReturn("avatar.png");
        $disguised->shouldReceive("getClientOriginalExtension")->andReturn("png");

        try {
            \App\Support\SecureUpload::storePublicImage($disguised, "assets/avatar");
            $this->fail("A file detected as PHP must not be stored");
        } catch (\RuntimeException $e) {
            $this->assertSame("This file type is not allowed.", $e->getMessage());
        }

        $this->assertSame([], $this->avatarFiles());
    }

    public function test_avatar_upload_rejects_double_extension_trick(): void
    {
        $user = $this->makeSubscribedUser();
        $file = UploadedFile::fake()->image('photo.png')->size(10);
        $tricky = new UploadedFile($file->getRealPath(), 'photo.php.png.php', 'image/png', null, true);

        $this->actingAs($user)->postJson('/user/avatar/upload', ['avatar' => $tricky])->assertStatus(422);
        $this->assertSame([], $this->avatarFiles());
    }

    public function test_valid_avatar_is_stored_with_random_name_and_content_based_extension(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->postJson('/user/avatar/upload', [
            'avatar' => UploadedFile::fake()->image('My Photo (1).png', 50, 50),
        ])->assertOk()->assertJsonPath('success', true);

        $files = $this->avatarFiles();
        $this->assertCount(1, $files);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}\.png$/', $files[0]);
        $this->assertSame('assets/avatar/' . $files[0], $user->fresh()->avatar);
    }

    public function test_profile_update_avatar_uses_the_same_protection(): void
    {
        $user = $this->makeSubscribedUser();

        $this->actingAs($user)->put('/user/profile', [
            'first_name' => 'A',
            'last_name' => 'B',
            'whatsapp_number' => '+923001234567',
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->createWithContent('shell.phtml', '<?php echo 1; ?>'),
        ])->assertSessionHasErrors('avatar');

        $this->assertSame([], $this->avatarFiles());
    }

    // ── Payment screenshots ──────────────────────────────────────────────

    private function submitPayment($user, UploadedFile $file)
    {
        $package = Package::first();
        $method = PaymentMethod::forceCreate(['name' => 'Bank', 'slug' => 'bank-' . uniqid(), 'is_active' => true]);

        return $this->actingAs($user)->post('/user/payment/submit', [
            'package_id' => $package->id,
            'payment_method_id' => $method->id,
            'screenshot' => $file,
        ]);
    }

    public function test_payment_screenshot_is_stored_privately_not_in_public(): void
    {
        $user = $this->makeSubscribedUser();

        $this->submitPayment($user, UploadedFile::fake()->image('receipt.jpg'))->assertRedirect();

        $payment = Payment::latest('id')->firstOrFail();
        $this->assertStringStartsWith('payments/', $payment->screenshot);
        Storage::disk('local')->assertExists($payment->screenshot);
        $this->assertSame([], File::files($this->publicDir . '/images/payments'));
    }

    public function test_payment_screenshot_rejects_non_images(): void
    {
        $user = $this->makeSubscribedUser();
        $before = Payment::count();

        $this->submitPayment($user, UploadedFile::fake()->createWithContent('receipt.php', '<?php echo 1; ?>'))
            ->assertSessionHasErrors('screenshot');

        $this->assertSame($before, Payment::count());
    }

    public function test_only_admins_can_view_payment_screenshots(): void
    {
        $user = $this->makeSubscribedUser();
        $this->submitPayment($user, UploadedFile::fake()->image('receipt.jpg'))->assertRedirect();
        $payment = Payment::latest('id')->firstOrFail();

        $this->actingAs($user)->get("/admin/payments/{$payment->id}/screenshot")->assertForbidden();
        $this->actingAs($this->makeSubscribedUser())->get("/admin/payments/{$payment->id}/screenshot")->assertForbidden();

        $this->actingAs($this->makeAdmin())->get("/admin/payments/{$payment->id}/screenshot")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_legacy_public_screenshots_are_served_to_admins_and_traversal_is_blocked(): void
    {
        $user = $this->makeSubscribedUser();
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $method = PaymentMethod::forceCreate(['name' => 'Bank', 'slug' => 'bank-legacy', 'is_active' => true]);

        File::put($this->publicDir . '/images/payments/legacy.jpg', 'fake-jpg-bytes');
        File::put($this->publicDir . '/secret.txt', 'top secret');

        $legacy = Payment::forceCreate([
            'subscription_id' => $subscription->id, 'user_id' => $user->id, 'payment_method_id' => $method->id,
            'amount' => 1, 'currency' => 'USD', 'status' => 'pending', 'screenshot' => 'images/payments/legacy.jpg',
        ]);
        $traversal = Payment::forceCreate([
            'subscription_id' => $subscription->id, 'user_id' => $user->id, 'payment_method_id' => $method->id,
            'amount' => 1, 'currency' => 'USD', 'status' => 'pending', 'screenshot' => 'images/payments/../../secret.txt',
        ]);

        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get("/admin/payments/{$legacy->id}/screenshot")->assertOk();
        $this->actingAs($admin)->get("/admin/payments/{$traversal->id}/screenshot")->assertNotFound();
    }

    // ── Web-server rules shipped with the app ────────────────────────────

    public function test_htaccess_rules_protect_uploads_and_app_internals(): void
    {
        $root = file_get_contents(base_path('.htaccess'));
        $this->assertStringContainsString('storage|tests|vendor', $root);
        $this->assertMatchesRegularExpression('/FilesMatch "\^\\\\\."/', $root);
        $this->assertStringContainsString('sql', $root);

        $payments = file_get_contents(base_path('public/images/payments/.htaccess'));
        $this->assertStringContainsString('Require all denied', $payments);

        foreach (['public/images/.htaccess', 'public/assets/avatar/.htaccess'] as $path) {
            $rules = file_get_contents(base_path($path));
            $this->assertStringContainsString('php', $rules, $path);
            $this->assertStringContainsString('Require all denied', $rules, $path);
        }
    }
}
