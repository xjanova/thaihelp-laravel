<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SetupController extends Controller
{
    /**
     * Show the first-time setup wizard.
     */
    public function index()
    {
        // If setup already completed, redirect to home
        if ($this->isSetupCompleted()) {
            return redirect('/');
        }

        $dbReady = $this->checkDatabase();

        return view('pages.setup', [
            'dbReady' => $dbReady,
            'tables' => $dbReady ? $this->getExistingTables() : [],
        ]);
    }

    /**
     * Step 1: Run database migrations.
     */
    public function migrate(Request $request)
    {
        if (SiteSetting::get('setup_completed') === '1' || SiteSetting::get('setup_completed') === 'true') {
            return response()->json(['success' => false, 'error' => 'Setup already completed'], 403);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Artisan::call('db:seed', ['--force' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Database migrated successfully',
                'output' => $output,
                'tables' => $this->getExistingTables(),
            ]);
        } catch (\Exception $e) {
            Log::error('Migration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Migration failed. Check server logs for details.',
            ], 500);
        }
    }

    /**
     * Step 2: Create admin account and save site settings.
     */
    public function configure(Request $request)
    {
        if (SiteSetting::get('setup_completed') === '1' || SiteSetting::get('setup_completed') === 'true') {
            return response()->json(['success' => false, 'error' => 'Setup already completed'], 403);
        }

        $request->validate([
            'site_name' => 'required|string|max:100',
            'site_description' => 'string|max:255',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:6|max:100',
            'default_map_lat' => 'nullable|numeric',
            'default_map_lng' => 'nullable|numeric',
        ]);

        try {
            // Create or update admin user
            $admin = User::updateOrCreate(
                ['email' => $request->admin_email],
                [
                    'name' => $request->admin_name,
                    'nickname' => $request->admin_name,
                    'email' => $request->admin_email,
                    'password' => Hash::make($request->admin_password),
                ]
            );
            $admin->is_admin = true;
            $admin->save();

            // Save site settings
            SiteSetting::set('site_name', $request->site_name, 'general');
            SiteSetting::set('site_description', $request->site_description ?? 'ชุมชนช่วยเหลือนักเดินทาง', 'general');
            SiteSetting::set('default_map_lat', $request->default_map_lat ?? '13.7563', 'map');
            SiteSetting::set('default_map_lng', $request->default_map_lng ?? '100.5018', 'map');
            SiteSetting::set('setup_completed', 'true', 'system');

            // Bust the CheckSetup middleware cache so redirect stops immediately
            \Illuminate\Support\Facades\Cache::forget('setup_completed');

            return response()->json([
                'success' => true,
                'message' => 'Setup completed!',
                'admin_id' => $admin->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Setup configure failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Configuration failed. Check server logs for details.',
            ], 500);
        }
    }

    /**
     * Check if setup has been completed.
     */
    private function isSetupCompleted(): bool
    {
        try {
            if (!Schema::hasTable('site_settings')) {
                return false;
            }
            $val = SiteSetting::get('setup_completed');
            return $val === 'true' || $val === '1';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if database connection works.
     */
    private function checkDatabase(): bool
    {
        try {
            Schema::hasTable('users');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get list of existing tables.
     */
    private function getExistingTables(): array
    {
        try {
            $tables = Schema::getTables();
            return array_map(fn($t) => $t['name'], $tables);
        } catch (\Exception $e) {
            return [];
        }
    }
}
