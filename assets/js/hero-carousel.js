(function () {
    function pad(value) {
        return String(value).length < 2 ? '0' + value : String(value);
    }

    document.querySelectorAll('[data-hero-carousel]').forEach(function (root) {
        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-hero-slide]'));
        if (slides.length < 2) {
            return;
        }

        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hero-dot]'));
        var previousButton = root.querySelector('[data-hero-prev]');
        var nextButton = root.querySelector('[data-hero-next]');
        var counter = root.querySelector('[data-hero-count]');
        var progressBar = root.querySelector('.hero-progress span');
        var interval = Math.max(2500, parseInt(root.getAttribute('data-interval'), 10) || 6500);
        var activeIndex = Math.max(0, slides.findIndex(function (slide) {
            return slide.classList.contains('is-active');
        }));
        var paused = false;
        var timer = null;
        var startedAt = 0;

        function paint() {
            slides.forEach(function (slide, index) {
                var isActive = index === activeIndex;
                slide.classList.toggle('is-active', isActive);
                if (isActive) {
                    slide.removeAttribute('aria-hidden');
                } else {
                    slide.setAttribute('aria-hidden', 'true');
                }
            });
            dots.forEach(function (dot, index) {
                var isActive = index === activeIndex;
                dot.classList.toggle('is-active', isActive);
                if (isActive) {
                    dot.setAttribute('aria-current', 'true');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });
            if (counter) {
                counter.textContent = pad(activeIndex + 1) + ' / ' + pad(slides.length);
            }
            restart();
        }

        function goTo(index) {
            activeIndex = (index + slides.length) % slides.length;
            paint();
        }

        function tick() {
            if (paused || document.hidden) {
                return;
            }
            var elapsed = Date.now() - startedAt;
            if (progressBar) {
                progressBar.style.transform = 'scaleX(' + Math.min(1, elapsed / interval) + ')';
            }
            if (elapsed >= interval) {
                goTo(activeIndex + 1);
            }
        }

        function restart() {
            if (timer) {
                clearInterval(timer);
            }
            startedAt = Date.now();
            if (progressBar) {
                progressBar.style.transform = 'scaleX(0)';
            }
            timer = setInterval(tick, 60);
        }

        if (previousButton) {
            previousButton.addEventListener('click', function () {
                goTo(activeIndex - 1);
            });
        }
        if (nextButton) {
            nextButton.addEventListener('click', function () {
                goTo(activeIndex + 1);
            });
        }
        dots.forEach(function (dot, index) {
            dot.addEventListener('click', function () {
                goTo(index);
            });
        });

        root.addEventListener('mouseenter', function () {
            paused = true;
        });
        root.addEventListener('mouseleave', function () {
            paused = false;
            restart();
        });
        root.addEventListener('focusin', function () {
            paused = true;
        });
        root.addEventListener('focusout', function () {
            paused = false;
            restart();
        });
        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goTo(activeIndex - 1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                goTo(activeIndex + 1);
            }
        });

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            paused = true;
        }

        paint();
    });
})();
