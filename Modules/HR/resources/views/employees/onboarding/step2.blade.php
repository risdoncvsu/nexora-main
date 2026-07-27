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

    @include('partials.onboarding-stepper', ['currentStep' => 2])

<div class="flex flex-col lg:flex-row gap-12">

      <!-- Left: form -->
      <div class="flex-1">
        <h2 class="text-white text-sm font-bold tracking-wide mb-4">
    EMPLOYMENT INFORMATION
</h2>

@if ($errors->any())
    <div class="mb-4 rounded bg-amber-500/10 border border-amber-400/40 text-amber-100 px-4 py-3 text-sm">
        Please review the highlighted fields below.
    </div>
@endif

<form 
 action="{{ route('hr.onboarding.storeStep2') }}"
    method="POST"
    class="space-y-6 max-w-3xl" >

    @csrf

    <!-- Top Row -->
    <!-- Top Row -->
<div class="flex gap-6">

    <!-- Department -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Department <span class="text-red-500">*</span>
        </label>
        <select id="department" name="department" required class="w-[630px] h-[40px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">Select Department</option>
            <option value="Business Intelligence" {{ old('department', $step2['department'] ?? '') == 'Business Intelligence' ? 'selected' : '' }}>Business Intelligence</option>
            <option value="E-commerce" {{ old('department', $step2['department'] ?? '') == 'E-commerce' ? 'selected' : '' }}>E-commerce</option>
            <option value="Finance" {{ old('department', $step2['department'] ?? '') == 'Finance' ? 'selected' : '' }}>Finance</option>
            <option value="Human Resources" {{ old('department', $step2['department'] ?? '') == 'Human Resources' ? 'selected' : '' }}>Human Resources</option>
            <option value="IT Service Management" {{ old('department', $step2['department'] ?? '') == 'IT Service Management' ? 'selected' : '' }}>IT Service Management</option>
            <option value="Inventory Management" {{ old('department', $step2['department'] ?? '') == 'Inventory Management' ? 'selected' : '' }}>Inventory Management</option>
            <option value="Order Management" {{ old('department', $step2['department'] ?? '') == 'Order Management' ? 'selected' : '' }}>Order Management</option>
            <option value="Procurement Management" {{ old('department', $step2['department'] ?? '') == 'Procurement Management' ? 'selected' : '' }}>Procurement Management</option>
            <option value="Production Management" {{ old('department', $step2['department'] ?? '') == 'Production Management' ? 'selected' : '' }}>Production Management</option>
        </select>
        @error('department')
            <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <!-- Position -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Position <span class="text-red-500">*</span>
        </label>
        <select id="position" name="position" required class="w-[630px] h-[40px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">Select Department First</option>
        </select>
        @error('position')
            <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
        @enderror
    </div>

</div>

<!-- Bottom Row -->
<div class="flex gap-6">

    <!-- Hire Date -->
    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Hire Date <span class="text-red-500">*</span>
        </label>
        <input
        name="hire_date"
            type="date"
            required
            value="{{ old('hire_date', $step2['hire_date'] ?? '') }}"
            class="w-[412px] h-[40px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500"
        />
        @error('hire_date')
            <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-slate-300 text-xs mb-1">
            Start Time <span class="text-red-500">*</span>
        </label>
        <input
            name="start_time"
            type="time"
            required
            value="{{ old('start_time', $step2['start_time'] ?? '') }}"
            class="w-[412px] h-[40px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500"
        />
        @error('start_time')
            <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-[10px] text-slate-400">HR assigned work start (basis for late check)</p>
    </div>

    <div>
        <label class="block text-slate-300 text-xs mb-1">
            End Time <span class="text-red-500">*</span>
        </label>
        <input
            name="end_time"
            type="time"
            required
            value="{{ old('end_time', $step2['end_time'] ?? '') }}"
            class="w-[412px] h-[40px] bg-[#0d1730] text-white text-sm rounded px-3 outline-none focus:ring-1 focus:ring-blue-500"
        />
        @error('end_time')
            <p class="mt-1 text-[11px] text-red-300">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-[10px] text-slate-400">Required work hours = End Time − Start Time</p>
    </div>


</div>


    

    <!-- Navigation Buttons -->
<div class="pt-6 flex gap-4">

    <!-- Back Button -->
   <div class="pt-8">
           <button
    type="button"
    onclick="window.location.href='{{ route('hr.onboarding.step1') }}'"
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

<script>
  const positionsByDepartment = {
    "Business Intelligence": ["BI Manager", "BI Staff"],

    "E-commerce": ["E-commerce Manager", "E-commerce Staff"],

    "Finance": ["Finance Manager", "Finance Staff"],

    "Human Resources": ["Human Resources Manager", "Human Resources Staff"],

    "IT Service Management": ["IT Service Manager", "IT Service Staff"],

    "Inventory Management": ["Inventory Manager", "Inventory Staff"],

    "Order Management": ["Order Manager", "Order Staff", "Delivery Rider"],

    "Procurement Management": ["Procurement Manager", "Procurement Staff"],

    "Production Management": ["Production Manager", "Production Staff"]
};

  const departmentSelect = document.getElementById("department");
  const positionSelect = document.getElementById("position");

  function populatePositions(selectedDepartment, selectedPosition = "") {
    const positions = positionsByDepartment[selectedDepartment] || [];

    positionSelect.innerHTML = "";

    if (!selectedDepartment) {
      positionSelect.appendChild(new Option("Select Department First", ""));
      return;
    }

    positionSelect.appendChild(new Option("Select Position", ""));
    positions.forEach(function (position) {
      const option = new Option(position, position);
      if (selectedPosition && position === selectedPosition) {
        option.selected = true;
      }
      positionSelect.appendChild(option);
    });
  }

  departmentSelect.addEventListener("change", function () {
    populatePositions(this.value);
  });

  const initialDepartment = @json(old('department', $step2['department'] ?? ''));
  const initialPosition = @json(old('position', $step2['position'] ?? ''));

  if (initialDepartment) {
    departmentSelect.value = initialDepartment;
    populatePositions(initialDepartment, initialPosition);
  }
</script>

</body>
</html>

