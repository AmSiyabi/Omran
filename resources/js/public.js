/**
 * Public-site behavior — deliberately tiny and Livewire-free.
 * Scroll reveals fire once per element (spec §5.2), and reduced-motion
 * users see everything immediately.
 */
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealables = document.querySelectorAll('[data-reveal]');

if (reduceMotion) {
    revealables.forEach((el) => el.classList.add('is-revealed'));
} else {
    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
    );

    revealables.forEach((el) => observer.observe(el));
}

// قائمة الجوال
const navToggle = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');

navToggle?.addEventListener('click', () => {
    const isOpen = navMenu.classList.toggle('hidden') === false;
    navToggle.setAttribute('aria-expanded', String(isOpen));
});
