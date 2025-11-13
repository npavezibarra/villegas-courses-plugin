(function () {
    if (typeof window.VILLEGAS_COURSE_CONSOLE === 'undefined') {
        return;
    }

    var courseData = window.VILLEGAS_COURSE_CONSOLE;

    console.log('Course Number of Lessons:', courseData.totalLessons);
    console.log('Lessons Completed:', courseData.lessonsCompleted);
    console.log('Final Quiz id:', courseData.finalQuizId);
    console.log('Quiz slug:', courseData.quizSlug);
    console.log('Site URL:', courseData.siteUrl);

    document.addEventListener('DOMContentLoaded', function () {
        var quizSlug = courseData.quizSlug || '';
        var siteUrl = courseData.siteUrl || '';

        if (!quizSlug || !siteUrl) {
            return;
        }

        var finalBtn = document.getElementById('final-evaluation-button');
        if (!finalBtn) {
            return;
        }

        if (!siteUrl.endsWith('/')) {
            siteUrl += '/';
        }

        var finalUrl = siteUrl + 'evaluaciones/' + quizSlug + '/';
        finalBtn.setAttribute('href', finalUrl);
        console.log('Final Quiz URL set to:', finalUrl);
    });
})();
