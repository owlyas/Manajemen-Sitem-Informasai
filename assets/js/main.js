/**
 * Bloom&Books - Client-side Interactivity Script
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // 1b. Mobile Quick Search Toggle
    const mobileSearchToggleBtn = document.getElementById('mobileSearchToggleBtn');
    const mobileSearchBar = document.getElementById('mobileSearchBar');
    const mobileSearchInput = document.getElementById('mobileSearchInput');

    if (mobileSearchToggleBtn && mobileSearchBar) {
        mobileSearchToggleBtn.addEventListener('click', () => {
            mobileSearchBar.classList.toggle('hidden');
            if (!mobileSearchBar.classList.contains('hidden') && mobileSearchInput) {
                mobileSearchInput.focus();
            }
        });
    }

    // 2. User Dropdown Toggle (if logged in)
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!userDropdownMenu.contains(e.target) && !userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.add('hidden');
            }
        });
    }

    // 3. Auto dismiss flash alerts after 5 seconds
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.style.transition = 'opacity 0.5s ease';
            flashAlert.style.opacity = '0';
            setTimeout(() => flashAlert.remove(), 500);
        }, 5000);
    }
});
