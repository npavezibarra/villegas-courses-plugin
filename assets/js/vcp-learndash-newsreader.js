(function () {
    if (typeof document === 'undefined') {
        return;
    }

    var ready = function (callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    };

    var formatValue = function (value) {
        return Math.round(Number(value)) + 'px';
    };

    ready(function () {
        var panels = document.querySelectorAll('.ld-tabs-content [role="tabpanel"]');
        if (!panels.length) {
            return;
        }

        panels.forEach(function (panel) {
            if (panel.querySelector('.nr-controls')) {
                return;
            }

            var computed = window.getComputedStyle(panel);
            var baseSize = parseFloat(computed.getPropertyValue('--p-size'));
            if (!baseSize || Number.isNaN(baseSize)) {
                baseSize = 18;
            }

            var wrapper = document.createElement('div');
            wrapper.className = 'nr-controls';

            var label = document.createElement('label');
            label.setAttribute('for', 'nr-slider-' + Math.random().toString(36).slice(2));
            label.textContent = 'Paragraph size';

            var slider = document.createElement('input');
            slider.type = 'range';
            slider.min = '14';
            slider.max = '28';
            slider.step = '1';
            slider.value = baseSize.toString();
            slider.id = label.getAttribute('for');

            var output = document.createElement('output');
            output.textContent = formatValue(baseSize);

            var update = function (value) {
                panel.style.setProperty('--p-size', value + 'px');
                output.textContent = formatValue(value);
            };

            slider.addEventListener('input', function (event) {
                update(event.target.value);
            });

            wrapper.appendChild(label);
            wrapper.appendChild(slider);
            wrapper.appendChild(output);

            panel.insertBefore(wrapper, panel.firstChild);
        });
    });
})();
