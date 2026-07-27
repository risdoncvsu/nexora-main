<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        * { font-family: "Inter", sans-serif; }

        /* Kept custom: things Tailwind utilities can't express cleanly */
        .id-card-front-bg {
            background: linear-gradient(180deg, #0B1E3D 0%, #0d4fde 180%);
        }
        .id-card-position-bg {
            background: linear-gradient(180deg, #0B1E3D 0%, #0D4FDE 180%);
        }
    </style>
</head>

<body class="m-0 p-0 bg-[#132B52] text-white">

    <!-- =====================================================
         TOP NAVBAR
    ====================================================== -->

     @include('partials.employee-navbar')
   
    <!-- =====================================================
         EDIT FORM
    ====================================================== -->
    <div class="w-full max-w-[108.1875rem] min-h-[45.375rem] ml-[90px] mr-10 mt-14 py-7 pr-2.5 pl-[60px] grid grid-cols-[68%_32%] gap-6 bg-[#183668] rounded-[22px] shadow-[inset_5px_10px_18px_rgba(191,0,0,.03),inset_1px_0_1px_rgba(0,0,0,.20),0_18px_35px_rgba(0,0,0,.35)]">

        <form action="#" method="POST" enctype="multipart/form-data" class="contents">

            <div class="grid grid-cols-[70%_30%] gap-[30px] items-start col-span-2">

                <!-- LEFT SIDE -->
                <div class="pl-10">

                    <!-- Profile Image -->
                    <div>
                        <h3 class="text-[13px] font-normal text-[#D7E4FF] tracking-[.5px] mb-[7px] uppercase">PROFILE IMAGE</h3>

                        <div class="relative w-[100px] h-[100px] rounded-full bg-[#7FB3FF] flex justify-center items-center overflow-hidden shadow-[0_4px_12px_rgba(0,0,0,.35)] cursor-not-allowed">
                            @if(!empty($employee->profile_picture))
                                <img id="editProfilePreview"
                                     src="{{ asset('profile_pictures/' . $employee->profile_picture) }}"
                                     alt="Profile"
                                     class="w-full h-full object-cover rounded-full">
                            @else
                                <i id="editProfilePlaceholder"
                                   class="fa-solid fa-circle-user text-[120px] text-[#1C4176]"></i>
                            @endif
                        </div>

                        <input type="file"
                               name="profile_picture"
                               id="edit_profile_picture"
                               accept="image/png, image/jpeg, image/jpg"
                               class="hidden"
                               disabled>

                        <p id="editProfilePictureError" class="text-red-400 text-[10px] mt-1.5 hidden"></p>
                    </div>

                    <div class="flex items-end gap-2 mb-3.5 mt-6">
                        <h3 class="text-[13px] font-light text-white uppercase whitespace-nowrap m-0">Employee Details</h3>

                        <div class="flex gap-3.5 ml-[146px] w-full">
                            <div class="relative w-[210.4px]">
                                <label class="absolute top-[3px] left-4 text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Hire Date</label>
                                <input type="text" disabled value="{{ $employee->hire_date ? \Illuminate\Support\Carbon::parse($employee->hire_date)->format('M d, Y') : '' }}"
                                    class="w-full h-10 box-border py-3 px-2.5 pt-3 pr-[38px] bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none cursor-not-allowed">
                            </div>

                            <div class="relative w-[210.4px]">
                                <label class="absolute top-[3px] left-4 text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Status</label>
                                <input type="text" disabled value="Active"
                                    class="w-full h-10 box-border py-3 px-2.5 pt-3 pr-[38px] bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- NAME ROW -->
                    <div class="flex gap-[15px] mb-[15px]">

                        <div class="relative w-[220px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">First Name</label>
                            <input name="first_name" id="first_name" value="{{ $employee->first_name ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>

                        <div class="relative w-[220px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Middle Name</label>
                            <input name="middle_name" id="middle_name" value="{{ $employee->middle_name ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>

                        <div class="relative w-[220px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Last Name</label>
                            <input name="last_name" id="last_name" value="{{ $employee->last_name ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>

                        <div class="relative w-[120px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Suffix</label>
                            <input type="text" name="suffix" id="suffix" value="{{ $employee->suffix ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed">
                        </div>

                    </div>

                    <!-- Row 2: Department / Position -->
                    <div class="flex gap-[15px] mb-[15px]">

                        <div class="relative w-[406px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Department</label>
                            <input type="text" id="department" name="department" value="{{ $employee->department ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed">
                        </div>

                        <div class="relative w-[406px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Position</label>
                            <input type="text" id="position" name="position" value="{{ $employee->position ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Row 3: Gender / Marital Status / Nationality -->
                    <div class="flex gap-[15px] mb-[15px]">

                        <div class="relative w-[269px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Gender</label>
                            <input type="text" name="gender" value="{{ $employee->gender ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed">
                        </div>

                        <div class="relative w-[269px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Marital Status</label>
                            <input type="text" name="marital_status" value="{{ $employee->marital_status ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed">
                        </div>

                        <div class="relative w-[269px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Nationality</label>
                            <input type="text" name="nationality" id="nationality" value="{{ $employee->nationality ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>

                    </div>

                    <!-- Row 4: Address -->
                    <div class="mb-[15px]">
                        <div class="relative w-[837px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Address</label>
                            <textarea name="address" id="address" rows="1" disabled
                                class="w-full h-10 overflow-hidden box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none resize-none text-center flex items-center cursor-not-allowed">{{ $employee->address ?? '' }}</textarea>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="mb-[15px]">
                        <h3 class="text-[13px] font-light text-white uppercase whitespace-nowrap mb-[15px] mt-[30px]">Contact Details</h3>

                        <div class="relative w-[837px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Email Address</label>
                            <input type="email" name="email" value="{{ $employee->company_email ?? $employee->email ?? '' }}" disabled
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="mb-[15px]">
                        <div class="relative w-[837px] pr-[430px]">
                            <label class="absolute top-[3px] left-[16.5px] text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Phone Number</label>
                            <input type="text" name="phone" id="phone" value="{{ $employee->phone ?? '' }}" disabled
                                maxlength="11" inputmode="numeric" pattern="\d{11}"
                                class="w-full h-10 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[11px] outline-none text-center cursor-not-allowed placeholder:text-[#8FA6D8]">
                        </div>
                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="flex flex-col gap-[15px] mt-[22%] w-[536px] -ml-[50%]">

                    <h3 class="text-[13px] font-light text-white uppercase whitespace-nowrap">Supporting Documents</h3>

                    <div class="relative w-[536px]">
                        <label class="absolute top-[3px] left-2.5 text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">CV</label>
                        <input type="file" name="curriculum_vitae" disabled
                            class="w-[536px] h-8 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-[#8FA6D8] border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[13px] cursor-not-allowed file:hidden">
                    </div>

                    <div class="relative w-[536px]">
                        <label class="absolute top-[3px] left-2.5 text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Birth Certificate</label>
                        <input type="file" name="birth_certificate" disabled
                            class="w-[536px] h-8 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-[#8FA6D8] border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[13px] cursor-not-allowed file:hidden">
                    </div>

                    <div class="relative w-[536px]">
                        <label class="absolute top-[3px] left-2.5 text-[9px] font-semibold text-[#6B7280] pointer-events-none z-[2]">Valid ID</label>
                        <input type="file" name="valid_id" disabled
                            class="w-[536px] h-8 box-border py-3 px-2.5 pt-3 bg-[#0B1E3D] text-[#8FA6D8] border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[13px] cursor-not-allowed file:hidden">
                    </div>

                </div>

            </div>

        </form>

    </div>

    <!-- =====================================================
     DELETE CONFIRMATION MODAL
====================================================== -->
    <div id="deleteModalOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[2000] hidden items-center justify-center">
        <div class="w-[420px] bg-[#132B52] rounded-[18px] shadow-[0_20px_45px_rgba(0,0,0,.45),inset_0_1px_0_rgba(255,255,255,.05)] p-7 relative border border-white/5">

            <!-- Icon -->
            <div class="w-14 h-14 rounded-full bg-[#FF000420] flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-[#FF6B6B]" viewBox="0 0 24 24" fill="none">
                    <path d="M12 9v4M12 17h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h2 class="text-white text-lg font-medium text-center mb-2">Delete Employee?</h2>

            <p class="text-[#C9DAF8] text-[13px] text-center leading-relaxed mb-1">
                Are you sure you want to delete
            </p>
            <p class="text-white text-[15px] font-semibold text-center mb-1">
                EMPLOYEE NAME
            </p>
            <p class="text-[#8FA6D8] text-[11px] text-center mb-5">
                Employee ID: 20260000
            </p>

            <p class="text-[#8FA6D8] text-[11px] text-center mb-4 leading-relaxed">
                This action cannot be undone. Type <span class="text-[#FF6B6B] font-semibold tracking-wide">DELETE</span> below to confirm.
            </p>

            <input type="text" id="deleteConfirmInput" autocomplete="off"
                placeholder="Type DELETE to confirm"
                class="w-full h-11 box-border py-3 px-3.5 bg-[#0B1E3D] text-white border-0 shadow-[0_4px_8px_rgba(0,0,0,.35)] rounded-[10px] text-[13px] outline-none text-center tracking-[2px] uppercase placeholder:text-[#8FA6D8] placeholder:tracking-normal placeholder:normal-case focus:shadow-[0_0_0_2px_rgba(255,107,107,.35)] mb-5">

            <div class="flex gap-3">
                <button type="button" id="cancelDeleteBtn"
                    class="flex-1 h-11 border border-[#5D8CFF] rounded-[10px] text-[13px] font-light text-white bg-[#0048FF20] hover:bg-[#0048FF50] transition-all duration-250 cursor-pointer">
                    CANCEL
                </button>
                <button type="button" id="confirmDeleteBtn" disabled
                    class="flex-1 h-11 border border-[#5D8CFF] rounded-[10px] text-[13px] font-light text-white bg-[#FF000420] opacity-40 cursor-not-allowed transition-all duration-250">
                    DELETE
                </button>
            </div>
        </div>
    </div>

    <script>
        const deleteForm      = document.getElementById("deleteForm");
        const deleteModal     = document.getElementById("deleteModalOverlay");
        const deleteInput     = document.getElementById("deleteConfirmInput");
        const confirmDeleteBtn= document.getElementById("confirmDeleteBtn");
        const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");

        if (deleteForm) {
            deleteForm.addEventListener("submit", function (e) {
                e.preventDefault();
                openDeleteModal();
            });
        }

        function openDeleteModal() {
            deleteInput.value = "";
            toggleConfirmBtn();
            deleteModal.classList.remove("hidden");
            deleteModal.classList.add("flex");
            setTimeout(() => deleteInput.focus(), 50);
        }

        function closeDeleteModal() {
            deleteModal.classList.add("hidden");
            deleteModal.classList.remove("flex");
        }

        function toggleConfirmBtn() {
            const isMatch = deleteInput.value.trim() === "DELETE";
            confirmDeleteBtn.disabled = !isMatch;
            confirmDeleteBtn.classList.toggle("opacity-40", !isMatch);
            confirmDeleteBtn.classList.toggle("cursor-not-allowed", !isMatch);
            confirmDeleteBtn.classList.toggle("bg-[#C0392B95]", isMatch);
            confirmDeleteBtn.classList.toggle("cursor-pointer", isMatch);
        }

        deleteInput.addEventListener("input", function () {
            this.value = this.value.toUpperCase();
            toggleConfirmBtn();
        });

        deleteInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !confirmDeleteBtn.disabled) {
                deleteForm.submit();
            }
        });

        cancelDeleteBtn.addEventListener("click", closeDeleteModal);

        deleteModal.addEventListener("click", function (e) {
            if (e.target === deleteModal) closeDeleteModal();
        });

        confirmDeleteBtn.addEventListener("click", function () {
            if (deleteInput.value.trim() === "DELETE") {
                deleteForm.submit();
            }
        });
    </script>

</body>
</html>
