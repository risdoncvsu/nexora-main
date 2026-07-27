<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Employee Onboarding</title>

<!-- Google Font: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
  }
</script>
</head>

 @include('partials.navbar')
 
<body class="bg-[#1B3A6B] min-h-screen font-sans">

<h1 class="text-white pt-[20px] pl-[100px] text-[28px] font-bold tracking-wide mb-8 text-left">EMPLOYEE ONBOARDING</h1>


  <div class="pt-[24px]">
    <!-- Employee Onboarding Content -->

<div class="max-w-7xl mx-auto">

   <!-- Title -->
    @include('partials.onboarding-stepper', ['currentStep' => 3])

<div class="flex flex-col lg:flex-row gap-12">

      <!-- Left: form -->
      <div class="flex-1">
        <h2 class="text-white text-sm font-bold tracking-wide mb-6">
    REQUIRED DOCUMENTS
</h2>

@if ($errors->any())
    <div class="mb-4 rounded bg-red-500/20 border border-red-400 text-red-200 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    action="{{ route('hr.onboarding.storeStep3') }}"
    method="POST"
    enctype="multipart/form-data"
    class="space-y-6">

    @csrf

    <!-- Birth Certificate -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Birth Certificate <span class="text-red-400">*</span>
        </label>

        <div class="relative">
            <input type="file" name="birth_certificate" id="birth_certificate"
                accept=".pdf,.jpg,.jpeg,.png"
                class="file-input hidden"
                onchange="updateFileLabel(this, 'birth_certificate_label')"
            />
            <label for="birth_certificate"
                class="w-[1282px] h-[45px] bg-[#0D1730] text-white text-sm rounded px-3 flex items-center justify-between cursor-pointer">
                <span id="birth_certificate_label" class="truncate text-slate-400">Choose file</span>
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5l5 5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
            </label>
        </div>
        @error('birth_certificate')
            <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <!-- Curriculum Vitae -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Curriculum Vitae <span class="text-red-400">*</span>
        </label>

        <div class="relative">
            <input type="file" name="curriculum_vitae" id="curriculum_vitae"
                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                class="file-input hidden"
                onchange="updateFileLabel(this, 'curriculum_vitae_label')"
            />
            <label for="curriculum_vitae"
                class="w-[1282px] h-[45px] bg-[#0D1730] text-white text-sm rounded px-3 flex items-center justify-between cursor-pointer">
                <span id="curriculum_vitae_label" class="truncate text-slate-400">Choose file</span>
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5l5 5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
            </label>
        </div>
        @error('curriculum_vitae')
            <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <!-- Valid ID -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Valid ID <span class="text-red-400">*</span>
        </label>

        <div class="relative">
            <input type="file" name="valid_id" id="valid_id"
                accept=".pdf,.jpg,.jpeg,.png"
                class="file-input hidden"
                onchange="updateFileLabel(this, 'valid_id_label')"
            />
            <label for="valid_id"
                class="w-[1282px] h-[45px] bg-[#0D1730] text-white text-sm rounded px-3 flex items-center justify-between cursor-pointer">
                <span id="valid_id_label" class="truncate text-slate-400">Choose file</span>
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5l5 5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
            </label>
        </div>
        @error('valid_id')
            <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
        @enderror
    </div>


<script>
function updateFileLabel(input, labelId) {
    const labelEl = document.getElementById(labelId);
    if (!labelEl) return;

    if (input.files && input.files.length > 0) {
        labelEl.textContent = input.files[0].name;
        labelEl.classList.remove('text-slate-400');
        labelEl.classList.add('text-white');
    } else {
        labelEl.textContent = 'Choose file';
        labelEl.classList.remove('text-white');
        labelEl.classList.add('text-slate-400');
    }
}
</script>

    <!-- Navigation Buttons -->
     <div class="pt-6 flex gap-4">
    <!-- Back Button -->
    <!-- Back Button -->
   <div class="pt-8">
           <button
    type="button"
    onclick="window.location.href='{{ route('hr.onboarding.step2') }}'"
    class="w-[218px] h-[56px] border-0 border-[0.1px] border-[#dcdcdc54] rounded-md bg-[#C3326720] text-white text-[0.9375rem] font-normal tracking-[.3px] cursor-pointer shadow-[0_8px_20px_rgba(0,0,0,.25)] transition-all duration-250 hover:bg-[#C3326740] hover:-translate-y-0.5 active:scale-[.97]"
>
    BACK
</button>
          </div>

   <div class="pt-8">
           <button
    type="submit"
     class="w-[218px] h-[56px] border-0 border-[0.1px] border-[#dcdcdc54] rounded-md bg-[#0061FF20] text-white text-[0.9375rem] font-normal tracking-[.3px] cursor-pointer shadow-[0_8px_20px_rgba(0,0,0,.25)] transition-all duration-250 hover:bg-[#0061FF30] hover:-translate-y-0.5 active:scale-[.97]">
    NEXT

  
</button>
          </div>

</form>
      </div>

     

    </div>
  </div>
  


    </div>

   </div>

</body>
</html>

