<div class="max-w-4xl mx-auto py-4 md:py-8 px-2 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-2xl overflow-hidden transition-all duration-500 hover:shadow-green-100 border border-gray-100">
        <!-- Header Banner Section -->
        <div class="relative h-32 md:h-48 bg-gradient-to-br from-green-500 via-green-600 to-green-800">
            <div class="absolute inset-0 opacity-20">
                <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 100 100">
                    <path d="M0 0 L100 0 L100 100 L0 100 Z" fill="url(#grid)"></path>
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"></path>
                        </pattern>
                    </defs>
                </svg>
            </div>
            
            <div class="absolute -bottom-12 md:-bottom-16 left-0 right-0 px-4 md:px-8 flex flex-col md:flex-row items-center md:items-end md:space-x-6">
                <!-- Avatar -->
                <div class="h-24 w-24 md:h-32 md:w-32 bg-white rounded-2xl p-1 shadow-lg transform transition hover:rotate-2">
                    <div id="profileAvatar" class="h-full w-full bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center text-3xl md:text-4xl font-black text-green-700 uppercase">
                        <!-- Initials will be injected here -->
                    </div>
                </div>
                <!-- Profile Identity -->
                <div class="mt-4 md:mt-0 md:mb-4 md:pb-2 text-center md:text-left">
                    <h2 id="displayFullName" class="text-2xl md:text-3xl font-extrabold text-gray-800 md:text-white drop-shadow-none md:drop-shadow-md">Loading...</h2>
                    <p class="flex items-center justify-center md:justify-start text-gray-500 md:text-green-50 font-medium opacity-90">
                        <span class="bg-green-100 md:bg-white/20 text-green-700 md:text-inherit px-2 py-0.5 rounded text-xs md:text-sm mr-2">Student</span>
                        <span id="displaySchoolId" class="tracking-wider text-xs md:text-sm">00-0000-00000</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="pt-24 md:pt-20 pb-6 md:pb-10 px-4 md:px-8 mt-12 md:mt-0">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12">
                <!-- Sidebar Info -->
                <div class="lg:col-span-1 space-y-6 md:space-y-8">
                    <div class="bg-gray-50 rounded-2xl p-5 md:p-6 space-y-4 border border-gray-100">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Account Details</h4>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="mt-1 flex-shrink-0 h-8 w-8 bg-white rounded-lg shadow-sm flex items-center justify-center text-green-600">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-400 font-medium">Email Address</p>
                                    <p id="displayEmail" class="text-sm font-semibold text-gray-700 truncate">loading...</p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="mt-1 flex-shrink-0 h-8 w-8 bg-white rounded-lg shadow-sm flex items-center justify-center text-green-600">
                                    <i class="fas fa-users-viewfinder"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-400 font-medium">Section</p>
                                    <p id="displaySection" class="text-sm font-semibold text-gray-700">loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Forms -->
                <div class="lg:col-span-2 space-y-10 md:space-y-12">
                    <!-- Update Name Section -->
                    <section>
                        <div class="flex items-center justify-between mb-4 md:mb-6">
                            <h3 class="text-lg md:text-xl font-bold text-gray-800 flex items-center">
                                <span class="h-1.5 w-6 md:w-8 bg-green-500 rounded-full mr-3"></span>
                                Personal Information
                            </h3>
                        </div>
                        
                        <form id="profileUpdateNameForm" class="space-y-5 md:space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                                <div class="space-y-1">
                                    <label class="text-xs md:text-sm font-bold text-gray-500 ml-1">First Name</label>
                                    <input type="text" id="inputFirstName" name="firstName" 
                                           class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 focus:ring-2 focus:ring-green-500 transition-all placeholder-gray-300" 
                                           placeholder="Enter your first name" required>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs md:text-sm font-bold text-gray-500 ml-1">Last Name</label>
                                    <input type="text" id="inputLastName" name="lastName" 
                                           class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 focus:ring-2 focus:ring-green-500 transition-all placeholder-gray-300" 
                                           placeholder="Enter your last name" required>
                                </div>
                                <div class="sm:col-span-2 space-y-1">
                                    <label class="text-xs md:text-sm font-bold text-gray-500 ml-1">Middle Name (Optional)</label>
                                    <input type="text" id="inputMiddleName" name="middleName" 
                                           class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-gray-700 focus:ring-2 focus:ring-green-500 transition-all placeholder-gray-300" 
                                           placeholder="Enter your middle name">
                                </div>
                            </div>
                            <div class="flex justify-center md:justify-end">
                                <button type="submit" id="btnUpdateName" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-green-200 transition-all transform hover:-translate-y-1 active:scale-95">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </section>

                    <!-- Security Section -->
                    <section class="pt-6 md:pt-8 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-4 md:mb-6">
                            <h3 class="text-lg md:text-xl font-bold text-gray-800 flex items-center">
                                <span class="h-1.5 w-6 md:w-8 bg-blue-500 rounded-full mr-3"></span>
                                Account Security
                            </h3>
                        </div>

                        <div id="passwordManager" class="bg-gray-50 rounded-2xl p-6 md:p-8 border border-gray-100">
                            <!-- Step 1: Request OTP -->
                            <div id="stepRequestOtp" class="text-center md:text-left">
                                <p class="text-sm md:text-base text-gray-500 mb-6">Want to change your password? We'll send a 6-digit verification code to your registered email address to ensure it's really you.</p>
                                <button type="button" onclick="handleRequestOtp()" id="btnRequestOtp" 
                                        class="w-full md:w-auto inline-flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-1 active:scale-95">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    <span>Change Password</span>
                                </button>
                            </div>

                            <!-- Step 2: Verify OTP and New Password -->
                            <div id="stepVerifyOtp" class="hidden space-y-5 md:space-y-6">
                                <div class="flex items-center space-x-3 text-blue-600 bg-blue-50 p-4 rounded-xl border border-blue-100">
                                    <i class="fas fa-info-circle text-lg md:text-xl"></i>
                                    <p class="text-xs md:text-sm font-medium">Verification code sent! Please check your inbox.</p>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="space-y-1">
                                        <label class="text-xs md:text-sm font-bold text-gray-500 ml-1">New Password</label>
                                        <div class="relative">
                                            <input type="password" id="inputNewPassword" 
                                                   class="w-full bg-white border-none rounded-xl px-4 py-3 text-gray-700 focus:ring-2 focus:ring-blue-500 transition-all shadow-sm" 
                                                   placeholder="Minimum 8 characters">
                                            <button type="button" onclick="togglePasswordVisibility('inputNewPassword')" class="absolute right-4 top-3.5 text-gray-400 hover:text-blue-500">
                                                <i class="fas fa-eye" id="eye-inputNewPassword"></i>
                                            </button>
                                        </div>
                                        <!-- Password Requirements List -->
                                        <div class="mt-4 p-4 bg-white rounded-xl border border-gray-100 space-y-2 shadow-sm">
                                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Password must contain:</p>
                                            <div id="req-length" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-red-500 transition-all duration-300">
                                                <i class="fas fa-times-circle" id="icon-length"></i>
                                                <span>At least 8 characters</span>
                                            </div>
                                            <div id="req-upper" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-red-500 transition-all duration-300">
                                                <i class="fas fa-times-circle" id="icon-upper"></i>
                                                <span>At least 1 uppercase letter</span>
                                            </div>
                                            <div id="req-lower" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-red-500 transition-all duration-300">
                                                <i class="fas fa-times-circle" id="icon-lower"></i>
                                                <span>At least 1 lowercase letter</span>
                                            </div>
                                            <div id="req-number" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-red-500 transition-all duration-300">
                                                <i class="fas fa-times-circle" id="icon-number"></i>
                                                <span>At least 1 number</span>
                                            </div>
                                            <div id="req-special" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-red-500 transition-all duration-300">
                                                <i class="fas fa-times-circle" id="icon-special"></i>
                                                <span>At least 1 special character</span>
                                            </div>
                                            <div id="req-space" class="flex items-center space-x-2 text-[11px] md:text-xs font-semibold text-green-500 transition-all duration-300">
                                                <i class="fas fa-check-circle" id="icon-space"></i>
                                                <span>No spaces allowed</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs md:text-sm font-bold text-gray-500 ml-1">6-Digit Verification Code</label>
                                        <div class="flex space-x-1.5 md:space-x-2 justify-between">
                                            <input type="text" id="otp_1" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                            <input type="text" id="otp_2" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                            <input type="text" id="otp_3" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                            <input type="text" id="otp_4" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                            <input type="text" id="otp_5" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                            <input type="text" id="otp_6" maxlength="1" class="otp-input w-9 h-12 md:w-12 md:h-14 bg-white border-2 border-gray-100 rounded-lg md:rounded-xl text-center text-xl md:text-2xl font-black text-blue-600 focus:border-blue-500 focus:ring-0 transition-all shadow-sm">
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col space-y-3 pt-4">
                                    <button type="button" onclick="handleUpdatePassword()" id="btnUpdatePassword"
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-blue-200 transition-all active:scale-95">
                                        Update Password
                                    </button>
                                    <button type="button" onclick="cancelChangePassword()" 
                                            class="w-full bg-white border-2 border-gray-200 text-gray-600 font-bold py-3 px-6 rounded-xl transition-all hover:bg-gray-100 active:scale-95">
                                        Cancel
                                    </button>
                                </div>
                                <p class="text-center">
                                    <span class="text-xs md:text-sm text-gray-500">Didn't receive the code?</span>
                                    <button type="button" onclick="handleRequestOtp()" class="text-xs md:text-sm text-blue-600 font-bold hover:underline ml-1">Resend code</button>
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-4 md:px-8 py-5 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center space-y-3 md:space-y-0 text-center md:text-left">
            <div class="flex items-center space-x-2">
                <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest">Profile synchronized</span>
            </div>
            <p class="text-[10px] md:text-xs text-gray-400 font-medium font-mono">System Version 3.1.0 • PHINMA Education</p>
        </div>
    </div>
</div>
