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
  
    @include('partials.onboarding-stepper', ['currentStep' => 4])

<!-- Final Step Content -->
<div class="flex-1">

    <h2 class="text-white text-sm font-bold tracking-wide mb-6">
        EMPLOYEE INFORMATION
    </h2>

    <form class="space-y-6">

        <!-- Employee ID -->
        <div>
            <label class="block text-slate-300 text-xs mb-1">
                Employee ID
            </label>

            <div class="relative w-[650px]">
                <input
                    id="employee_id_field"
                    type="text"
                    value="{{ $employee['employee_id'] }}"
                    readonly
                    class="w-full h-[45px] bg-[#132B52] text-white text-sm rounded px-4 pr-12 border border-blue-500/30 cursor-not-allowed"
                />

                <button
                    type="button"
                    onclick="copyField('employee_id_field', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Email & Password Row -->
        <div class="flex gap-6">

            <!-- Employee Email -->
            <div>
                <label class="block text-slate-300 text-xs mb-1">
                    Employee Email
                </label>

                <div class="relative w-[650px]">
                    <input
                        id="employee_email_field"
                        type="text"
                        value="{{ $employee['company_email'] }}"
                        readonly
                        class="w-full h-[45px] bg-[#132B52] text-white text-sm rounded px-4 pr-12 border border-blue-500/30 cursor-not-allowed"
                    />

                    <button
                        type="button"
                        onclick="copyField('employee_email_field', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Temporary Password -->
            <div>
                <label class="block text-slate-300 text-xs mb-1">
                    Temporary Password
                </label>

                <div class="relative w-[650px]">
                    <input
                        id="employee_password_field"
                        type="text"
                        value="{{ $employee['temporary_password'] }}"
                        readonly
                        class="w-full h-[45px] bg-[#132B52] text-white text-sm rounded px-4 pr-12 border border-blue-500/30 cursor-not-allowed"
                    />

                    <button
                        type="button"
                        onclick="copyField('employee_password_field', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <!-- Dashboard Button -->
        <div class="pt-8">
            <a 
href="{{ route('hr.dashboard') }}"
class="inline-flex items-center bg-[#0048FF80] hover:bg-[#0048FF] text-white  px-8 py-3 rounded shadow-lg shadow-blue-900/40 transition">

    DASHBOARD

</a>
        </div>

    </form>

</div>

     

    </div>
  </div>
  


    </div>

   </div>

<script>
function copyField(inputId, btn) {
    const input = document.getElementById(inputId);

    input.removeAttribute('readonly'); // some browsers block copy on readonly inputs
    input.select();
    input.setSelectionRange(0, 99999); // for mobile

    let copied = false;

    // Try modern Clipboard API first
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value)
            .then(() => showCopiedFeedback(btn))
            .catch(() => fallbackCopy(input, btn));
    } else {
        fallbackCopy(input, btn);
    }

    input.setAttribute('readonly', true); // restore readonly
}

function fallbackCopy(input, btn) {
    try {
        const success = document.execCommand('copy');
        if (success) {
            showCopiedFeedback(btn);
        } else {
            alert('Copy failed. Please copy manually: ' + input.value);
        }
    } catch (err) {
        alert('Copy failed. Please copy manually: ' + input.value);
    }
}

function showCopiedFeedback(btn) {
    const originalSVG = btn.innerHTML;

    btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    `;

    setTimeout(() => {
        btn.innerHTML = originalSVG;
    }, 1200);
}
</script>

</body>
</html>
