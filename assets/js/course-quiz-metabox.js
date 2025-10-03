(function ($) {
    'use strict';

    $(function () {
        if (typeof $.fn.select2 === 'undefined') {
            return;
        }

        var data = window.villegasCourseQuizData || {};
        var labels = data.labels || {};

        $('.villegas-quiz-select').each(function () {
            var $select = $(this);
            var placeholder = $select.data('placeholder') || labels.placeholder || '';
            var selectedId = $select.data('selected-id');
            var selectedText = $select.data('selected-text');

            if (selectedId && selectedText) {
                var existingOption = $select.find('option[value="' + selectedId + '"]');
                if (!existingOption.length) {
                    var option = new Option(selectedText, selectedId, true, true);
                    $select.append(option);
                }
            }

            $select.select2({
                width: '100%',
                ajax: {
                    url: data.restUrl,
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    headers: {
                        'X-WP-Nonce': data.nonce || ''
                    },
                    data: function (params) {
                        return {
                            search: params.term || ''
                        };
                    },
                    processResults: function (response) {
                        return {
                            results: response && response.results ? response.results : []
                        };
                    }
                },
                placeholder: placeholder,
                allowClear: true,
                minimumInputLength: 1,
                language: {
                    inputTooShort: function () {
                        return labels.inputTooShort || '';
                    },
                    noResults: function () {
                        return labels.noResults || '';
                    },
                    searching: function () {
                        return labels.searching || '';
                    }
                }
            });
        });
    });
})(jQuery);
