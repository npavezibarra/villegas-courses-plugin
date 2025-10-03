(function($){
    if (window.politeiaCustomPollerInitialized) {
        return;
    }

    const ajaxConfig = window.villegasAjax || {};
    const quizConfig = window.quizConfig;

    if (!quizConfig || !quizConfig.quizId || !ajaxConfig.ajaxUrl || !quizConfig.nonce) {
        return;
    }

    window.politeiaCustomPollerInitialized = true;

    let baselineId = 0;
    if (typeof quizConfig.currentActivityId === 'number' && !isNaN(quizConfig.currentActivityId)) {
        baselineId = quizConfig.currentActivityId;
    } else if (typeof quizConfig.currentActivityId === 'string' && quizConfig.currentActivityId) {
        const parsed = parseInt(quizConfig.currentActivityId, 10);
        baselineId = isNaN(parsed) ? 0 : parsed;
    }

    function pollNewAttempt() {
        $.post(ajaxConfig.ajaxUrl, {
            action: 'politeia_poll_new_attempt',
            quiz_id: quizConfig.quizId,
            baseline_activity_id: baselineId,
            nonce: quizConfig.nonce
        }).done(function(res){
            if (!res || !res.success || !res.data) {
                return;
            }

            const data = res.data;
            console.log('[CustomPoll]', data);

            if (data.status === 'waiting_new_attempt' || data.status === 'pending') {
                const retryDelay = parseInt(data.retry_after, 10) > 0 ? parseInt(data.retry_after, 10) : 2;
                if (data.activity_id) {
                    $('#custom-activity-id').text(data.activity_id);
                } else {
                    $('#custom-activity-id').text('—');
                }
                $('#custom-percentage').text('…');
                setTimeout(pollNewAttempt, retryDelay * 1000);
                return;
            }

            if (data.status === 'ready') {
                if (data.activity_id) {
                    $('#custom-activity-id').text(data.activity_id);
                    const parsedId = parseInt(data.activity_id, 10);
                    if (!isNaN(parsedId)) {
                        baselineId = parsedId;
                    }
                }

                if (typeof data.percentage === 'number') {
                    $('#custom-percentage').text(Math.round(data.percentage) + '%');
                }
            }
        }).fail(function(){
            console.error('[CustomPoll] AJAX fail');
            setTimeout(pollNewAttempt, 2000);
        });
    }

    $(document).on('learndash-quiz-finished', function(){
        $('#custom-activity-id').text('—');
        $('#custom-percentage').text('…');
        pollNewAttempt();
    });
})(jQuery);
