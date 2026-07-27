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

<div class="flex flex-col lg:flex-row gap-12">

    
      <!-- Left: form -->
      <div class="flex-1">
        <!-- Step 4 Content -->


 <form 
action="{{ route('hr.onboarding.storeStep4') }}" 
method="POST"
> @csrf

    @if ($errors->any())
        <div class="bg-red-500/20 border border-red-500 text-red-100 rounded p-4 mb-6">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 ml-[1%] w-[700px] bg-[none] rounded-xl  py-5 ">
       
        <p class="text-white text-sm font-semibold">POLICY ACKNOWLEDGEMENT AND COMPLIANCE</p>
        <p class="mt-1 text-[14px] font-thin text-white opacity-80">By checking this box, the employee confirms that they have read and understood the physical copy of the company's policies and agreements, agree to all terms and conditions, and authorize the Human Resources Department to record this acknowledgment in the system on their behalf.</p>
    </div>

<div class="w-[1300px] bg-[#0B1E3D] rounded-xl px-6 py-8 space-y-8">

<div class="pb-6">
 <p class="text-slate-300 text-sm leading-7 mb-4 text-center">As part of the onboarding process, I acknowledge that I have read, understood, and agree to comply with the following company policies. I understand that failure to follow these policies may result in disciplinary action in accordance with company procedures.</p>
  <br>  <!-- Policy 1 -->
    <div class=" pb-6">
        <h3 class="text-white font-semibold mb-3">
            Employee Handbook Acknowledgment
        </h3>

        <p class="text-slate-300 text-sm leading-7 mb-4">
            As part of my employment with the Company, I acknowledge that I have received, reviewed, and understood the contents of the Employee Handbook. I understand that the handbook contains important information regarding the Company's policies, procedures, standards of conduct, workplace expectations, employee benefits, and other employment-related guidelines. I agree to familiarize myself with these policies and to comply with all applicable rules and regulations throughout the duration of my employment. I understand that the Company may amend, modify, or update its policies from time to time, and I acknowledge my responsibility to remain informed of any such changes. I further understand that failure to comply with the provisions outlined in the Employee Handbook may result in corrective or disciplinary action, up to and including termination of employment, in accordance with Company policies and applicable laws.
        </p>

        
    </div>

    <!-- Policy 2 -->
    <div class=" pb-6">
        <h3 class="text-white font-semibold mb-3">Code of Conduct Sign-Off</h3>

        <p class="text-slate-300 text-sm leading-7 mb-4">
I acknowledge that I have read, understood, and agreed to comply with the Company's Code of Conduct. I understand that the Code of Conduct serves as a guide for ethical behavior, professional responsibility, workplace integrity, and respectful interactions with colleagues, clients, business partners, and other stakeholders. I agree to perform my duties honestly, responsibly, and in a manner that reflects the values and reputation of the Company. I understand that I am expected to avoid conflicts of interest, protect Company assets, maintain professionalism in all work-related activities, and adhere to all applicable laws and regulations. I further acknowledge that violations of the Code of Conduct may result in disciplinary measures, including suspension or termination of employment, depending on the nature and severity of the violation.</p>
        
    </div>

    <!-- Policy 3 -->
    <div class="  pb-6">
        <h3 class="text-white font-semibold mb-3">Confidentiality & Non-Disclosure Agreement (NDA)</h3>

        <p class="text-slate-300 text-sm leading-7 mb-4">
I understand that during the course of my employment, I may gain access to confidential, proprietary, sensitive, or non-public information belonging to the Company, its employees, clients, customers, suppliers, business partners, or affiliates. Such information may include, but is not limited to, business strategies, financial records, customer data, trade secrets, operational procedures, intellectual property, technological resources, and other confidential materials. I agree to maintain the confidentiality of all such information and to use it solely for legitimate business purposes related to my assigned duties. I further agree not to disclose, reproduce, distribute, or misuse confidential information without proper authorization from the Company. This obligation shall remain in effect during my employment and, where applicable, after the termination of my employment. I understand that unauthorized disclosure or misuse of confidential information may result in disciplinary action, legal liability, and other remedies available under applicable laws and Company policies.        </p>

       
    </div>

    <!-- Policy 4 -->
    <div class=" pb-6">
        <h3 class="text-white font-semibold mb-3">Health & Safety Policy Acknowledgment</h3>

        <p class="text-slate-300 text-sm leading-7 mb-4">
I acknowledge the Company's commitment to maintaining a safe, healthy, and secure working environment for all employees, contractors, visitors, and stakeholders. I agree to comply with all workplace health and safety policies, procedures, standards, and emergency protocols established by the Company. I understand that workplace safety is a shared responsibility and that I am expected to perform my duties in a manner that minimizes risks to myself and others. I agree to use equipment properly, follow safety instructions, participate in required safety trainings, and immediately report any accidents, injuries, hazards, unsafe conditions, or potential risks to the appropriate personnel. I understand that failure to comply with safety requirements may place others at risk and may result in disciplinary action. I further acknowledge my responsibility to contribute to a culture of safety, accountability, and continuous improvement within the workplace.        </p>

        
    </div>

    <!-- Policy 5 -->
    <div class=" pb-6">
        <h3 class="text-white font-semibold mb-3">Anti-Harassment Policy Sign-Off</h3>

        <p class="text-slate-300 text-sm leading-7 mb-4">
I acknowledge and support the Company's commitment to fostering a professional, inclusive, and respectful workplace that is free from harassment, discrimination, bullying, intimidation, retaliation, and any other form of inappropriate conduct. I understand that all employees are entitled to work in an environment where they are treated with dignity, fairness, and respect regardless of their position, background, personal characteristics, or beliefs as protected by applicable laws and Company policies. I agree to conduct myself professionally at all times, to respect the rights and dignity of others, and to refrain from engaging in any behavior that may create a hostile, offensive, or intimidating work environment. I further agree to report any incidents of harassment, discrimination, bullying, or retaliation through the appropriate reporting channels provided by the Company. I understand that all reports will be handled seriously and that violations of this policy may result in disciplinary action, including termination of employment, depending on the circumstances and severity of the offense.        </p>

       
    </div>

   
<!-- Agreement Checkbox -->
<div class="mt-8 border-t border-slate-700 pt-6">
    <label class="flex items-start gap-3 cursor-pointer">
        <input
            type="checkbox"
            name="policy_agreement"
            value="1"
            required
            class="mt-1 h-5 w-5 rounded border-slate-400 text-blue-600 focus:ring-blue-500"
        >

        <span class="text-slate-300 text-sm leading-6">
            I hereby acknowledge that I have read, understood, and agree to comply with all Company policies, procedures, guidelines, and agreements stated above. I understand that these policies form part of my employment obligations and that failure to adhere to them may result in disciplinary action in accordance with Company rules and applicable laws. I further authorize the Human Resources Department to record this acknowledgment electronically as part of my employee onboarding records.
        </span>
    </label>
</div>
   
</div>

<!-- Navigation Buttons -->

 



<div class="pt- flex gap-4 flex justify-center">

<div class="pt-3 ">
           <button
    type="button"
    onclick="window.location.href='{{ route('hr.onboarding.step2') }}'"
    class="w-[218px] h-[56px] border-0 border-[0.1px] border-[#dcdcdc54] rounded-md bg-[#C3326720] text-white text-[0.9375rem] font-normal tracking-[.3px] cursor-pointer shadow-[0_8px_20px_rgba(0,0,0,.25)] transition-all duration-250 hover:bg-[#C3326740] hover:-translate-y-0.5 active:scale-[.97]"
>
    BACK
</button>
          </div>

   <div class="pt-3">
           <button
    type="submit"
     class="w-[218px] h-[56px] border-0 border-[0.1px] border-[#dcdcdc54] rounded-md bg-[#66FF6B20] text-white text-[0.9375rem] font-normal tracking-[.3px] cursor-pointer shadow-[0_8px_20px_rgba(0,0,0,.25)] transition-all duration-250 hover:bg-[#66FF6B40] hover:-translate-y-0.5 active:scale-[.97]">
    FINISH

  
</button>
          </div>


</form>
  </div>
  


    </div>

    

   </div>

</body>
</html>

