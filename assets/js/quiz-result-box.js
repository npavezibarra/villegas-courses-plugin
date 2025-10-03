(function($){
    const ajaxConfig = window.villegasAjax || {};
    const quizConfig = window.quizConfig || {};

    if (!ajaxConfig.ajaxUrl || !quizConfig.quizId || !quizConfig.nonce) {
        return;
    }

    const baselineId = Number(quizConfig.currentActivityId) || 0;

    function pollNewAttempt(){
        $.post(ajaxConfig.ajaxUrl, {
            action: 'politeia_poll_new_attempt',
            quiz_id: quizConfig.quizId,
            baseline_activity_id: baselineId,
            nonce: quizConfig.nonce
        }).done(function(res){
            if (!res || !res.success) {
                return;
            }

            const d = res.data || {};
            console.log('[CustomPoll]', d);

            if (d.status === 'waiting_new_attempt' || d.status === 'pending') {
                $('#custom-activity-id').text(d.activity_id || '—');
                $('#custom-percentage').text('…');
                setTimeout(pollNewAttempt, (d.retry_after || 2) * 1000);
                return;
            }

            if (d.status === 'ready') {
                $('#custom-activity-id').text(d.activity_id);
                if (typeof d.percentage === 'number') {
                    $('#custom-percentage').text(Math.round(d.percentage) + '%');
                }
            }
        }).fail(function(){
            console.error('[CustomPoll] AJAX fail');
            setTimeout(pollNewAttempt, 2000);
        });
    }

    $(document).on('learndash-quiz-finished', function(){
        pollNewAttempt();
    });
})(jQuery);
