            </main>

            <!-- Bottom Minimal Admin Footer -->
            <footer class="h-12 border-t border-slate-200/60 bg-white/50 px-8 flex items-center justify-between text-xs text-slate-400 shrink-0">
                <span>&copy; <?= date('Y') ?> Bloom&Books Back-Office &bull; Versi 2.0.0</span>
                <span>Role: <strong class="text-primary font-semibold">Administrator</strong></span>
            </footer>

        </div>
    </div>

    <!-- Script Interaktif Sidebar Admin -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) {
                sidebar.classList.toggle('-translate-x-full');
            }
            if (backdrop) {
                backdrop.classList.toggle('hidden');
            }
        }

        // Auto dismiss flash alert after 5s
        const flashAlert = document.getElementById('adminFlashAlert');
        if (flashAlert) {
            setTimeout(() => {
                flashAlert.style.transition = 'opacity 0.4s ease';
                flashAlert.style.opacity = '0';
                setTimeout(() => flashAlert.remove(), 400);
            }, 5000);
        }
    </script>
</body>
</html>
