@once
<script>
window.initListingGalleries = function initListingGalleries(root) {
    const scope = root && root.querySelectorAll ? root : document;
    const scrollers = (scope.matches && scope.matches('[data-listing-gallery]'))
        ? [scope]
        : Array.from(scope.querySelectorAll('[data-listing-gallery]'));
    scrollers.forEach(bindListingGallery);
};

function bindListingGallery(scroller) {
    if (!(scroller instanceof Element) || scroller.dataset.galleryBound === '1') {
        return;
    }
    scroller.dataset.galleryBound = '1';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const loop = scroller.getAttribute('data-loop') !== '0';
    const card = scroller.closest('.vehicle-item');
    const currentEl = card ? card.querySelector('[data-listing-photo-current]') : null;
    let currentReal = 0;

    function realSlides() {
        return Array.from(scroller.querySelectorAll('[data-listing-gallery-slide]:not([data-clone])'));
    }

    function allSlides() {
        return Array.from(scroller.querySelectorAll('[data-listing-gallery-slide]'));
    }

    function slideForReal(index) {
        return scroller.querySelector('[data-listing-gallery-slide][data-real-index="' + index + '"]:not([data-clone])');
    }

    function realIndexOf(slide) {
        const n = Number(slide && slide.getAttribute('data-real-index'));
        return Number.isFinite(n) ? n : 0;
    }

    function jumpToSlide(slide) {
        if (!slide) return;
        scroller.scrollLeft = slide.offsetLeft;
    }

    function scrollToSlide(slide, smooth) {
        if (!slide) return;
        scroller.scrollTo({
            left: slide.offsetLeft,
            behavior: smooth && !prefersReducedMotion ? 'smooth' : 'instant',
        });
    }

    function closestSlide() {
        const slides = allSlides();
        if (!slides.length) return null;
        const center = scroller.scrollLeft + scroller.clientWidth / 2;
        let closest = slides[0];
        let min = Infinity;
        slides.forEach((slide) => {
            const c = slide.offsetLeft + slide.offsetWidth / 2;
            const d = Math.abs(center - c);
            if (d < min) {
                min = d;
                closest = slide;
            }
        });
        return closest;
    }

    function updateCount(index) {
        currentReal = index;
        if (currentEl) {
            currentEl.textContent = String(index + 1);
        }
    }

    function settle() {
        const slide = closestSlide();
        if (!slide) return;
        if (slide.getAttribute('data-clone') === 'last') {
            jumpToSlide(slideForReal(realSlides().length - 1));
            updateCount(realSlides().length - 1);
            return;
        }
        if (slide.getAttribute('data-clone') === 'first') {
            jumpToSlide(slideForReal(0));
            updateCount(0);
            return;
        }
        updateCount(realIndexOf(slide));
    }

    function ensureClones() {
        const reals = realSlides();
        if (!loop || reals.length < 2 || scroller.dataset.clonesReady === '1') {
            return;
        }
        const first = reals[0];
        const last = reals[reals.length - 1];
        const cloneFirst = first.cloneNode(true);
        const cloneLast = last.cloneNode(true);
        cloneFirst.setAttribute('data-clone', 'first');
        cloneLast.setAttribute('data-clone', 'last');
        cloneFirst.setAttribute('aria-hidden', 'true');
        cloneLast.setAttribute('aria-hidden', 'true');
        cloneFirst.setAttribute('tabindex', '-1');
        cloneLast.setAttribute('tabindex', '-1');
        const firstImg = cloneFirst.querySelector('img');
        const lastImg = cloneLast.querySelector('img');
        if (firstImg) firstImg.alt = '';
        if (lastImg) lastImg.alt = '';
        scroller.insertBefore(cloneLast, first);
        scroller.appendChild(cloneFirst);
        scroller.dataset.clonesReady = '1';
    }

    function go(delta) {
        const count = realSlides().length;
        if (count < 2) return;

        function afterMove(fn) {
            let finished = false;
            const finish = function () {
                if (finished) return;
                finished = true;
                scroller.removeEventListener('scrollend', finish);
                fn();
            };
            scroller.addEventListener('scrollend', finish, { once: true });
            window.setTimeout(finish, prefersReducedMotion ? 40 : 380);
        }

        const next = currentReal + delta;
        if (loop && next >= count) {
            scrollToSlide(scroller.querySelector('[data-clone="first"]'), true);
            updateCount(0);
            afterMove(function () {
                jumpToSlide(slideForReal(0));
                updateCount(0);
            });
            return;
        }
        if (loop && next < 0) {
            scrollToSlide(scroller.querySelector('[data-clone="last"]'), true);
            updateCount(count - 1);
            afterMove(function () {
                jumpToSlide(slideForReal(count - 1));
                updateCount(count - 1);
            });
            return;
        }
        const clamped = Math.max(0, Math.min(count - 1, next));
        scrollToSlide(slideForReal(clamped), true);
        updateCount(clamped);
    }

    ensureClones();
    jumpToSlide(slideForReal(0));
    updateCount(0);

    if ('onscrollsnapchanging' in Element.prototype) {
        scroller.addEventListener('scrollsnapchanging', (event) => {
            const pending = event.snapTargetInline;
            if (pending) updateCount(realIndexOf(pending));
        });
        scroller.addEventListener('scrollsnapchange', settle);
    }

    let rafId = null;
    let timeout = 0;
    scroller.addEventListener('scroll', () => {
        if (rafId) return;
        rafId = requestAnimationFrame(() => {
            rafId = null;
            const slide = closestSlide();
            if (slide) updateCount(realIndexOf(slide));
        });
        window.clearTimeout(timeout);
        timeout = window.setTimeout(settle, 100);
    }, { passive: true });
    scroller.addEventListener('scrollend', () => {
        window.clearTimeout(timeout);
        settle();
    });

    let pointerStartX = 0;
    let dragged = false;
    scroller.addEventListener('pointerdown', (event) => {
        pointerStartX = event.clientX;
        dragged = false;
    });
    scroller.addEventListener('pointermove', (event) => {
        if (Math.abs(event.clientX - pointerStartX) > 8) {
            dragged = true;
        }
    });
    scroller.addEventListener('click', (event) => {
        if (!dragged) return;
        event.preventDefault();
        event.stopPropagation();
        dragged = false;
    });

    const imageBox = scroller.closest('.vehicle-image-container') || card;
    const prevBtn = imageBox ? imageBox.querySelector('[data-listing-gallery-prev]') : null;
    const nextBtn = imageBox ? imageBox.querySelector('[data-listing-gallery-next]') : null;
    if (prevBtn) {
        prevBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            go(-1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            go(1);
        });
    }

    scroller.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            go(1);
            return;
        }
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            go(-1);
            return;
        }
        if (event.key === 'Enter' || event.key === ' ') {
            const href = slideForReal(currentReal) && slideForReal(currentReal).getAttribute('href');
            if (!href) return;
            event.preventDefault();
            window.location.href = href;
        }
    });

    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(() => {
            jumpToSlide(slideForReal(currentReal));
        }).observe(scroller);
    }
}

(function bootListingGalleries() {
    const start = function () {
        window.initListingGalleries(document);
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
@endonce
