document.addEventListener('DOMContentLoaded', () => {

    const carousels = document.querySelectorAll('.esquina-carousel');

    carousels.forEach(carousel => {

        let interval;

        function getScrollStep() {
            const card = carousel.querySelector('.esquina-card');

            if (!card) {
                return 200;
            }

            const styles = window.getComputedStyle(carousel);
            const gap = parseFloat(styles.columnGap || styles.gap || '16') || 16;

            return card.offsetWidth + gap;
        }

        function startAutoScroll() {

            if (!window.esquinaRecSettings || !esquinaRecSettings.autoplay) {
                return;
            }

            interval = setInterval(() => {

                const step = getScrollStep();
                const maxScroll = carousel.scrollWidth - carousel.clientWidth;

                if (carousel.scrollLeft >= maxScroll - 10) {
                    carousel.scrollTo({ left: 0, behavior: 'smooth' });
                    return;
                }

                carousel.scrollBy({ left: step, behavior: 'smooth' });

            }, esquinaRecSettings.speed || 5000);
        }

        function stopAutoScroll() {
            clearInterval(interval);
        }

        if (window.esquinaRecSettings && esquinaRecSettings.autoplay) {
            startAutoScroll();
        }

        carousel.addEventListener('mouseenter', stopAutoScroll);
        carousel.addEventListener('mouseleave', startAutoScroll);

        const wrapper = carousel.closest('.esquina-carousel-viewport');

        if (!wrapper) {
            return;
        }

        const prev = wrapper.querySelector('.esquina-prev');
        const next = wrapper.querySelector('.esquina-next');

        if (prev) {
            prev.addEventListener('click', () => {
                carousel.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
            });
        }

        if (next) {
            next.addEventListener('click', () => {
                carousel.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
            });
        }

    });

});
