        <?php
        // Determine base URLs dynamically regardless of current directory depth
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        $script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $hospital_segment = '/hospital';
        $hospital_pos = strpos($script_path, $hospital_segment);

        if ($hospital_pos !== false) {
            $project_path = substr($script_path, 0, $hospital_pos);
            $hospital_path = substr($script_path, 0, $hospital_pos + strlen($hospital_segment));
        } else {
            $project_path = $script_path;
            $hospital_path = $script_path;
        }

        $project_base_url = rtrim("$protocol://$host$project_path", '/');
        $hospital_base_url = rtrim("$protocol://$host$hospital_path", '/');
        $current_page = basename($_SERVER['PHP_SELF']);
        $logout_url = $project_base_url . '/logout.php';
        ?>
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg hidden md:block flex-shrink-0">
            <div class="p-6 border-b">
                <a href="<?php echo $hospital_base_url; ?>/index.php" class="flex items-center gap-2 text-2xl font-black text-blue-600">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Tech</span>
                </a>
                <p class="text-xs text-gray-500 mt-2">لوحة تحكم المستشفى</p>
            </div>

            <nav class="mt-6 px-4 space-y-1">
                <!-- Dashboard -->
                <a href="<?php echo $hospital_base_url; ?>/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'index.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                    <i class="fas fa-home w-6"></i>
                    <span>الرئيسية</span>
                </a>

                <!-- Appointments -->
                <a href="<?php echo $hospital_base_url; ?>/appointments.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'appointments.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                    <i class="fas fa-calendar-check w-6"></i>
                    <span>المواعيد</span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-auto">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE c.hospital_id = ? AND a.status = 'pending'");
                        $stmt->execute([$hospital_id]);
                        echo $stmt->fetchColumn();
                        ?>
                    </span>
                </a>

                <!-- Doctors Section -->
                <div class="mt-4">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">الأطباء</p>
                    <a href="<?php echo $hospital_base_url; ?>/doctors.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'doctors.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-list w-6"></i>
                        <span>قائمة الأطباء</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/doctor_form.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'doctor_form.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-plus-circle w-6"></i>
                        <span>إضافة طبيب جديد</span>
                    </a>
                </div>

                <!-- Clinics Section -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">العيادات</p>
                    <a href="<?php echo $hospital_base_url; ?>/clinics.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'clinics.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-list w-6"></i>
                        <span>قائمة العيادات</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/clinic_form.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'clinic_form.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-plus-circle w-6"></i>
                        <span>إضافة عيادة جديدة</span>
                    </a>
                </div>

                <!-- Patients -->
                <a href="<?php echo $hospital_base_url; ?>/patients.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'patients.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                    <i class="fas fa-users w-6"></i>
                    <span>المرضى</span>
                </a>

                <!-- Reports -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">التقارير</p>
                    <a href="<?php echo $hospital_base_url; ?>/reports/appointments_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'appointments_report.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>تقرير المواعيد</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/reports/patients_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'patients_report.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-users w-6"></i>
                        <span>تقرير المرضى</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/reports/financial_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'financial_report.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-money-bill-wave w-6"></i>
                        <span>التقارير المالية</span>
                    </a>
                </div>

                <!-- Settings -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">الإعدادات</p>
                    <a href="<?php echo $hospital_base_url; ?>/settings.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors <?php echo $current_page == 'settings.php' ? 'bg-blue-50 text-blue-600 font-medium' : ''; ?>">
                        <i class="fas fa-cog w-6"></i>
                        <span>إعدادات الحساب</span>
                    </a>
                </div>
                <!-- Logout -->
                <div class="border-t my-4 pt-4">
                    <a href="<?php echo $logout_url; ?>" class="flex items-center gap-3 px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

        <!-- Mobile Sidebar -->
        <aside id="mobileSidebar" class="fixed inset-y-0 right-0 w-72 bg-white shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 md:hidden overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <a href="<?php echo $hospital_base_url; ?>/index.php" class="flex items-center gap-2 text-xl font-black text-blue-600">
                    <i class="fas fa-heartbeat"></i>
                    <span>Health Tech</span>
                </a>
                <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <nav class="p-4 space-y-1">
                <!-- Dashboard -->
                <a href="<?php echo $hospital_base_url; ?>/index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                    <i class="fas fa-home w-6"></i>
                    <span>الرئيسية</span>
                </a>

                <!-- Appointments -->
                <a href="<?php echo $hospital_base_url; ?>/appointments.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                    <i class="fas fa-calendar-check w-6"></i>
                    <span>المواعيد</span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-auto">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE c.hospital_id = ? AND a.status = 'pending'");
                        $stmt->execute([$hospital_id]);
                        echo $stmt->fetchColumn();
                        ?>
                    </span>
                </a>

                <!-- Doctors Section -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">الأطباء</p>
                    <a href="<?php echo $hospital_base_url; ?>/doctors.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-list w-6"></i>
                        <span>قائمة الأطباء</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/doctor_form.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-plus-circle w-6"></i>
                        <span>إضافة طبيب جديد</span>
                    </a>
                </div>

                <!-- Clinics Section -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">العيادات</p>
                    <a href="<?php echo $hospital_base_url; ?>/clinics.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-list w-6"></i>
                        <span>قائمة العيادات</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/clinic_form.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-plus-circle w-6"></i>
                        <span>إضافة عيادة جديدة</span>
                    </a>
                </div>

                <!-- Patients -->
                <a href="<?php echo $hospital_base_url; ?>/patients.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                    <i class="fas fa-users w-6"></i>
                    <span>المرضى</span>
                </a>

                <!-- Reports -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">التقارير</p>
                    <a href="<?php echo $hospital_base_url; ?>/reports/appointments_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>تقرير المواعيد</span>
                    </a>

                    <a href="<?php echo $hospital_base_url; ?>/reports/patients_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-users w-6"></i>
                        <span>تقرير المرضى</span>
                    </a>

                    <a href="<?php echo $hospital_base_url; ?>/reports/financial_report.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-money-bill-wave w-6"></i>
                        <span>التقارير المالية</span>
                    </a>
                </div>

                <!-- Settings -->
                <div class="mt-2">
                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">الإعدادات</p>
                    <a href="<?php echo $hospital_base_url; ?>/settings.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-cog w-6"></i>
                        <span>إعدادات الحساب</span>
                    </a>
                    <a href="<?php echo $hospital_base_url; ?>/profile.php" class="flex items-center gap-3 px-4 py-2 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-user-cog w-6"></i>
                        <span>الملف الشخصي</span>
                    </a>
                </div>

                <!-- Logout -->
                <div class="border-t my-4 pt-4">
                    <a href="<?php echo $logout_url; ?>" class="flex items-center gap-3 px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition-colors" onclick="toggleSidebar()">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10 sticky top-0">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="text-gray-600 hover:text-blue-600 md:hidden">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800 hidden md:block">
                        <?php
                        $pageTitles = [
                            'index.php' => 'الرئيسية',
                            'doctors.php' => 'قائمة الأطباء',
                            'doctor_form.php' => isset($_GET['id']) ? 'تعديل بيانات الطبيب' : 'إضافة طبيب جديد',
                            'clinics.php' => 'قائمة العيادات',
                            'clinic_form.php' => isset($_GET['id']) ? 'تعديل بيانات العيادة' : 'إضافة عيادة جديدة',
                            'appointments.php' => 'المواعيد',
                            'patients.php' => 'المرضى',
                            'settings.php' => 'الإعدادات',
                            'profile.php' => 'الملف الشخصي'
                        ];
                        echo $pageTitles[basename($_SERVER['PHP_SELF'])] ?? 'لوحة التحكم';
                        ?>
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notifButton" class="p-2 text-gray-500 hover:text-blue-600 focus:outline-none relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <!-- Notifications Dropdown -->
                        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-100 z-50 text-right transform translate-x-4">
                            <div class="px-6 py-3 border-b">
                                <p class="text-sm font-semibold text-gray-800">الإشعارات</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <a href="<?php echo $hospital_base_url; ?>/appointments.php" class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50">
                                    <div class="mt-0.5 text-blue-500">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="flex-1 pr-1">
                                        <p class="text-sm text-gray-800">مواعيد قيد الانتظار</p>
                                        <p class="text-xs text-gray-500">عدد المواعيد:
                                            <strong class="text-gray-800">
                                            <?php
                                                try {
                                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments a JOIN clinics c ON a.clinic_id = c.id WHERE c.hospital_id = ? AND a.status = 'pending'");
                                                    $stmt->execute([$hospital_id]);
                                                    echo (int)$stmt->fetchColumn();
                                                } catch (Exception $e) { echo '0'; }
                                            ?>
                                            </strong>
                                        </p>
                                    </div>
                                </a>
                                <div class="px-6 py-3 text-xs text-gray-500 border-t">لا توجد إشعارات أخرى</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile -->
                    <div class="relative group" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="hidden md:inline text-sm font-medium text-gray-700">
                                <?php echo htmlspecialchars($user['name'] ?? 'المسؤول'); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open"
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="<?php echo $hospital_base_url; ?>/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-circle ml-2"></i>
                                <span>الملف الشخصي</span>
                            </a>
                            <a href="<?php echo $hospital_base_url; ?>/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog ml-2"></i>
                                <span>الإعدادات</span>
                            </a>
                            <div class="border-t my-1"></div>
                            <a href="<?php echo $logout_url; ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt ml-2"></i>
                                <span>تسجيل الخروج</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Scrollable Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6">
                <!-- Add Alpine.js for dropdown functionality -->
                <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>

                <script>
                // Toggle mobile sidebar
                function toggleSidebar() {
                    const sidebar = document.getElementById('mobileSidebar');
                    const overlay = document.getElementById('sidebarOverlay');

                    sidebar.classList.toggle('translate-x-full');
                    overlay.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                }

                // Close sidebar when clicking on a link
                document.addEventListener('DOMContentLoaded', function() {
                    const links = document.querySelectorAll('#mobileSidebar a');
                    links.forEach(link => {
                        link.addEventListener('click', () => {
                            if (window.innerWidth < 768) { // Only for mobile
                                toggleSidebar();
                            }
                        });
                    });

                    // Close sidebar when pressing Escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && !document.getElementById('mobileSidebar').classList.contains('translate-x-full')) {
                            toggleSidebar();
                        }
                    });

                    // Notifications dropdown toggle
                    const notifBtn = document.getElementById('notifButton');
                    const notifDropdown = document.getElementById('notifDropdown');
                    if (notifBtn && notifDropdown) {
                        notifBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            notifDropdown.classList.toggle('hidden');
                        });
                        // click outside to close
                        document.addEventListener('click', function(e) {
                            if (!notifDropdown.classList.contains('hidden')) {
                                const isClickInside = notifDropdown.contains(e.target) || notifBtn.contains(e.target);
                                if (!isClickInside) notifDropdown.classList.add('hidden');
                            }
                        });
                        // Escape key to close
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                notifDropdown.classList.add('hidden');
                            }
                        });
                    }
                });
                </script>

