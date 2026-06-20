document.addEventListener('DOMContentLoaded', () => {

    const carousels = document.querySelectorAll('.esquina-carousel');

    carousels.forEach(carousel => {

        let interval;

        function startAutoScroll() {

            interval = setInterval(() => {

                carousel.scrollBy({
                    left: 200,
                    behavior: 'smooth'
                });

                const maxScroll =
                    carousel.scrollWidth -
                    carousel.clientWidth;

                if (carousel.scrollLeft >= maxScroll - 10) {

                    carousel.scrollTo({
                        left: 0,
                        behavior: 'smooth'
                    });

                }

            }, esquinaRecSettings.speed || 5000);

        }

        function stopAutoScroll() {
            clearInterval(interval);
        }

        if (esquinaRecSettings.autoplay) {
            startAutoScroll();
        }

        carousel.addEventListener(
            'mouseenter',
            stopAutoScroll
        );

        carousel.addEventListener(
            'mouseleave',
            startAutoScroll
        );

    });

});

document.querySelectorAll('.esquina-next')
.forEach(btn => {

    btn.addEventListener('click', () => {

        const carousel =
            btn.parentElement.querySelector(
                '.esquina-carousel'
            );

        carousel.scrollBy({
            left:300,
            behavior:'smooth'
        });

    });

});

document.querySelectorAll('.esquina-prev')
.forEach(btn => {

    btn.addEventListener('click', () => {

        const carousel =
            btn.parentElement.querySelector(
                '.esquina-carousel'
            );

        carousel.scrollBy({
            left:-300,
            behavior:'smooth'
        });

    });

});