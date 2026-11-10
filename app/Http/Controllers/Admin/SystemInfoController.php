<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Composer\InstalledVersions;
use App\Services\AssistantSettingsService;
use App\Models\AssistantUsageLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SystemInfoController extends Controller
{
    public function index(AssistantSettingsService $assistantSettings): View
    {
        $root = base_path();
        $composer = $this->readJson($root . '/composer.json');
        $composerLock = $this->readJson($root . '/composer.lock');
        $package = $this->readJson($root . '/package.json');
        $packageLock = $this->readJson($root . '/package-lock.json');

        $phpConstraint = $composer['require']['php'] ?? 'No definido';
        $phpMinimum = $this->minimumPhpVersion($phpConstraint);
        $phpSupported = $phpMinimum === null || version_compare(PHP_VERSION, $phpMinimum, '>=');

        $phpPackages = [];
        foreach ($composer['require'] ?? [] as $name => $constraint) {
            if ($name === 'php') {
                continue;
            }

            $lockedVersion = $this->lockedComposerVersion($composerLock, $name);
            $installedVersion = InstalledVersions::isInstalled($name)
                ? (string) InstalledVersions::getPrettyVersion($name)
                : 'No detectado en vendor';
            $phpPackages[] = [
                'name' => $name,
                'constraint' => $constraint,
                'locked' => $lockedVersion,
                'installed' => $installedVersion,
                'matches' => $this->normalizeVersion($lockedVersion) === $this->normalizeVersion($installedVersion),
            ];
        }

        $npmPackages = [];
        foreach ($package['dependencies'] ?? [] as $name => $constraint) {
            $npmPackages[] = $this->npmPackageRow($name, $constraint, $packageLock, 'Producción');
        }
        foreach ($package['devDependencies'] ?? [] as $name => $constraint) {
            $npmPackages[] = $this->npmPackageRow($name, $constraint, $packageLock, 'Desarrollo / build');
        }

        $requiredExtensions = ['ctype', 'curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'PDO', 'tokenizer', 'xml'];
        $requiredExtensions[] = config('database.default') === 'mysql' ? 'pdo_mysql' : 'pdo_sqlite';
        $extensions = array_map(fn (string $extension) => [
            'name' => $extension,
            'loaded' => extension_loaded($extension),
        ], $requiredExtensions);

        $usageTableExists = Schema::hasTable('assistant_usage_logs');
        $usageLogs = $usageTableExists
            ? AssistantUsageLog::query()->with('user:id,name')->latest('id')->paginate(25, [
                'id', 'user_id', 'user_name', 'conversation_id', 'user_message', 'assistant_message',
                'model', 'input_tokens', 'output_tokens', 'total_tokens', 'tool_calls',
                'duration_ms', 'status', 'error_type', 'created_at',
            ])
            : null;
        $usageTotals = $usageTableExists ? [
            'requests' => AssistantUsageLog::count(),
            'input_tokens' => (int) AssistantUsageLog::sum('input_tokens'),
            'output_tokens' => (int) AssistantUsageLog::sum('output_tokens'),
            'total_tokens' => (int) AssistantUsageLog::sum('total_tokens'),
            'failed' => AssistantUsageLog::where('status', 'failed')->count(),
        ] : null;
        $quotaUsers = null;
        if ($usageTableExists) {
            $timezone = config('assistant.quota_timezone', 'America/Santiago');
            $localNow = CarbonImmutable::now($timezone);
            $startUtc = $localNow->startOfDay()->utc();
            $endUtc = $localNow->addDay()->startOfDay()->utc();
            $quotaUsers = User::query()
                ->with('roles:id,name,slug')
                ->orderBy('name')
                ->paginate(25, ['id', 'name', 'email'], 'quota_users_page');
            $requestsByUser = AssistantUsageLog::query()
                ->whereIn('user_id', $quotaUsers->getCollection()->pluck('id'))
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->selectRaw('user_id, COUNT(*) as request_count')
                ->groupBy('user_id')
                ->pluck('request_count', 'user_id');
            $lifetimeRequestsByUser = AssistantUsageLog::query()
                ->whereIn('user_id', $quotaUsers->getCollection()->pluck('id'))
                ->selectRaw('user_id, COUNT(*) as request_count')
                ->groupBy('user_id')
                ->pluck('request_count', 'user_id');
            $quotaUsers->getCollection()->transform(function (User $user) use ($requestsByUser, $lifetimeRequestsByUser) {
                $user->assistant_requests_today = (int) ($requestsByUser[$user->id] ?? 0);
                $user->assistant_requests_lifetime = (int) ($lifetimeRequestsByUser[$user->id] ?? 0);
                $user->assistant_is_admin = $user->roles->contains('slug', 'admin');

                return $user;
            });
        }

        return view('admin.system-info.index', [
            'phpConstraint' => $phpConstraint,
            'phpSupported' => $phpSupported,
            'phpPackages' => $phpPackages,
            'npmPackages' => $npmPackages,
            'extensions' => $extensions,
            'databaseDriver' => config('database.default'),
            'laravelVersion' => app()->version(),
            'phpSapi' => PHP_SAPI,
            'assistantSettings' => [
                'system_prompt' => $assistantSettings->prompt(),
                'model' => $assistantSettings->model(),
                'reasoning_effort' => $assistantSettings->reasoningEffort(),
                'max_output_tokens' => $assistantSettings->maxOutputTokens(),
            ],
            'assistantEnabled' => (bool) config('services.openai.enabled'),
            'usageTableExists' => $usageTableExists,
            'usageLogs' => $usageLogs,
            'usageTotals' => $usageTotals,
            'quotaUsers' => $quotaUsers,
            'asset_css' => ['comun/tablas'],
        ]);
    }

    public function updateAssistantSettings(Request $request, AssistantSettingsService $assistantSettings): RedirectResponse
    {
        $validated = $request->validate([
            'system_prompt' => ['required', 'string', 'min:30', 'max:12000'],
            'model' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'reasoning_effort' => ['required', 'in:none,low,medium,high,xhigh,max'],
            'max_output_tokens' => ['required', 'integer', 'min:100', 'max:3000'],
        ]);

        $assistantSettings->update([
            'system_prompt' => trim($validated['system_prompt']),
            'model' => $validated['model'],
            'reasoning_effort' => $validated['reasoning_effort'],
            'max_output_tokens' => (string) $validated['max_output_tokens'],
        ], $request->user()?->id);

        return redirect()->route('admin.system-info.index')->with('success', 'Configuración del asistente actualizada.');
    }

    private function readJson(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function lockedComposerVersion(array $lock, string $name): string
    {
        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $package) {
            if (($package['name'] ?? null) === $name) {
                return $package['version'] ?? 'No fijada';
            }
        }

        return 'No fijada';
    }

    private function npmPackageRow(string $name, string $constraint, array $lock, string $scope): array
    {
        $locked = $lock['packages']['node_modules/' . $name]['version'] ?? 'No fijada';

        return [
            'name' => $name,
            'constraint' => $constraint,
            'locked' => $locked,
            'scope' => $scope,
        ];
    }

    private function minimumPhpVersion(string $constraint): ?string
    {
        if (preg_match('/\^\s*(\d+\.\d+(?:\.\d+)?)/', $constraint, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(?:>=|>)\s*(\d+\.\d+(?:\.\d+)?)/', $constraint, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'v');
    }
}
