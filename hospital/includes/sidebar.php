        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg hidden md:block flex-shrink-0">
            <div class="p-6 border-b">
                <a href="../index.php" class="flex items-center gap-2 text-2xl font-black text-blue-600">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Tech</span>
                </a>
                <p class="text-xs text-gray-500 mt-2">لوحة تحكم المستشفى</p>
            </div>
            
            <nav class="mt-6 px-4 space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span>الرئيسية</span>
                </a>
                
                <a href="doctors.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-user-md w-6"></i>
                    <span>الأطباء</span>
                </a>
                
                <a href="clinics.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'clinics.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-clinic-medical w-6"></i>
                    <span>العيادات</span>
                </a>
                
                <a href="appointments.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-calendar-check w-6"></i>
                    <span>المواعيد</span>
                </a>
                
                <a href="patients.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span>المرضى</span>
                </a>
                
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-blue-50 text-blue-600 font-bold' : ''; ?>">
                    <i class="fas fa-cog w-6"></i>
                    <span>الإعدادات</span>
                </a>
                
                <div class="border-t my-4 pt-4">
                    <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>
        
        <!-- Mobile Sidebar -->
        <aside id="mobileSidebar" class="fixed inset-y-0 right-0 w-64 bg-white shadow-lg transform translate-x-full transition-transform duration-300 ease-in-out z-50 md:hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <span class="text-xl font-bold text-blue-600">القائمة</span>
                <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <!-- Copy of nav items for mobile -->
            <nav class="mt-6 px-4 space-y-2">
                 <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">
                    <i class="fas fa-home w-6"></i>
                    <span>الرئيسية</span>
                </a>
                <!-- ... other links ... -->
                 <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-600 hover:text-blue-600">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                
                <div class="flex items-center gap-4 mr-auto">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'المسؤول'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Main Content Scrollable Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
