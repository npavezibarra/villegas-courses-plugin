(function($){
    if (window.politeiaCustomPollerInitialized) {
        return;
    }

    const ajaxConfig = window.villegasAjax || {};
    const quizConfig = window.quizConfig;

    if (!quizConfig || !quizConfig.quizId || !ajaxConfig.ajaxUrl || !quizConfig.nonce) {
        return;
    }

    let lastSeen = 0;
    if (typeof quizConfig.currentActivityId === 'number' && !isNaN(quizConfig.currentActivityId)) {
        lastSeen = quizConfig.currentActivityId;
    } else if (typeof quizConfig.currentActivityId === 'string' && quizConfig.currentActivityId) {
        const parsed = parseInt(quizConfig.currentActivityId, 10);
        lastSeen = isNaN(parsed) ? 0 : parsed;
    }

    function pollCustomAttempt(lastId) {
        $.post(ajaxConfig.ajaxUrl, {
            action: 'politeia_poll_latest_attempt_strict',
            quiz_id: quizConfig.quizId,
            last_activity_id: lastId,
            nonce: quizConfig.nonce
        }).done(function(res){
            if (!res || !res.success || !res.data) {
                return;
            }

            const data = res.data;
            console.log('[CustomPoll]', data);

            if (data.status === 'waiting_new_attempt' || data.status === 'pending') {
                const retryDelay = parseInt(data.retry_after, 10) > 0 ? parseInt(data.retry_after, 10) : 2;
                setTimeout(function(){ pollCustomAttempt(lastId); }, retryDelay * 1000);
                return;
            }

            if (data.status === 'ready') {
                if (data.activity_id) {
                    lastSeen = parseInt(data.activity_id, 10) || lastSeen;
                    $('#custom-activity-id').text(lastSeen);
                }

                if (typeof data.percentage === 'number') {
                    $('#custom-percentage').text(Math.round(data.percentage) + '%');
                }
            }
        }).fail(function(){
            console.error('[CustomPoll] AJAX failed');
            setTimeout(function(){ pollCustomAttempt(lastId); }, 2000);
        });
    }

    $(document).on('learndash-quiz-finished', function(){
        pollCustomAttempt(lastSeen);
    });
})(jQuery);
