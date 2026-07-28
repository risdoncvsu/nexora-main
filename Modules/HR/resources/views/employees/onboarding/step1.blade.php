<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Employee Onboarding</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.2.3/css/flag-icons.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
            },
        },
    };
</script>
<style>
    html, body {
        font-family: 'Inter', sans-serif;
    }
</style>
</head>

 @include('partials.navbar')
 
<body class="bg-[#1B3A6B] min-h-screen font-sans">

<h1 class="text-white pt-[20px] pl-[100px] text-[28px] font-bold tracking-wide mb-8 text-left">EMPLOYEE ONBOARDING</h1>

  <div class="pt-[24px]">
    <!-- Employee Onboarding Content -->

<div class="max-w-7xl mx-auto">

   <!-- Title -->
    
    @include('partials.onboarding-stepper', ['currentStep' => 1])

<div class="flex flex-col lg:flex-row gap-12">

      @php $step1Data = $step1 ?? []; @endphp

      <form
    id="onboarding-step1-form"
    action="{{ route('hr.onboarding.storeStep1') }}"
    method="POST"
    enctype="multipart/form-data"
    class="contents">

    @csrf

      <!-- Left: form -->
      <div class="flex-1 space-y-4 max-w-3xl">
        <h2 class="text-white text-sm font-400 tracking-wide mb-4">PERSONAL INFORMATION</h2>

@if (session('error'))
    <div class="mb-4 rounded bg-red-500/20 border border-red-400 text-red-200 px-4 py-3 text-sm">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded bg-amber-500/10 border border-amber-400/40 text-amber-100 px-4 py-3 text-sm">
        Please review the highlighted fields below.
    </div>
@endif

          <!-- Name row -->
          <div class="flex items-start gap-4">

  <!-- First Name -->
  <div>
    <label class="block text-slate-300 text-xs mb-1">First Name</label>
    <div class="relative">
     <input
    type="text"
    name="first_name"
    value="{{ old('first_name', $step1Data['first_name'] ?? '') }}"
    required
    class="name-field w-[220px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500"
      />
    @error('first_name')
        <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
    @enderror
    </div>
  </div>

  <!-- Middle Name -->
  <div>
    <label class="block text-slate-300 text-xs mb-1">Middle Name</label>
    <div class="relative">
      <input
        type="text" name="middle_name"
        value="{{ old('middle_name', $step1Data['middle_name'] ?? '') }}"
        class="name-field w-[220px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500"
      />
    </div>
  </div>

  <!-- Last Name -->
  <div>
    <label class="block text-slate-300 text-xs mb-1">Last Name</label>
    <div class="relative">
      <input
        type="text" name="last_name"
        value="{{ old('last_name', $step1Data['last_name'] ?? '') }}"
        required
        class="name-field w-[220px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500"
      />
    @error('last_name')
        <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
    @enderror
    </div>
  </div>

  <!-- Suffix -->
<div>
    <label class="block text-slate-300 text-xs mb-1">Suffix</label>

    <div class="relative w-[118px]">
        <select
            name="suffix"
            class="w-full h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500 appearance-none"
        >
            <option class="hidden"></option>
            <option value="Jr." {{ old('suffix', $step1Data['suffix'] ?? '') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
            <option value="Sr." {{ old('suffix', $step1Data['suffix'] ?? '') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
            <option value="II" {{ old('suffix', $step1Data['suffix'] ?? '') == 'II' ? 'selected' : '' }}>II</option>
            <option value="III" {{ old('suffix', $step1Data['suffix'] ?? '') == 'III' ? 'selected' : '' }}>III</option>
            <option value="IV" {{ old('suffix', $step1Data['suffix'] ?? '') == 'IV' ? 'selected' : '' }}>IV</option>
            <option value="V" {{ old('suffix', $step1Data['suffix'] ?? '') == 'V' ? 'selected' : '' }}>V</option>
            <option value="NA" {{ old('suffix', $step1Data['suffix'] ?? '') == 'NA' ? 'selected' : '' }}>N/A</option>
        </select>

        <!-- Dropdown Arrow -->
        <svg
            class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 9l-7 7-7-7"
            />
        </svg>
    </div>
</div>

</div>

          <!-- Gender / Marital / Nationality row -->
       <div class="flex items-start gap-6">
            <div class="flex items-start gap-8">

 <!-- Gender -->
<div>
    <label class="block text-slate-300 text-xs mb-1">Gender</label>

    <div class="relative w-[253px]">
        <select
            name="gender"
            class="w-full h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500 appearance-none"
        >
            <option class="hidden"></option>
            <option value="Male" {{ old('gender', $step1Data['gender'] ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
            <option value="Female" {{ old('gender', $step1Data['gender'] ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
            <option value="Prefer not to say" {{ old('gender', $step1Data['gender'] ?? '') == 'Prefer not to say' ? 'selected' : '' }}>Prefer not to say</option>
        </select>

        <!-- Dropdown Arrow -->
        <svg
            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 9l-7 7-7-7"
            />
        </svg>
    </div>
</div>

 <div>
    <label class="block text-slate-300 text-xs mb-1">Marital Status</label>

    <div class="relative w-[253px]">
        <select
            name="marital_status"
            class="w-full h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 pr-8 outline-none focus:ring-1 focus:ring-blue-500 appearance-none"
        >
            <option class="hidden"></option>
            <option value="Single" {{ old('marital_status', $step1Data['marital_status'] ?? '') == 'Single' ? 'selected' : '' }}>Single</option>
            <option value="Married" {{ old('marital_status', $step1Data['marital_status'] ?? '') == 'Married' ? 'selected' : '' }}>Married</option>
            <option value="Widowed" {{ old('marital_status', $step1Data['marital_status'] ?? '') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
        </select>

        <!-- Dropdown Arrow -->
        <svg
            class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
</div>  

  <!-- Nationality -->
  <div>
    <label class="block text-slate-300 text-xs mb-1">Nationality</label>
    <div class="relative">

      <input type="hidden" name="nationality" id="nationality_value" value="{{ old('nationality', $step1Data['nationality'] ?? '') }}" />

      <!-- Trigger button (looks like the other selects) -->
      <button
        type="button"
        id="nationality_trigger"
        class="w-[253px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500 flex items-center justify-between"
      >
        <span id="nationality_display" class="flex items-center gap-2 text-slate-400">
          <span></span>
        </span>
       <!-- Dropdown Arrow -->
        <svg
            class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19 9l-7 7-7-7"
            />
        </svg>
      </button>

      <!-- Dropdown list -->
      <div
        id="nationality_dropdown"
        class="hidden absolute z-20 mt-1 w-[253px] max-h-[220px] overflow-y-auto bg-[#0d1730] border border-white/10 rounded shadow-lg"
      ></div>

    </div>
  </div>

</div>
</div>

          <!-- Address -->
          <div>
            <label class="block text-slate-300 text-xs mb-1">Address</label>
            <div class="relative">
              <input type="text"  name="address" value="{{ old('address', $step1Data['address'] ?? '') }}" class="w-[825px] bg-[#0d1730] text-white text-sm rounded px-3 py-2 pr-8 outline-none focus:ring-1 focus:ring-blue-500" />
           
            </div>
          </div>

          <!-- Email / Phone row -->
          <div class="flex items-start gap-6 pt-2">
            <div>
              <label class="block text-slate-300 text-xs mb-1">Email</label>
              <div class="relative">
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $step1Data['email'] ?? '') }}"
                    maxlength="254"
                    required
                    class="w-[540px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 py-2 pr-8 outline-none focus:ring-1 focus:ring-blue-500" />
                @error('email')
                    <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
                @enderror
              </div>
            </div>
            <div>
  <label class="block text-slate-300 text-xs mb-1">Phone Number</label>
  <div class="relative flex items-center gap-2">

    <!-- Dial code prefix box -->
    <div
      id="phone_code_box"
      class="h-[28px] px-3 flex items-center gap-1.5 bg-[#0d1730] text-white text-sm rounded border border-white/10 shrink-0"
    >
      <span id="phone_code_flag"></span>
      <span id="phone_code_text" class="text-slate-300">+--</span>
    </div>

    <input
        type="tel"
        name="phone"
        id="phone"
        value="{{ old('phone', $step1Data['phone'] ?? '') }}"
        inputmode="numeric"
        class="w-[200px] h-[28px] bg-[#0d1730] text-white text-sm rounded px-3 py-2 outline-none focus:ring-1 focus:ring-blue-500" />

  </div>
</div>
          </div>

          <!-- Company Email Preview -->
          <div class="pt-2">
            <label class="block text-slate-300 text-xs mb-1">Company Email (auto-generated)</label>
            <div class="relative">
              <input
                  type="text"
                  id="company_email_preview"
                  readonly
                  value="{{ $companyEmailPreview ?? '' }}"
                  placeholder="Generated from first and last name"
                  class="w-[452px] h-[28px] bg-[#0d1730] text-slate-300 text-sm rounded px-3 py-2 outline-none border border-white/10" />
            </div>
            <p class="mt-1 text-[11px] text-slate-400">If the same name already exists, a number is added (e.g. johnsmith2@{{ $companyEmailDomain ?? 'company-nexora.mail' }}).</p>
          </div>

          <!-- Next button -->
          <div class="pt-8">
           <button
    type="submit"
     class="w-[218px] h-[56px] border-0 border-[0.1px] border-[#dcdcdc54] rounded-md bg-[#0061FF20] text-white text-[0.9375rem] font-normal tracking-[.3px] cursor-pointer shadow-[0_8px_20px_rgba(0,0,0,.25)] transition-all duration-250 hover:bg-[#0061FF10] hover:-translate-y-0.5 active:scale-[.97]">
    NEXT

  
</button>
          </div>
      </div>

        <!-- Right: profile picture upload -->
      <div class="w-full ml-40 lg:w-[200px] flex flex-col items-center">
        <h2 class="text-white text-sm font-300 tracking-wide  mb-4 self-start lg:self-center">PROFILE IMAGE</h2>

        <label for="profile_picture" class="cursor-pointer group">
    <div class="relative w-[300px] h-[300px] rounded-full bg-[#77B2FF] flex items-center justify-center overflow-hidden shadow-lg shadow-black/30 transition">

        <!-- Placeholder icon -->
<svg
    id="placeholder"
    class="w-48 h-48 text-[#1B3A6B] transition"
    viewBox="0 0 24 24"
    fill="currentColor"
>
    <!-- Head -->
    <circle cx="12" cy="8" r="4.5"/>

    <!-- Body -->
    <path d="M12 13c-4.8 0-8.5 2.4-8.5 5.5V20h17v-1.5c0-3.1-3.7-5.5-8.5-5.5z"/>
</svg>
        <!-- Preview image -->
        <img
            id="imagePreview"
            src=""
            alt="Profile Preview"
            class="hidden w-full h-full object-cover"
        >

        <!-- Hover overlay -->
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
            <span class="text-white text-[11px] font-semibold tracking-wide">
                CHANGE PHOTO
            </span>
        </div>

    </div>
</label>

        <input
            type="file"
            name="profile_picture"
            id="profile_picture"
            accept="image/png, image/jpeg, image/jpg"
            class="hidden"
        
            onchange="previewImage(event)">

        <p class="text-slate-400 text-[11px] mt-3 text-center max-w-[180px]">
            JPG or PNG. Max size 2MB.
        </p>

        <p id="profilePictureError" class="text-red-400 text-[11px] mt-1 text-center max-w-[180px] hidden"></p>
      </div>

      </form>

      
   <script>
function previewImage(event) {

    const file = event.target.files[0];
    const errorEl = document.getElementById('profilePictureError');

    errorEl.classList.add('hidden');
    errorEl.textContent = '';

    if (!file) return;

    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    const maxSizeBytes = 2 * 1024 * 1024; // 2MB

    if (!allowedTypes.includes(file.type)) {
        errorEl.textContent = 'Only JPG or PNG files are allowed.';
        errorEl.classList.remove('hidden');
        event.target.value = '';
        return;
    }

    if (file.size > maxSizeBytes) {
        errorEl.textContent = 'File must be 2MB or smaller.';
        errorEl.classList.remove('hidden');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        document.getElementById('imagePreview').src = e.target.result;
        document.getElementById('imagePreview').classList.remove('hidden');
        document.getElementById('placeholder').classList.add('hidden');
    };

    reader.readAsDataURL(file);
}

/*
  Prevent name-type fields (First Name, Middle Name, Last Name, Nationality)
  from starting with a lowercase letter. Once there is already a character
  in the field, lowercase letters are allowed as normal (e.g. "McDonald",
  "dela Cruz" style middle characters still work).
*/
document.querySelectorAll('.name-field').forEach(function (input) {

    input.addEventListener('keydown', function (e) {

        // Only intercept plain single-letter keys (ignore Ctrl/Cmd combos, arrows, etc.)
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const isLowercaseLetter = /^[a-z]$/.test(e.key);

        if (!isLowercaseLetter) return;

        const atStart = input.selectionStart === 0 && input.selectionEnd === 0;
        const fieldIsEmpty = input.value.length === 0;

        // Block a lowercase letter only when it would become the very first character
        if (atStart && fieldIsEmpty) {
            e.preventDefault();
        }

    });

    input.addEventListener('paste', function (e) {

        const pasted = (e.clipboardData || window.clipboardData).getData('text');

        if (pasted.length === 0) return;

        const atStart = input.selectionStart === 0 && input.selectionEnd === 0;
        const fieldIsEmpty = input.value.length === 0;

        if (atStart && fieldIsEmpty && /^[a-z]/.test(pasted)) {
            e.preventDefault();

            // Auto-capitalize the first letter of the pasted text instead of rejecting it outright
            const fixed = pasted.charAt(0).toUpperCase() + pasted.slice(1);
            input.value = fixed;
        }

    });

});

/*
  Phone Number field:
  - digits only (no letters, symbols, spaces)
  - hard cap of 11 digits (e.g. 09171234567)
*/



/*
  Email field:
  - standard max length of 254 characters (RFC 5321 practical limit),
    enforced both via the maxlength attribute and here as a backup
    in case maxlength is ever removed or bypassed.
*/
const emailInput = document.getElementById('email');
const EMAIL_MAX_LENGTH = 254;

emailInput.addEventListener('input', function () {

    if (emailInput.value.length > EMAIL_MAX_LENGTH) {
        emailInput.value = emailInput.value.slice(0, EMAIL_MAX_LENGTH);
    }

});

const firstNameInput = document.querySelector('input[name="first_name"]');
const lastNameInput = document.querySelector('input[name="last_name"]');
const companyEmailPreview = document.getElementById('company_email_preview');
const existingCompanyEmails = @json(\Modules\HR\Models\Employee::pluck('company_email')->filter()->values());
const companyEmailDomain = @json($companyEmailDomain ?? 'company-nexora.mail');

function buildCompanyEmail(firstName, lastName) {
    const first = (firstName || '').replace(/\s+/g, '').toLowerCase();
    const last = (lastName || '').replace(/\s+/g, '').toLowerCase();
    if (!first || !last) return '';

    const base = first + last;
    let email = base + '@' + companyEmailDomain;
    if (!existingCompanyEmails.includes(email)) return email;

    let suffix = 2;
    while (existingCompanyEmails.includes(base + suffix + '@' + companyEmailDomain)) {
        suffix++;
    }
    return base + suffix + '@' + companyEmailDomain;
}

function updateCompanyEmailPreview() {
    if (!companyEmailPreview) return;
    companyEmailPreview.value = buildCompanyEmail(firstNameInput?.value, lastNameInput?.value);
}

firstNameInput?.addEventListener('input', updateCompanyEmailPreview);
lastNameInput?.addEventListener('input', updateCompanyEmailPreview);
updateCompanyEmailPreview();


const nationalities = [
    { name: "Filipino", code: "ph", dial: "+63" },
    { name: "American", code: "us", dial: "+1" },
    { name: "British", code: "gb", dial: "+44" },
    { name: "Canadian", code: "ca", dial: "+1" },
    { name: "Australian", code: "au", dial: "+61" },
    { name: "Japanese", code: "jp", dial: "+81" },
    { name: "Chinese", code: "cn", dial: "+86" },
    { name: "South Korean", code: "kr", dial: "+82" },
    { name: "Indian", code: "in", dial: "+91" },
    { name: "Indonesian", code: "id", dial: "+62" },
    { name: "Malaysian", code: "my", dial: "+60" },
    { name: "Singaporean", code: "sg", dial: "+65" },
    { name: "Thai", code: "th", dial: "+66" },
    { name: "Vietnamese", code: "vn", dial: "+84" },
    { name: "Cambodian", code: "kh", dial: "+855" },
    { name: "Lao", code: "la", dial: "+856" },
    { name: "Burmese", code: "mm", dial: "+95" },
    { name: "Bruneian", code: "bn", dial: "+673" },
    { name: "Timorese", code: "tl", dial: "+670" },
    { name: "Bangladeshi", code: "bd", dial: "+880" },
    { name: "Pakistani", code: "pk", dial: "+92" },
    { name: "Sri Lankan", code: "lk", dial: "+94" },
    { name: "Nepalese", code: "np", dial: "+977" },
    { name: "German", code: "de", dial: "+49" },
    { name: "French", code: "fr", dial: "+33" },
    { name: "Spanish", code: "es", dial: "+34" },
    { name: "Italian", code: "it", dial: "+39" },
    { name: "Dutch", code: "nl", dial: "+31" },
    { name: "Belgian", code: "be", dial: "+32" },
    { name: "Swiss", code: "ch", dial: "+41" },
    { name: "Austrian", code: "at", dial: "+43" },
    { name: "Swedish", code: "se", dial: "+46" },
    { name: "Norwegian", code: "no", dial: "+47" },
    { name: "Danish", code: "dk", dial: "+45" },
    { name: "Finnish", code: "fi", dial: "+358" },
    { name: "Polish", code: "pl", dial: "+48" },
    { name: "Portuguese", code: "pt", dial: "+351" },
    { name: "Greek", code: "gr", dial: "+30" },
    { name: "Irish", code: "ie", dial: "+353" },
    { name: "Russian", code: "ru", dial: "+7" },
    { name: "Ukrainian", code: "ua", dial: "+380" },
    { name: "Turkish", code: "tr", dial: "+90" },
    { name: "Mexican", code: "mx", dial: "+52" },
    { name: "Brazilian", code: "br", dial: "+55" },
    { name: "Argentine", code: "ar", dial: "+54" },
    { name: "Chilean", code: "cl", dial: "+56" },
    { name: "Colombian", code: "co", dial: "+57" },
    { name: "Peruvian", code: "pe", dial: "+51" },
    { name: "Emirati", code: "ae", dial: "+971" },
    { name: "Saudi Arabian", code: "sa", dial: "+966" },
    { name: "Qatari", code: "qa", dial: "+974" },
    { name: "Kuwaiti", code: "kw", dial: "+965" },
    { name: "Israeli", code: "il", dial: "+972" },
    { name: "Egyptian", code: "eg", dial: "+20" },
    { name: "South African", code: "za", dial: "+27" },
    { name: "Nigerian", code: "ng", dial: "+234" },
    { name: "Kenyan", code: "ke", dial: "+254" },
    { name: "New Zealander", code: "nz", dial: "+64" },
];

const nationalityTrigger = document.getElementById('nationality_trigger');
const nationalityDisplay = document.getElementById('nationality_display');
const nationalityDropdown = document.getElementById('nationality_dropdown');
const nationalityValue = document.getElementById('nationality_value');
const initialNationality = @json(old('nationality', $step1Data['nationality'] ?? ''));

function renderNationalityOptions() {
    nationalityDropdown.innerHTML = nationalities.map(n => `
        <div
            class="nationality-option px-3 py-2 text-sm text-white hover:bg-blue-600/30 cursor-pointer flex items-center gap-2"
            data-name="${n.name}"
            data-code="${n.code}"
        >
            <span class="fi fi-${n.code}"></span>
            <span>${n.name}</span>
        </div>
    `).join('');

    document.querySelectorAll('.nationality-option').forEach(option => {
        option.addEventListener('click', function () {
            const name = this.dataset.name;
            const code = this.dataset.code;
            const dial = nationalities.find(n => n.name === name)?.dial || '+--';

            nationalityDisplay.innerHTML = `
                <span class="fi fi-${code}"></span>
                <span class="text-white">${name}</span>
            `;
            nationalityValue.value = name;
            nationalityDropdown.classList.add('hidden');

            document.getElementById('phone_code_text').textContent = dial;
            document.getElementById('phone_code_text').classList.remove('text-slate-300');
            document.getElementById('phone_code_text').classList.add('text-white');

            phoneInput.value = '';
            phoneInput.setAttribute('maxlength', currentPhoneMaxLength());

            const max = currentPhoneMaxLength();
            if (phoneInput.value.length > max) {
                phoneInput.value = phoneInput.value.slice(0, max);
            }
            phoneInput.setAttribute('maxlength', max);
        });
    });
}

nationalityTrigger.addEventListener('click', function (e) {
    e.stopPropagation();
    nationalityDropdown.classList.toggle('hidden');
});

document.addEventListener('click', function (e) {
    if (!e.target.closest('#nationality_trigger') && !e.target.closest('#nationality_dropdown')) {
        nationalityDropdown.classList.add('hidden');
    }
});

renderNationalityOptions();

if (initialNationality) {
    const selectedNationality = nationalities.find(n => n.name === initialNationality);
    if (selectedNationality) {
        nationalityDisplay.innerHTML = `
            <span class="fi fi-${selectedNationality.code}"></span>
            <span class="text-white">${selectedNationality.name}</span>
        `;
        nationalityValue.value = selectedNationality.name;
        document.getElementById('phone_code_text').textContent = selectedNationality.dial;
        document.getElementById('phone_code_text').classList.remove('text-slate-300');
        document.getElementById('phone_code_text').classList.add('text-white');
    }
}

// Approximate max local-number digit lengths (excluding country/dial code) per country.
// These are practical UI limits, not strict telecom validation.
const phoneDigitLimits = {
    ph: 10, us: 10, gb: 10, ca: 10, au: 9, jp: 10, cn: 11, kr: 10,
    in: 10, id: 12, my: 10, sg: 8, th: 9, vn: 10, kh: 9, la: 10,
    mm: 9, bn: 7, tl: 8, bd: 10, pk: 10, lk: 9, np: 10, de: 11,
    fr: 9, es: 9, it: 10, nl: 9, be: 9, ch: 9, at: 11, se: 9,
    no: 8, dk: 8, fi: 10, pl: 9, pt: 9, gr: 10, ie: 9, ru: 10,
    ua: 9, tr: 10, mx: 10, br: 11, ar: 10, cl: 9, co: 10, pe: 9,
    ae: 9, sa: 9, qa: 8, kw: 8, il: 9, eg: 10, za: 9, ng: 10,
    ke: 9, nz: 9
};

const DEFAULT_PHONE_MAX = 11; // used before a nationality is chosen
const phoneInput = document.getElementById('phone');

function currentPhoneMaxLength() {
    const code = nationalityValue.value
        ? nationalities.find(n => n.name === nationalityValue.value)?.code
        : null;
    return (code && phoneDigitLimits[code]) || DEFAULT_PHONE_MAX;
}

// Digits-only + dynamic max length enforcement
phoneInput.addEventListener('input', function () {
    const max = currentPhoneMaxLength();

    // strip non-digits
    let digitsOnly = phoneInput.value.replace(/\D/g, '');

    // enforce current country's cap
    if (digitsOnly.length > max) {
        digitsOnly = digitsOnly.slice(0, max);
    }

    phoneInput.value = digitsOnly;
});

</script>

</body>
</html>
