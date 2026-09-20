    </div> <!-- end flex-1 flex -->

    <!-- Global Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 px-6 text-center text-xs text-slate-500 no-print">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                <?= htmlspecialchars($school['name']) ?> • สังกัด <?= htmlspecialchars($school['affiliation']) ?>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <span>ปีงบประมาณ พ.ศ. <?= $fiscalYear['year'] ?></span>
                <span>•</span>
                <span>ระบบแผนปฏิบัติการและจัดสรรงบประมาณ (PHP Edition)</span>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Mobile drawer toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const closeSidebarBtn = document.getElementById('close-sidebar-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');

        function toggleSidebar() {
            if (!sidebar || !sidebarBackdrop) return;
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                sidebarBackdrop.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebarBackdrop.classList.remove('hidden');
            }
        }

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', toggleSidebar);
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>
