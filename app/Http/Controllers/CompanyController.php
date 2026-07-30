<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\HrEmployeeProfileProvisioner;
use App\Services\ErpIntegrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function __construct(
        private readonly HrEmployeeProfileProvisioner $hrEmployeeProfileProvisioner,
    )
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:100'],
            'company_email' => ['required', 'email', 'max:255', 'unique:companies,company_email'],
            'phone_no' => ['nullable', 'string', 'max:50'],
            'country_code' => ['required', 'string', Rule::in(array_keys(config('client_locales.countries', [])))],
            'timezone' => ['required', 'timezone'],
            'admin_name' => ['required', 'string', 'max:255'],
        ]);
        $validated = $this->applyLocaleDefaults($validated);

        $baseUsername = $this->buildAdminUsername($validated['company_name'], $validated['admin_name']);
        $username = $this->uniqueUsername($baseUsername);
        $password = Str::random(14);

        DB::transaction(function () use ($validated, $username, $password): void {
            $company = Company::create($validated + [
                'status' => 'Active',
                'ecommerce_slug' => $this->uniqueEcommerceSlug($validated['company_name']),
            ]);

            $admin = User::create([
                'name' => $validated['admin_name'],
                'username' => $username,
                'email' => $username,
                'password' => Hash::make($password),
                'role' => 'company_admin',
                'company_id' => $company->id,
            ]);

            $company->update(['admin_user_id' => $admin->id]);
        });

        return redirect()
            ->route('admin.itsm.clients')
            ->with('success', 'Company registered successfully.')
            ->with('generated_credentials', [
                'username' => $username,
                'password' => $password,
            ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:100'],
            'company_email' => ['required', 'email', 'max:255', 'unique:companies,company_email,' . $company->id],
            'phone_no' => ['nullable', 'string', 'max:50'],
            'country_code' => ['required', 'string', Rule::in(array_keys(config('client_locales.countries', [])))],
            'timezone' => ['required', 'timezone'],
            'admin_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'admin_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);
        $validated = $this->applyLocaleDefaults($validated);

        $companyValues = collect($validated)->except([
            'admin_password',
            'admin_password_confirmation',
            'logo',
        ])->all();

        $previousLogoPath = $company->logo_path;
        if ($request->hasFile('logo')) {
            $companyValues['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($companyValues);

        if (isset($companyValues['logo_path'])
            && $previousLogoPath
            && ! filter_var($previousLogoPath, FILTER_VALIDATE_URL)
            && $previousLogoPath !== $companyValues['logo_path']) {
            Storage::disk('public')->delete($previousLogoPath);
        }

        if ($company->adminUser) {
            $company->adminUser->update(['name' => $validated['admin_name']]);

            if (! empty($validated['admin_password'])) {
                $company->adminUser->update(['password' => Hash::make($validated['admin_password'])]);
                app(ErpIntegrationService::class)->recordAudit((int) $company->id, 'client_admin.password_changed', 'ITSM', [
                    'changed_by' => auth()->id(),
                    'username' => $company->adminUser->username,
                ]);
            }

        }

        return redirect()
            ->route('admin.itsm.clients')
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'admin_name_confirmation' => ['required', 'string'],
        ]);

        if (! hash_equals($company->admin_name, $validated['admin_name_confirmation'])) {
            return redirect()
                ->route('admin.itsm.clients')
                ->withErrors(['admin_name_confirmation' => 'The system admin name confirmation did not match.']);
        }

        DB::transaction(function () use ($company): void {
            $adminUser = $company->adminUser;
            $company->delete();

            if ($adminUser) {
                $adminUser->delete();
            }

            $this->hrEmployeeProfileProvisioner->deleteEmployeesForCompany($company);
        });

        return redirect()
            ->route('admin.itsm.clients')
            ->with('success', 'Client deleted successfully.');
    }

    private function buildAdminUsername(string $companyName, string $adminName): string
    {
        $company = $this->credentialSegment($companyName, 'company');
        $admin = $this->credentialSegment($adminName, 'admin');

        // A company system admin is a client identity, not a root Nexora
        // identity.  Keep the same address convention used by HR employees.
        return "{$admin}@{$company}-nexora.mail";
    }

    private function credentialSegment(string $value, string $fallback): string
    {
        $segment = Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();

        return $segment !== '' ? $segment : $fallback;
    }

    private function uniqueUsername(string $username): string
    {
        if (! User::where('username', $username)->exists()) {
            return $username;
        }

        [$local, $domain] = explode('@', $username, 2);
        $counter = 2;

        do {
            $candidate = "{$local}{$counter}@{$domain}";
            $counter++;
        } while (User::where('username', $candidate)->exists());

        return $candidate;
    }

    private function uniqueEcommerceSlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        $base = $base !== '' ? $base : 'store';
        $candidate = $base;
        $counter = 2;

        while (Company::where('ecommerce_slug', $candidate)->exists()) {
            $candidate = "{$base}-{$counter}";
            $counter++;
        }

        return $candidate;
    }

    /** Apply the selected country prefix without duplicating a prefix already typed by the user. */
    private function applyLocaleDefaults(array $validated): array
    {
        $validated['country_code'] = strtoupper($validated['country_code']);
        $locale = config('client_locales.countries.'.$validated['country_code'], []);
        $prefix = (string) ($locale['dial_code'] ?? '');
        $phone = trim((string) ($validated['phone_no'] ?? ''));

        if ($phone !== '' && $prefix !== '' && ! str_starts_with(str_replace([' ', '-'], '', $phone), $prefix)) {
            $validated['phone_no'] = $prefix.' '.$phone;
        }

        return $validated;
    }
}
