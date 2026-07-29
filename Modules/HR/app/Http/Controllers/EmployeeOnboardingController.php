<?php

namespace Modules\HR\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HR\Models\Employee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use App\Models\Company;

class EmployeeOnboardingController extends Controller
{
    public function step1()
    {
        $step1 = session('step1', []);
        $clientId = (int) session('employee_client_id');
        $company = $clientId > 0 ? Company::find($clientId) : null;
        $clientCountryCode = strtoupper((string) ($company?->country_code ?? ''));
        $clientLocale = config('client_locales.countries.'.$clientCountryCode, []);
        $clientPhonePrefix = (string) ($clientLocale['dial_code'] ?? '');
        $clientNationality = self::nationalityForCountry($clientCountryCode);
        $companyEmailPreview = null;

        if (! empty($step1['first_name']) && ! empty($step1['last_name'])) {
            $companyEmailPreview = self::generateUniqueCompanyEmail(
                $step1['first_name'],
                $step1['last_name']
            );
        }

        $companyEmailDomain = self::companyEmailDomain();

        return view('employees.onboarding.step1', compact(
            'step1', 'companyEmailPreview', 'companyEmailDomain', 'clientCountryCode', 'clientPhonePrefix', 'clientNationality'
        ));
    }

    public function storeStep1(Request $request)
{
    $clientId = (int) session('employee_client_id');
    abort_unless($clientId > 0, 403, 'A client-scoped HR session is required to create an employee.');

    $request->validate([
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => [
            'required',
            'email',
            Rule::unique('hr.employees', 'email')->where('client_id', $clientId),
        ],
        'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'phone' => 'nullable|string|max:20',
        'phone_dial_code' => ['nullable', 'string', 'max:8', 'regex:/^\+?[0-9]+$/'],
    ]);

    // Keep the local part in the onboarding session. The complete E.164-like
    // number is assembled only when the employee is finally created.
    $data = $request->except('profile_picture');

    if ($request->hasFile('profile_picture')) {
        File::ensureDirectoryExists(public_path('profile_pictures'));
        $imageName = time() . '.' . $request->file('profile_picture')->extension();
        $request->file('profile_picture')->move(public_path('profile_pictures'), $imageName);

        $data['profile_picture'] = $imageName;
    }

    session(['step1' => $data]);

    return redirect()->route('hr.onboarding.step2');
}

    public function step2()
    {
        if (! session('step1')) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Please complete step 1 first.');
        }

        return view('employees.onboarding.step2');
    }

    public function storeStep2(Request $request)
    {
        if (! session('step1')) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Please complete step 1 first.');
        }

        $validated = $request->validate([
            'department' => 'required|string',
            'position' => 'required|string',
            'hire_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['end_time']);

        if ($end->lte($start)) {
            return back()
                ->withErrors(['end_time' => 'End Time must be after Start Time.'])
                ->withInput();
        }

        $validated['start_time'] = $start->format('H:i');
        $validated['end_time'] = $end->format('H:i');
        $validated['work_schedule'] = $validated['start_time'].'-'.$validated['end_time'];

        session(['step2' => $validated]);

        return redirect()->route('hr.onboarding.step3');
    }

    public function step3()
    {
        if (! session('step1')) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Please complete step 1 first.');
        }

        if (! session('step2')) {
            return redirect()->route('hr.onboarding.step2')
                ->with('error', 'Please complete step 2 first.');
        }

        return view('employees.onboarding.step3');
    }

    public function storeStep3(Request $request)
    {
        if (! session('step1') || ! session('step2')) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Your onboarding session expired. Please start again.');
        }

        $request->validate([
            'birth_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'curriculum_vitae' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'valid_id' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data = [
            'birth_certificate' => $request->file('birth_certificate')->store('documents', 'public'),
            'curriculum_vitae' => $request->file('curriculum_vitae')->store('documents', 'public'),
            'valid_id' => $request->file('valid_id')->store('documents', 'public'),
        ];

        session(['step3' => $data]);

        return redirect()->route('hr.onboarding.step4');
    }

    public function step4()
    {
        if (! session('step1')) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Please complete step 1 first.');
        }

        if (! session('step2')) {
            return redirect()->route('hr.onboarding.step2')
                ->with('error', 'Please complete step 2 first.');
        }

        if (! session('step3')) {
            return redirect()->route('hr.onboarding.step3')
                ->with('error', 'Please complete step 3 first.');
        }

        $step1 = session('step1');
        $companyEmailPreview = self::generateUniqueCompanyEmail(
            $step1['first_name'],
            $step1['last_name']
        );

        return view('employees.onboarding.step4', compact('companyEmailPreview'));
    }

    public function storeStep4(Request $request)
    {
        $step1 = session('step1');
        $step2 = session('step2');
        $step3 = session('step3');

        if (! $step1 || ! $step2 || ! $step3) {
            return redirect()->route('hr.onboarding.step1')
                ->with('error', 'Your onboarding session expired. Please start again.');
        }

        // The current onboarding UI uses one consolidated acknowledgement for
        // the policy pack. The old standalone flow still expected six hidden
        // policy fields, which made this step fail even when the visible box
        // was checked.
        $request->validate([
            'policy_agreement' => ['accepted'],
        ]);

        $clientId = (int) session('employee_client_id');
        abort_unless($clientId > 0, 403, 'A client-scoped HR session is required to create an employee.');

        if (Employee::where('email', $step1['email'])->exists()) {
            return redirect()->route('hr.onboarding.step1')
                ->withErrors(['email' => 'An employee with this email already exists for your client.'])
                ->withInput($step1);
        }

        $companyEmail = self::generateUniqueCompanyEmail(
            $step1['first_name'],
            $step1['last_name']
        );
        $plainPassword = 'NEX-' . Str::upper(Str::random(6));

    $employee = Employee::create([
        'first_name' => $step1['first_name'],
        'middle_name' => $step1['middle_name'] ?? null,
        'last_name' => $step1['last_name'],
        'suffix' => $step1['suffix'] ?? null,
        'gender' => $step1['gender'] ?? null,
        'marital_status' => $step1['marital_status'] ?? null,
        'nationality' => $step1['nationality'] ?? null,
        'address' => $step1['address'] ?? null,
        'phone' => $this->normalisePhone(
            (string) ($step1['phone_dial_code'] ?? $this->clientDialCode($clientId)),
            (string) ($step1['phone'] ?? '')
        ),
        'email' => $step1['email'],
        'profile_picture' => $step1['profile_picture'] ?? null,
        'department' => $step2['department'],
        'position' => $step2['position'],
        'hire_date' => $step2['hire_date'],
        'work_schedule' => $step2['work_schedule'],
        'birth_certificate' => $step3['birth_certificate'] ?? null,
        'curriculum_vitae' => $step3['curriculum_vitae'] ?? null,
        'valid_id' => $step3['valid_id'] ?? null,
        'medical_certificate' => $step3['medical_certificate'] ?? null,
        'company_email' => $companyEmail,
        'temporary_password' => Hash::make($plainPassword),
        'must_change_password' => true,
        'client_id' => $clientId,
        'approval_status' => 'Pending',
    ]);

    // Use the same collision-safe ID policy as the employee-management form.
    $employee->updateQuietly([
        'employee_id' => Employee::employeeCodeForId((int) $employee->id),
    ]);

    session()->forget(['step1', 'step2', 'step3']);
    // Keep the one-time credential available only for the success screen;
    // the HR database stores a hash and ITSM controls activation.
    $employee->temporary_password = $plainPassword;
    session(['employee' => $employee]);

    return redirect()->route('hr.onboarding.success');
}

    public function success()
    {
        $employee = session('employee');

        if (! $employee) {
            return redirect()->route('hr.onboarding.step1');
        }

        return view('employees.onboarding.success', compact('employee'));
    }

    private function clientDialCode(int $clientId): string
    {
        $countryCode = strtoupper((string) Company::query()->whereKey($clientId)->value('country_code'));

        return (string) config('client_locales.countries.'.$countryCode.'.dial_code', '');
    }

    private function normalisePhone(string $dialCode, string $localNumber): ?string
    {
        $localDigits = preg_replace('/\D+/', '', $localNumber) ?: '';
        $dialDigits = preg_replace('/\D+/', '', $dialCode) ?: '';

        if ($localDigits === '') {
            return null;
        }

        return $dialDigits === '' ? $localDigits : '+'.$dialDigits.$localDigits;
    }

    private static function nationalityForCountry(string $countryCode): ?string
    {
        return [
            'PH' => 'Filipino', 'US' => 'American', 'GB' => 'British', 'CA' => 'Canadian', 'AU' => 'Australian',
            'JP' => 'Japanese', 'CN' => 'Chinese', 'HK' => 'Chinese', 'KR' => 'South Korean', 'IN' => 'Indian',
            'ID' => 'Indonesian', 'MY' => 'Malaysian', 'SG' => 'Singaporean', 'TH' => 'Thai', 'VN' => 'Vietnamese',
            'DE' => 'German', 'FR' => 'French', 'MX' => 'Mexican', 'BR' => 'Brazilian', 'AE' => 'Emirati',
            'SA' => 'Saudi Arabian', 'NZ' => 'New Zealander',
        ][$countryCode] ?? null;
    }

    /**
     * firstnamelastname@company-nexora.mail, or a numbered local part when
     * that client already has the same generated address.
     */
    public static function generateUniqueCompanyEmail(string $firstName, string $lastName): string
    {
        $firstName = preg_replace('/\s+/', '', $firstName);
        $lastName = preg_replace('/\s+/', '', $lastName);
        $base = strtolower($firstName.$lastName);
        $domain = self::companyEmailDomain();
        $email = $base.'@'.$domain;

        if (! Employee::where('company_email', $email)->exists()) {
            return $email;
        }

        $suffix = 2;
        while (Employee::where('company_email', $base.$suffix.'@'.$domain)->exists()) {
            $suffix++;
        }

        return $base.$suffix.'@'.$domain;
    }

    public static function companyEmailDomain(): string
    {
        $company = Company::find((int) session('employee_client_id'));
        $segment = Str::of((string) ($company?->company_name ?? 'company'))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();

        return ($segment !== '' ? $segment : 'company').'-nexora.mail';
    }
}
