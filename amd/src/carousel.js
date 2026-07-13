// This file is part of Moodle - http://moodle.org/

define(['jquery'], function($) {
    return {
        init: function(carouselid) {
            var carousel = $('#' + carouselid);
            var button = carousel.find('.block-iednews-pause');

            if (!carousel.length || !button.length) {
                return;
            }

            var setPaused = function(paused) {
                var label = paused ? button.data('resume-label') : button.data('pause-label');
                button.attr({
                    'aria-pressed': paused ? 'true' : 'false',
                    'aria-label': label,
                    'title': label
                });
                button.find('.block-iednews-pause-icon').toggleClass('d-none', paused);
                button.find('.block-iednews-resume-icon').toggleClass('d-none', !paused);
                button.find('.block-iednews-pause-label').text(label);
            };

            var reducedmotion = window.matchMedia &&
                window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reducedmotion) {
                carousel.carousel('pause');
                setPaused(true);
            }

            button.on('click', function() {
                var paused = button.attr('aria-pressed') === 'true';
                if (paused) {
                    carousel.carousel('cycle');
                    setPaused(false);
                } else {
                    carousel.carousel('pause');
                    setPaused(true);
                }
            });
        }
    };
});
